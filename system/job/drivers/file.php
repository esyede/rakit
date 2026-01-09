<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Event;
use System\Carbon;
use System\Storage;

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
     *
     * @return bool
     */
    public function add($name, array $payloads = [], $scheduled_at = null)
    {
        $name = Str::slug($name);
        $id = Str::nanoid();
        $data = [
            'id' => $id,
            'name' => $name,
            'payloads' => serialize($payloads),
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $filename = $this->path . $name . '__' . $id . '.job.php';
        Storage::put($filename, static::guard(serialize($data)), LOCK_EX);

        $this->log(sprintf('Job added: %s - #%s', $name, $id));
        return true;
    }

    /**
     * Hapus job berdasarkan nama.
     *
     * @param string $name
     *
     * @return bool
     */
    public function forget($name)
    {
        $name = Str::slug($name);
        $pattern = $this->path . $name . '__*' . '.job.php';
        $files = glob($pattern);

        foreach ($files as $file) {
            Storage::delete($file);
        }

        $this->log(sprintf('Jobs deleted: %s', $name));
        return true;
    }

    /**
     * Jalankan antrian job di file.
     *
     * @param string $name
     * @param int    $retries
     * @param int    $sleep_ms
     *
     * @return bool
     */
    public function run($name, $retries = 1, $sleep_ms = 0)
    {
        $name = Str::slug($name);
        $files = glob($this->path . $name . '__*' . '.job.php');

        if (empty($files)) {
            $this->log('No job files found');
            return false;
        }

        $retries = (int) (($retries > 1) ? $retries : 1);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : 0);
        $successful = [];

        foreach ($files as $file) {
            $data = unserialize(static::unguard(Storage::get($file)));

            if (Carbon::parse($data['scheduled_at'])->lte(Carbon::now())) {
                $attempts = 0;
                $success = false;

                while ($attempts < $retries && !$success) {
                    $attempts++;

                    try {
                        Event::fire('rakit.jobs.run: ' . $data['name'], unserialize($data['payloads']));
                        $successful[] = $file;
                        $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));
                        $success = true;
                    } catch (\Throwable $e) {
                        if ($attempts >= $retries) {
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($attempts >= $retries) {
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

        foreach ($successful as $file) {
            Storage::delete($file);
        }

        return true;
    }

    /**
     * Jalankan semua job di file.
     *
     * @param int $retries
     * @param int $sleep_ms
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0)
    {
        $files = glob($this->path . '*.job.php');

        if (empty($files)) {
            $this->log('No job files found');
            return false;
        }

        $retries = (int) (($retries > 1) ? $retries : 1);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : 0);
        $successful = [];

        foreach ($files as $file) {
            $data = unserialize(static::unguard(Storage::get($file)));

            if (Carbon::parse($data['scheduled_at'])->lte(Carbon::now())) {
                $attempts = 0;
                $success = false;

                while ($attempts < $retries && !$success) {
                    $attempts++;
                    try {
                        Event::fire('rakit.jobs.run: ' . $data['name'], unserialize($data['payloads']));
                        $successful[] = $file;
                        $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));
                        $success = true;
                    } catch (\Throwable $e) {
                        if ($attempts >= $retries) {
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($attempts >= $retries) {
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

        foreach ($successful as $file) {
            Storage::delete($file);
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
}
