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
     * @var \System\Memcached
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
     * @param \System\Memcached |null $memcached
     * @param string|null             $key
     */
    public function __construct(BaseMemcached $memcached, $key = null)
    {
        $this->memcached = $memcached;
        $this->key = $key ?: Config::get('job.key', 'rakit.job') . ':';
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
        $scheduled_at = $scheduled_at ?: Carbon::now();
        $timestamp = Carbon::parse($scheduled_at)->timestamp;
        $id = Str::nanoid();
        $data = [
            'id' => $id,
            'name' => $name,
            'queue' => $queue,
            'without_overlapping' => $without_overlapping ? 1 : 0,
            'payloads' => serialize($payloads),
            'scheduled_at' => $timestamp,
            'created_at' => Carbon::now()->timestamp,
            'attempts' => 0,
        ];

        /** @disregard */
        $this->memcached->set($this->key . 'data:' . $id, $data, 0); // 0 = tidak pernah expired
        /** @disregard */
        $jobs = $this->memcached->get($this->key . 'queue:' . $queue . ':' . $name);
        $jobs = $jobs ?: [];
        $jobs[$timestamp . ':' . $id] = $id;
        /** @disregard */
        $this->memcached->set($this->key . 'queue:' . $queue . ':' . $name, $jobs, 0);
        /** @disregard */
        $all = $this->memcached->get($this->key . 'all_jobs');
        $all = $all ?: [];
        $all[$timestamp . ':' . $id] = ['id' => $id, 'queue' => $queue, 'name' => $name];
        /** @disregard */
        $this->memcached->set($this->key . 'all_jobs', $all, 0);
        $this->log(sprintf('Job added: %s - %s (queue: %s)', $name, $id, $queue));

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
        /** @disregard */
        $jobs = $this->memcached->get($this->key . 'queue:' . $queue . ':' . $name);
        $jobs = $jobs ?: [];

        foreach ($jobs as $id) {
            /** @disregard */
            $data = $this->memcached->get($this->key . 'data:' . $id);

            if ($data && isset($data['without_overlapping']) && (int) $data['without_overlapping'] === 1) {
                if (isset($data['queue']) && $data['queue'] === $queue) {
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
        $deleted = 0;

        if ($queue) {
            /** @disregard */
            $jobs = $this->memcached->get($this->key . 'queue:' . $queue . ':' . $name);
            $jobs = $jobs ?: [];

            foreach ($jobs as $id) {
                /** @disregard */
                $this->memcached->delete($this->key . 'data:' . $id);
                $deleted++;
            }

            /** @disregard */
            $this->memcached->delete($this->key . 'queue:' . $queue . ':' . $name);
        } else {
            /** @disregard */
            $all = $this->memcached->get($this->key . 'all_jobs');
            $all = $all ?: [];
            $items = [];

            foreach ($all as $key => $job) {
                if ($job['name'] === $name) {
                    /** @disregard */
                    $this->memcached->delete($this->key . 'data:' . $job['id']);
                    /** @disregard */
                    $jobs = $this->memcached->get($this->key . 'queue:' . $job['queue'] . ':' . $name);
                    $jobs = $jobs ?: [];

                    if (isset($jobs[$key])) {
                        unset($jobs[$key]);
                    }

                    /** @disregard */
                    $this->memcached->set($this->key . 'queue:' . $job['queue'] . ':' . $name, $jobs, 0);
                    $deleted++;
                } else {
                    $items[$key] = $job;
                }
            }

            /** @disregard */
            $this->memcached->set($this->key . 'all_jobs', $items, 0);
        }

        $this->log(sprintf('Jobs forgotten: %s (queue: %s, %d jobs deleted)', $name, $queue ?: 'all', $deleted));
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
        $now = Carbon::now()->timestamp;
        $ready = [];

        if ($queue) {
            /** @disregard */
            $jobs = $this->memcached->get( $this->key . 'queue:' . $queue . ':' . $name);
            $jobs = $jobs ?: [];

            if (!empty($jobs)) {
                foreach ($jobs as $key => $id) {
                    $timestamp = explode(':', $key)[0];

                    if (intval($timestamp) <= $now) {
                        $ready[$key] = $id;
                    }
                }
            }
        } else {
            /** @disregard */
            $all = $this->memcached->get($this->key . 'all_jobs');
            $all = $all ?: [];

            if (!empty($all)) {
                foreach ($all as $key => $job) {
                    if ($job['name'] === $name) {
                        $timestamp = explode(':', $key)[0];

                        if (intval($timestamp) <= $now) {
                            $ready[$key] = $job['id'];
                        }
                    }
                }
            }
        }

        if (empty($ready)) {
            $this->log('No jobs ready to run for: ' . $name);
            return true;
        }

        ksort($ready);

        $ready = array_slice($ready, 0, $config['max_job'], true);
        $retries = intval(($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = intval(($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $successful = [];

        foreach ($ready as $key => $id) {
            /** @disregard */
            $data = $this->memcached->get($this->key . 'data:' . $id);

            if (!$data) {
                continue;
            }

            $attempts = 0;
            $success = false;

            while ($attempts < $retries && !$success) {
                $attempts++;

                try {
                    Event::fire('rakit.jobs.process', [$data]);
                    $successful[] = ['key' => $key, 'id' => $id, 'queue' => $data['queue'], 'name' => $data['name']];
                    $this->log(sprintf('Job executed: %s - %s (attempt %d)', $data['name'], $id, $attempts));
                    $success = true;
                } catch (\Throwable $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data, $e);
                        $successful[] = ['key' => $key, 'id' => $id, 'queue' => $data['queue'], 'name' => $data['name']];
                        $this->log(sprintf('Job failed permanently: %s - %s ::: %s (after %d attempts)', $data['name'], $id, $e->getMessage(), $attempts), 'error');
                    } else {
                        $this->log(sprintf('Job retry: %s - %s (attempt %d)', $data['name'], $id, $attempts));

                        if ($sleep_ms > 0) {
                            usleep($sleep_ms * 1000);
                        }
                    }
                } catch (\Exception $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data, $e);
                        $successful[] = ['key' => $key, 'id' => $id, 'queue' => $data['queue'], 'name' => $data['name']];
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

        $this->cleanup_successful($successful);
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
        $now = Carbon::now()->timestamp;
        /** @disregard */
        $all = $this->memcached->get($this->key . 'all_jobs');
        $all = $all ?: [];
        $ready = [];

        if (!empty($all)) {
            foreach ($all as $key => $job) {
                $timestamp = (int) explode(':', $key)[0];

                if ($timestamp <= $now) {
                    if (is_array($queues) && !empty($queues)) {
                        if (in_array($job['queue'], $queues)) {
                            $ready[$key] = $job['id'];
                        }
                    } else {
                        $ready[$key] = $job['id'];
                    }
                }
            }
        }

        if (empty($ready)) {
            $this->log('No jobs ready to run');
            return true;
        }

        ksort($ready);

        $ready = array_slice($ready, 0, $config['max_job'], true);
        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $successful = [];

        foreach ($ready as $key => $id) {
            /** @disregard */
            $data = $this->memcached->get($this->key . 'data:' . $id);

            if (!$data) {
                continue;
            }

            $attempts = 0;
            $success = false;

            while ($attempts < $retries && !$success) {
                $attempts++;

                try {
                    Event::fire('rakit.jobs.process', [$data]);
                    $successful[] = ['key' => $key, 'id' => $id, 'queue' => $data['queue'], 'name' => $data['name']];
                    $this->log(sprintf('Job executed: %s - %s (attempt %d)', $data['name'], $id, $attempts));
                    $success = true;
                } catch (\Throwable $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data, $e);
                        $successful[] = ['key' => $key, 'id' => $id, 'queue' => $data['queue'], 'name' => $data['name']];
                        $this->log(sprintf('Job failed permanently: %s - %s ::: %s (after %d attempts)', $data['name'], $id, $e->getMessage(), $attempts), 'error');
                    } else {
                        $this->log(sprintf('Job retry: %s - %s (attempt %d)', $data['name'], $id, $attempts));

                        if ($sleep_ms > 0) {
                            usleep($sleep_ms * 1000);
                        }
                    }
                } catch (\Exception $e) {
                    if ($attempts >= $retries) {
                        $this->move_to_failed($data, $e);
                        $successful[] = ['key' => $key, 'id' => $id, 'queue' => $data['queue'], 'name' => $data['name']];
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

        $this->cleanup_successful($successful);
        return true;
    }

    /**
     * Cleanup successful jobs.
     *
     * @param array $successful
     */
    protected function cleanup_successful($successful)
    {
        /** @disregard */
        $all = $this->memcached->get($this->key . 'all_jobs');
        $all = $all ?: [];

        foreach ($successful as $job) {
            /** @disregard */
            $this->memcached->delete($this->key . 'data:' . $job['id']);

            if (isset($all[$job['key']])) {
                unset($all[$job['key']]);
            }

            /** @disregard */
            $jobs = $this->memcached->get($this->key . 'queue:' . $job['queue'] . ':' . $job['name']);
            $jobs = $jobs ?: [];

            if (isset($jobs[$job['key']])) {
                unset($jobs[$job['key']]);
            }

            /** @disregard */
            $this->memcached->set($this->key . 'queue:' . $job['queue'] . ':' . $job['name'], $jobs, 0);
        }

        /** @disregard */
        $this->memcached->set($this->key . 'all_jobs', $all, 0);
    }

    /**
     * Pindahkan job ke failed jobs.
     *
     * @param array      $data
     * @param \Exception $exception
     */
    protected function move_to_failed($data, $exception)
    {
        $data['failed_at'] = Carbon::now()->timestamp;
        $data['exception'] = $exception->getMessage();
        /** @disregard */
        $this->memcached->set($this->key . 'failed:' . $data['id'], $data, 0);
        /** @disregard */
        $fails = $this->memcached->get($this->key . 'failed_jobs');
        $fails = $fails ?: [];
        $fails[] = $data['id'];
        /** @disregard */
        $this->memcached->set($this->key . 'failed_jobs', $fails, 0);
    }
}
