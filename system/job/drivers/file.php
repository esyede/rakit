<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Event;
use System\Carbon;
use System\Storage;
use System\Config;

class File extends Driver
{
    /**
     * Berisi path direktori penyimpanan job.
     *
     * @var string
     */
    protected $path;

    /**
     * Buat instance driver file baru.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, DS) . DS;

        if (!is_dir($this->path)) {
            Storage::mkdir($this->path, 0755);
        }
    }

    /**
     * Tambahkan sebuah job.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     * @param string      $queue
     * @param bool        $without_overlapping
     *
     * @return bool
     */
    public function add($name, array $payloads = [], $scheduled_at = null, $queue = 'default', $without_overlapping = false)
    {
        $name = Str::slug($name);
        $id = Str::nanoid();
        $data = [
            'id' => $id,
            'name' => $name,
            'queue' => $queue,
            'without_overlapping' => $without_overlapping,
            'payloads' => serialize($payloads),
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $file = $this->path . $name . '__' . $id . '.job.php';
        Storage::put($file, static::guard(serialize($data)), LOCK_EX);

        $this->log(sprintf('Job added: %s - #%s (queue: %s)', $name, $id, $queue));
        return true;
    }

    /**
     * Cek apakah job sedang overlapping.
     *
     * @param string $name
     * @param string $queue
     *
     * @return bool
     */
    public function has_overlapping($name, $queue = 'default')
    {
        $name = Str::slug($name);
        $files = glob($this->path . $name . '__*.job.php');

        if (empty($files)) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(static::unguard(Storage::get($file)));

                if (
                    isset($data['queue']) && $data['queue'] === $queue
                    && isset($data['without_overlapping']) && $data['without_overlapping']
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Hapus job berdasarkan nama.
     *
     * @param string      $name
     * @param string|null $queue
     *
     * @return bool
     */
    public function forget($name, $queue = null)
    {
        $name = Str::slug($name);
        $pattern = $this->path . $name . '__*' . '.job.php';
        $files = glob($pattern);

        foreach ($files as $file) {
            if ($queue) {
                $data = unserialize(static::unguard(Storage::get($file)));

                if (isset($data['queue']) && $data['queue'] === $queue) {
                    Storage::delete($file);
                }
            } else {
                Storage::delete($file);
            }
        }

        $this->log(sprintf('Jobs deleted: %s (queue: %s)', $name, $queue ?: 'all'));
        return true;
    }

    /**
     * Jalankan antrian job.
     *
     * @param string      $name
     * @param int         $retries
     * @param int         $sleep_ms
     * @param string|null $queue
     *
     * @return bool
     */
    public function run($name, $retries = 1, $sleep_ms = 0, $queue = null)
    {
        $config = Config::get('job');
        $name = Str::slug($name);
        $files = glob($this->path . $name . '__*' . '.job.php');

        if (empty($files)) {
            $this->log('No job files found');
            return false;
        }

        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $successful = [];
        $failed = [];
        $processed = 0;

        foreach ($files as $file) {
            if ($processed >= $config['max_job']) {
                break;
            }

            $data = unserialize(static::unguard(Storage::get($file)));

            if ($queue && isset($data['queue']) && $data['queue'] !== $queue) {
                continue;
            }

            if (Carbon::parse($data['scheduled_at'])->lte(Carbon::now())) {
                $processed++;
                $attempts = 0;
                $success = false;

                while ($attempts < $retries && !$success) {
                    $attempts++;

                    try {
                        Event::fire('rakit.jobs.process', [$data]);
                        $successful[] = $file;
                        $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));
                        $success = true;
                    } catch (\Throwable $e) {
                        if ($attempts >= $retries) {
                            $failed[] = ['file' => $file, 'data' => $data, 'exception' => $e];
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($attempts >= $retries) {
                            $failed[] = ['file' => $file, 'data' => $data, 'exception' => $e];
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($successful)) {
            foreach ($successful as $file) {
                Storage::delete($file);
            }
        }

        if (!empty($failed)) {
            foreach ($failed as $job) {
                $this->move_to_failed($job['data'], $job['exception']);
                Storage::delete($job['file']);
            }
        }

        return true;
    }

    /**
     * Jalankan semua job.
     *
     * @param int        $retries
     * @param int        $sleep_ms
     * @param array|null $queues
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0, $queues = null)
    {
        $config = Config::get('job');
        $files = glob($this->path . '*.job.php');

        // Exclude failed jobs
        $files = array_filter($files, function ($file) {
            return strpos(basename($file), 'failed__') !== 0;
        });

        if (empty($files)) {
            $this->log('No job files found');
            return false;
        }

        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $successful = [];
        $failed = [];
        $processed = 0;

        foreach ($files as $file) {
            if ($processed >= $config['max_job']) {
                break;
            }

            $data = unserialize(static::unguard(Storage::get($file)));

            if ($queues && is_array($queues) && !empty($queues)) {
                if (!isset($data['queue']) || !in_array($data['queue'], $queues)) {
                    continue;
                }
            }

            if (Carbon::parse($data['scheduled_at'])->lte(Carbon::now())) {
                $processed++;
                $attempts = 0;
                $success = false;

                while ($attempts < $retries && !$success) {
                    $attempts++;

                    try {
                        Event::fire('rakit.jobs.process', [$data]);
                        $successful[] = $file;
                        $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));
                        $success = true;
                    } catch (\Throwable $e) {
                        if ($attempts >= $retries) {
                            $failed[] = ['file' => $file, 'data' => $data, 'exception' => $e];
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($attempts >= $retries) {
                            $failed[] = ['file' => $file, 'data' => $data, 'exception' => $e];
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($successful)) {
            foreach ($successful as $file) {
                Storage::delete($file);
            }
        }

        if (!empty($failed)) {
            foreach ($failed as $job) {
                $this->move_to_failed($job['data'], $job['exception']);
                Storage::delete($job['file']);
            }
        }

        return true;
    }

    /**
     * Helper untuk proteksi akses file via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        return $guard . $value;
    }

    /**
     * Helper untuk buang proteksi akses file via browser.
     * (Kebalikan dari method guard).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        return str_replace($guard, '', $value);
    }

    /**
     * Pindahkan job ke failed jobs.
     *
     * @param array      $data
     * @param \Exception $exception
     */
    protected function move_to_failed($data, $exception)
    {
        $error = get_class($exception)
            . (('' === $exception->getMessage()) ? '' : ': ' . $exception->getMessage())
            . ' in ' . $exception->getFile() . ':' . $exception->getLine();
        $data = array_merge($data, [
            'exception' => $error,
            'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $file = $this->path . 'failed__' . $data['id'] . '.job.php';
        Storage::put($file, static::guard(serialize($data)), LOCK_EX);
    }
}
