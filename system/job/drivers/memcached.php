<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Event;
use System\Str;
use System\Memcached as BaseMemcached;

class Memcached extends Driver
{
    /**
     * Instance Memcached.
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Prefix key untuk job.
     *
     * @var string
     */
    protected $key = 'rakit.job:';

    /**
     * Constructor.
     *
     * @param \Memcached|null $memcached
     * @param string|null     $key
     */
    public function __construct(BaseMemcached $memcached, $key = null)
    {
        $this->memcached = $memcached;
        $this->key = $key ?: Config::get('job.key') . ':';
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
        $scheduled_at = $scheduled_at ?: Carbon::now();
        $timestamp = Carbon::parse($scheduled_at)->timestamp;
        $id = Str::nanoid();
        $data = [
            'id' => $id,
            'name' => $name,
            'payloads' => serialize($payloads),
            'scheduled_at' => $timestamp,
            'created_at' => Carbon::now()->timestamp,
            'attempts' => 0,
        ];

        $key = $this->key . 'data:' . $id;
        /** @disregard */
        $this->memcached->set($key, $data, 0); // 0 = tidak pernah expired

        $queue = $this->key . 'queue:' . $name;
        /** @disregard */
        $this->memcached->add($queue, [$timestamp => $id], 0);

        $keys = $this->key . 'all_jobs';
        /** @disregard */
        $all = $this->memcached->get($keys) ?: [];
        $all[$timestamp] = $id;
        /** @disregard */
        $this->memcached->set($keys, $all, 0);

        $this->log(sprintf('Job added: %s - %s', $name, $id));

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
        $queue = $this->key . 'queue:' . $name;
        /** @disregard */
        $jobs = $this->memcached->get($queue) ?: [];

        foreach ($jobs as $timestamp => $id) {
            $key = $this->key . 'data:' . $id;
            /** @disregard */
            $this->memcached->delete($key);
        }

        /** @disregard */
        $this->memcached->delete($queue);
        $this->log(sprintf('Jobs forgotten: %s (%d jobs)', $name, count($jobs)));

        $keys = $this->key . 'all_jobs';
        /** @disregard */
        $all = $this->memcached->get($keys) ?: [];

        foreach ($jobs as $timestamp => $id) {
            unset($all[$timestamp]);
        }

        /** @disregard */
        $this->memcached->set($keys, $all, 0);
        return true;
    }

    /**
     * Jalankan antrian job.
     *
     * @param string $name
     * @param int    $retries
     * @param int    $sleep_ms
     *
     * @return bool
     */
    public function run($name, $retries = 1, $sleep_ms = 0)
    {
        $config = Config::get('job');
        $name = Str::slug($name);
        $queue = $this->key . 'queue:' . $name;
        $now = Carbon::now()->timestamp;
        /** @disregard */
        $jobs = $this->memcached->get($queue) ?: [];
        $ready = [];

        foreach ($jobs as $timestamp => $id) {
            if ($timestamp <= $now) {
                $ready[$timestamp] = $id;
            }
        }

        if (empty($ready)) {
            $this->log('No jobs ready to run for: ' . $name);
            return true;
        }

        // Sort berdasarkan timestamp (FIFO)
        ksort($ready);

        $ready = array_slice($ready, 0, $config['max_job'], true);
        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $successful = [];

        foreach ($ready as $timestamp => $id) {
            $key = $this->key . 'data:' . $id;
            /** @disregard */
            $data = $this->memcached->get($key);

            if (!$data) {
                continue; // Job sudah dihapus
            }

            $attempts = 0;
            $success = false;

            while ($attempts < $retries && !$success) {
                $attempts++;

                try {
                    Event::fire('rakit.jobs.run: ' . $data['name'], unserialize($data['payloads']));
                    $successful[] = ['timestamp' => $timestamp, 'id' => $id];
                    $this->log(sprintf('Job executed: %s - %s (attempt %d)', $data['name'], $id, $attempts));
                    $success = true;
                } catch (\Throwable $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data);
                        $successful[] = ['timestamp' => $timestamp, 'id' => $id]; // Mark for deletion even if failed
                        $this->log(sprintf('Job failed permanently: %s - %s ::: %s (after %d attempts)', $data['name'], $id, $e->getMessage(), $attempts), 'error');
                    } else {
                        $this->log(sprintf('Job retry: %s - %s (attempt %d)', $data['name'], $id, $attempts));

                        if ($sleep_ms > 0) {
                            usleep($sleep_ms * 1000);
                        }
                    }
                } catch (\Exception $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data);
                        $successful[] = ['timestamp' => $timestamp, 'id' => $id]; // Mark for deletion even if failed
                        $this->log(sprintf('Job failed permanently: %s - %s ::: %s (after %d attempts)', $data['name'], $id, $e->getMessage(), $attempts), 'error');
                    } else {
                        $this->log(sprintf('Job retry: %s - %s (attempt %d)', $data['name'], $id, $attempts));

                        if ($sleep_ms > 0) {
                            usleep($sleep_ms * 1000);
                        }
                    }
                }
            }
        }

        foreach ($successful as $job) {
            $key = $this->key . 'data:' . $job['id'];
            /** @disregard */
            $this->memcached->delete($key);
            unset($jobs[$job['timestamp']]);
        }

        /** @disregard */
        $this->memcached->set($queue, $jobs, 0);

        $keys = $this->key . 'all_jobs';
        /** @disregard */
        $all = $this->memcached->get($keys) ?: [];

        foreach ($successful as $job) {
            unset($all[$job['timestamp']]);
        }

        /** @disregard */
        $this->memcached->set($keys, $all, 0);

        return true;
    }

    /**
     * Jalankan semua job.
     *
     * @param int $retries
     * @param int $sleep_ms
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0)
    {
        $config = Config::get('job');
        $now = Carbon::now()->timestamp;
        $keys = $this->key . 'all_jobs';
        /** @disregard */
        $all = $this->memcached->get($keys) ?: [];
        $ready = [];

        foreach ($all as $timestamp => $id) {
            if ($timestamp <= $now) {
                $ready[$timestamp] = $id;
            }
        }

        if (empty($ready)) {
            $this->log('No jobs ready to run');
            return true;
        }

        // Sort berdasarkan timestamp (FIFO)
        ksort($ready);

        $ready = array_slice($ready, 0, $config['max_job'], true);
        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $successful = [];

        foreach ($ready as $timestamp => $id) {
            $key = $this->key . 'data:' . $id;
            /** @disregard */
            $data = $this->memcached->get($key);

            if (!$data) {
                continue; // Job sudah dihapus
            }

            $attempts = 0;
            $success = false;

            while ($attempts < $retries && !$success) {
                $attempts++;

                try {
                    Event::fire('rakit.jobs.run: ' . $data['name'], unserialize($data['payloads']));
                    $successful[] = ['timestamp' => $timestamp, 'id' => $id, 'name' => $data['name']];
                    $this->log(sprintf('Job executed: %s - %s (attempt %d)', $data['name'], $id, $attempts));
                    $success = true;
                } catch (\Throwable $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data);
                        $successful[] = ['timestamp' => $timestamp, 'id' => $id];
                        $this->log(sprintf('Job failed permanently: %s - %s ::: %s (after %d attempts)', $data['name'], $id, $e->getMessage(), $attempts), 'error');
                    } else {
                        $this->log(sprintf('Job retry: %s - %s (attempt %d)', $data['name'], $id, $attempts));

                        if ($sleep_ms > 0) {
                            usleep($sleep_ms * 1000);
                        }
                    }
                } catch (\Exception $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data);
                        $successful[] = ['timestamp' => $timestamp, 'id' => $id];
                        $this->log(sprintf('Job failed permanently: %s - %s ::: %s (after %d attempts)', $data['name'], $id, $e->getMessage(), $attempts), 'error');
                    } else {
                        $this->log(sprintf('Job retry: %s - %s (attempt %d)', $data['name'], $id, $attempts));

                        if ($sleep_ms > 0) {
                            usleep($sleep_ms * 1000);
                        }
                    }
                }
            }
        }

        foreach ($successful as $job) {
            $key = $this->key . 'data:' . $job['id'];
            /** @disregard */
            $this->memcached->delete($key);
            unset($all[$job['timestamp']]);
        }

        /** @disregard */
        $this->memcached->set($keys, $all, 0);

        foreach ($successful as $job) {
            $key = $this->key . 'queue:' . $job['name'];
            /** @disregard */
            $queues = $this->memcached->get($key) ?: [];
            unset($queues[$job['timestamp']]);
            /** @disregard */
            $this->memcached->set($key, $queues, 0);
        }

        return true;
    }

    /**
     * Pindahkan job ke failed jobs.
     *
     * @param array $data
     */
    protected function move_to_failed($data)
    {
        $key = $this->key . 'failed:' . $data['id'];
        $data['failed_at'] = Carbon::now()->timestamp;
        /** @disregard */
        $this->memcached->set($key, $data, 0);

        $list = $this->key . 'failed_jobs';
        /** @disregard */
        $fails = $this->memcached->get($list) ?: [];
        $fails[] = $data['id'];
        /** @disregard */
        $this->memcached->set($list, $fails, 0);
    }
}
