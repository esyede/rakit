<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Hook;
use System\Str;

class Memcached extends Driver
{
    /**
     * Contains the Memcached instance.
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Prefix key for job storage.
     *
     * @var string
     */
    protected $key = 'rakit.job:';

    /**
     * The jobs this worker holds a claim on, by ID.
     *
     * @var array
     */
    protected $claims = [];

    /**
     * Constructor.
     *
     * @param \Memcached  $memcached
     * @param string|null $key
     */
    public function __construct(\Memcached $memcached, $key = null)
    {
        $this->memcached = $memcached;
        $this->key = $key ?: Config::get('job.key', 'rakit.job').':';
    }

    /**
     * Add a new job to the queue.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     * @param string      $queue
     * @param bool        $without_overlapping
     *
     * @return bool
     */
    public function add(
        $name,
        array $payloads = [],
        $scheduled_at = null,
        $queue = 'default',
        $without_overlapping = false
    ) {
        $name = Str::slug($name);
        $scheduled_at = $scheduled_at ?: Carbon::now();
        $timestamp = Carbon::parse($scheduled_at)->timestamp;
        $id = Str::ulid();
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
        $this->memcached->set($this->key.'data:'.$id, $data, 0); // 0 = never expires
        /** @disregard */
        $jobs = $this->memcached->get($this->key.'queue:'.$queue.':'.$name);
        $jobs = $jobs ?: [];
        $jobs[$timestamp.':'.$id] = $id;
        /** @disregard */
        $this->memcached->set($this->key.'queue:'.$queue.':'.$name, $jobs, 0);
        /** @disregard */
        $all = $this->memcached->get($this->key.'all_jobs');
        $all = $all ?: [];
        $all[$timestamp.':'.$id] = ['id' => $id, 'queue' => $queue, 'name' => $name];
        /** @disregard */
        $this->memcached->set($this->key.'all_jobs', $all, 0);
        $this->log(sprintf('Job added: %s - %s (queue: %s)', $name, $id, $queue));

        return true;
    }

    /**
     * Check if there is an overlapping job.
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
        $jobs = $this->memcached->get($this->key.'queue:'.$queue.':'.$name);
        $jobs = $jobs ?: [];

        foreach ($jobs as $id) {
            /** @disregard */
            $data = $this->memcached->get($this->key.'data:'.$id);

            if ($data && isset($data['without_overlapping']) && (int) $data['without_overlapping'] === 1) {
                if (isset($data['queue']) && $data['queue'] === $queue) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Delete a job from the queue.
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
            $jobs = $this->memcached->get($this->key.'queue:'.$queue.':'.$name);
            $jobs = $jobs ?: [];

            foreach ($jobs as $id) {
                /** @disregard */
                $this->memcached->delete($this->key.'data:'.$id);
                $deleted++;
            }

            /** @disregard */
            $this->memcached->delete($this->key.'queue:'.$queue.':'.$name);
        } else {
            /** @disregard */
            $all = $this->memcached->get($this->key.'all_jobs');
            $all = $all ?: [];
            $items = [];

            foreach ($all as $key => $job) {
                if ($job['name'] === $name) {
                    /** @disregard */
                    $this->memcached->delete($this->key.'data:'.$job['id']);
                    /** @disregard */
                    $jobs = $this->memcached->get($this->key.'queue:'.$job['queue'].':'.$name);
                    $jobs = $jobs ?: [];

                    if (isset($jobs[$key])) {
                        unset($jobs[$key]);
                    }

                    /** @disregard */
                    $this->memcached->set($this->key.'queue:'.$job['queue'].':'.$name, $jobs, 0);
                    $deleted++;
                } else {
                    $items[$key] = $job;
                }
            }

            /** @disregard */
            $this->memcached->set($this->key.'all_jobs', $items, 0);
        }

        $this->log(sprintf('Jobs forgotten: %s (queue: %s, %d jobs deleted)', $name, $queue ?: 'all', $deleted));
        return true;
    }

    /**
     * Run a specific job.
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
        $name = Str::slug($name);
        $this->processed = 0;
        $now = Carbon::now()->timestamp;
        $ready = [];

        if ($queue) {
            /** @disregard */
            $jobs = $this->memcached->get($this->key.'queue:'.$queue.':'.$name);
            $jobs = $jobs ?: [];

            if (! empty($jobs)) {
                foreach ($jobs as $key => $id) {
                    $timestamp = explode(':', $key)[0];

                    if ((int) $timestamp <= $now) {
                        $ready[$key] = $id;
                    }
                }
            }
        } else {
            /** @disregard */
            $all = $this->memcached->get($this->key.'all_jobs');
            $all = $all ?: [];

            if (! empty($all)) {
                foreach ($all as $key => $job) {
                    if ($job['name'] === $name) {
                        $timestamp = explode(':', $key)[0];

                        if ((int) $timestamp <= $now) {
                            $ready[$key] = $job['id'];
                        }
                    }
                }
            }
        }

        if (empty($ready)) {
            $this->log('No jobs ready to run for: '.$name);
            return true;
        }

        ksort($ready);

        $jobs = [];

        foreach (array_slice($ready, 0, Config::get('job.max_job'), true) as $key => $id) {
            $jobs[] = ['key' => $key, 'id' => $id];
        }

        list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
        $this->work($jobs, $retries, $sleep_ms);

        return true;
    }

    /**
     * Run all available jobs.
     *
     * @param int        $retries
     * @param int        $sleep_ms
     * @param array|null $queues
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0, $queues = null)
    {
        $this->processed = 0;
        $now = Carbon::now()->timestamp;
        /** @disregard */
        $all = $this->memcached->get($this->key.'all_jobs');
        $all = $all ?: [];
        $ready = [];

        if (! empty($all)) {
            foreach ($all as $key => $job) {
                $timestamp = (int) explode(':', $key)[0];

                if ($timestamp <= $now) {
                    if (is_array($queues) && ! empty($queues)) {
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

        $jobs = [];

        foreach (array_slice($ready, 0, Config::get('job.max_job'), true) as $key => $id) {
            $jobs[] = ['key' => $key, 'id' => $id];
        }

        list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
        $this->work($jobs, $retries, $sleep_ms);

        return true;
    }

    /**
     * Claim a job with add(), which only one worker can do while the claim lives; it
     * expires, so a job whose worker died runs again.
     *
     * @param array $job
     *
     * @return array|bool
     */
    protected function claim($job)
    {
        $claim = $this->key.'claim:'.$job['id'];

        /** @disregard */
        if (! $this->memcached->add($claim, 1, $this->retry_after())) {
            return false;
        }

        /** @disregard */
        $data = $this->memcached->get($this->key.'data:'.$job['id']);

        if (! $data) {
            /** @disregard */
            $this->memcached->delete($claim);
            return false;
        }

        $this->claims[$job['id']] = [
            'key' => $job['key'],
            'id' => $job['id'],
            'queue' => $data['queue'],
            'name' => $data['name'],
            'claim' => $claim,
        ];
        $data['id'] = isset($data['id']) ? $data['id'] : $job['id'];

        return $data;
    }

    /**
     * Remove a job that succeeded, with its claim.
     *
     * @param array $data
     */
    protected function complete($data)
    {
        $job = $this->claims[$data['id']];
        unset($this->claims[$data['id']]);

        $this->cleanup_successful([$job]);
        /** @disregard */
        $this->memcached->delete($job['claim']);
    }

    /**
     * Store a job that ran out of attempts as failed, and remove it.
     *
     * @param array      $data
     * @param \Throwable $e
     */
    protected function bury($data, $e)
    {
        $this->move_to_failed($data, $e);
        $this->complete($data);
    }

    /**
     * Cleanup successful jobs.
     *
     * @param array $successful
     */
    protected function cleanup_successful($successful)
    {
        /** @disregard */
        $all = $this->memcached->get($this->key.'all_jobs');
        $all = $all ?: [];

        foreach ($successful as $job) {
            /** @disregard */
            $this->memcached->delete($this->key.'data:'.$job['id']);

            if (isset($all[$job['key']])) {
                unset($all[$job['key']]);
            }

            /** @disregard */
            $jobs = $this->memcached->get($this->key.'queue:'.$job['queue'].':'.$job['name']);
            $jobs = $jobs ?: [];

            if (isset($jobs[$job['key']])) {
                unset($jobs[$job['key']]);
            }

            /** @disregard */
            $this->memcached->set($this->key.'queue:'.$job['queue'].':'.$job['name'], $jobs, 0);
        }

        /** @disregard */
        $this->memcached->set($this->key.'all_jobs', $all, 0);
    }

    /**
     * Move the failed job to the failed jobs storage.
     *
     * @param array      $data
     * @param \Throwable $exception
     */
    protected function move_to_failed($data, $exception)
    {
        $data['failed_at'] = Carbon::now()->timestamp;
        $data['exception'] = $exception->getMessage();
        /** @disregard */
        $this->memcached->set($this->key.'failed:'.$data['id'], $data, 0);
        /** @disregard */
        $fails = $this->memcached->get($this->key.'failed_jobs');
        $fails = $fails ?: [];
        $fails[] = $data['id'];
        /** @disregard */
        $this->memcached->set($this->key.'failed_jobs', $fails, 0);
    }
}
