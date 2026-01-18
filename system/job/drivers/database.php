<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Database as DB;
use System\Event;
use System\Str;

class Database extends Driver
{
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
        $config = Config::get('job');
        $name = Str::slug($name);
        $id = DB::table($config['table'])->insert_get_id([
            'name' => $name,
            'payloads' => serialize($payloads),
            'queue' => $queue,
            'without_overlapping' => $without_overlapping ? 1 : 0,
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

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
        $count = DB::table(Config::get('job.table'))
            ->where('name', Str::slug($name))
            ->where('queue', $queue)
            ->where('without_overlapping', 1)
            ->count();

        return $count > 0;
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
        $config = Config::get('job');
        $name = Str::slug($name);

        Event::fire('rakit.jobs.forget: ' . $name);

        $query = DB::table($config['table'])->where('name', $name);

        if ($queue) {
            $query->where('queue', $queue);
        }

        $jobs = $query->get();

        if (!empty($jobs)) {
            $ids = [];

            foreach ($jobs as $job) {
                $ids[] = $job->id;
            }

            DB::table($config['table'])->where_in('id', $ids)->delete();
        }

        $fails = DB::table($config['failed_table'])->where('name', $name);

        if ($queue) {
            $fails->where('queue', $queue);
        }

        $jobs = $fails->get();

        if (!empty($jobs)) {
            $ids = [];

            foreach ($jobs as $job) {
                $ids[] = $job->id;
            }

            DB::table($config['failed_table'])->where_in('id', $ids)->delete();
        }

        $this->log(sprintf('Jobs deleted: %s (queue: %s)', $name, $queue ?: 'all'));

        return true;
    }

    /**
     * Jalankan antrian job di database.
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

        if (empty($name)) {
            $this->log('Job is empty');
            return false;
        }

        $query = DB::table($config['table'])
            ->where('name', $name)
            ->where('scheduled_at', '<=', Carbon::now()->format('Y-m-d H:i:s'));

        if ($queue) {
            $query->where('queue', $queue);
        }

        $jobs = $query->order_by('created_at', 'ASC')
            ->take($config['max_job'])
            ->get();

        if (empty($jobs)) {
            $this->log('Job is empty');
        } else {
            $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
            $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
            $successful = [];

            foreach ($jobs as $job) {
                $attempts = 0;
                $success = false;

                while ($attempts < $retries && !$success) {
                    $attempts++;

                    try {
                        Event::fire('rakit.jobs.process', [$job]);
                        $successful[] = $job->id;
                        $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $job->name, $job->id, $attempts));
                        $success = true;
                    } catch (\Throwable $e) {
                        if ($attempts >= $retries) {
                            $error = get_class($e)
                                . (('' === $e->getMessage()) ? '' : ': ' . $e->getMessage())
                                . ' in ' . $e->getFile() . ':' . $e->getLine() . "\nStack trace:\n"
                                . $e->getTraceAsString();
                            DB::table($config['failed_table'])->insert([
                                'job_id' => $job->id,
                                'name' => $job->name,
                                'queue' => $job->queue,
                                'payloads' => $job->payloads,
                                'exception' => $error,
                                'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $job->name, $job->id, $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $job->name, $job->id, $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($attempts >= $retries) {
                            $error = get_class($e)
                                . (('' === $e->getMessage()) ? '' : ': ' . $e->getMessage())
                                . ' in ' . $e->getFile() . ':' . $e->getLine() . "\nStack trace:\n"
                                . $e->getTraceAsString();
                            DB::table($config['failed_table'])->insert([
                                'job_id' => $job->id,
                                'name' => $job->name,
                                'queue' => $job->queue,
                                'payloads' => $job->payloads,
                                'exception' => $error,
                                'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $job->name, $job->id, $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $job->name, $job->id, $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    }
                }
            }

            if (!empty($successful)) {
                DB::table($config['table'])->where_in('id', $successful)->delete();
            }
        }
    }

    /**
     * Jalankan semua job di database.
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
        $query = DB::table($config['table'])
            ->where('scheduled_at', '<=', Carbon::now()->format('Y-m-d H:i:s'));

        if (is_array($queues) && !empty($queues)) {
            $query->where_in('queue', $queues);
        }

        $jobs = $query->order_by('created_at', 'ASC')
            ->take($config['max_job'])
            ->get();

        if (empty($jobs)) {
            $this->log('Job is empty');
        } else {
            $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
            $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
            $successful = [];

            foreach ($jobs as $job) {
                $attempts = 0;
                $success = false;

                while ($attempts < $retries && !$success) {
                    $attempts++;

                    try {
                        Event::fire('rakit.jobs.process', [$job]);
                        $successful[] = $job->id;
                        $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $job->name, $job->id, $attempts));
                        $success = true;
                    } catch (\Throwable $e) {
                        if ($attempts >= $retries) {
                            $error = get_class($e)
                                . (('' === $e->getMessage()) ? '' : ': ' . $e->getMessage())
                                . ' in ' . $e->getFile() . ':' . $e->getLine() . "\nStack trace:\n"
                                . $e->getTraceAsString();
                            DB::table($config['failed_table'])->insert([
                                'job_id' => $job->id,
                                'name' => $job->name,
                                'queue' => $job->queue,
                                'payloads' => $job->payloads,
                                'exception' => $error,
                                'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $job->name, $job->id, $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $job->name, $job->id, $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    } catch (\Exception $e) {
                        if ($attempts >= $retries) {
                            $error = get_class($e)
                                . (('' === $e->getMessage()) ? '' : ': ' . $e->getMessage())
                                . ' in ' . $e->getFile() . ':' . $e->getLine() . "\nStack trace:\n"
                                . $e->getTraceAsString();
                            DB::table($config['failed_table'])->insert([
                                'job_id' => $job->id,
                                'name' => $job->name,
                                'queue' => $job->queue,
                                'payloads' => $job->payloads,
                                'exception' => $error,
                                'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                            $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $job->name, $job->id, $e->getMessage(), $attempts), 'error');
                        } else {
                            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $job->name, $job->id, $attempts));

                            if ($sleep_ms > 0) {
                                usleep($sleep_ms * 1000);
                            }
                        }
                    }
                }
            }

            if (!empty($successful)) {
                DB::table($config['table'])->where_in('id', $successful)->delete();
            }
        }
    }
}
