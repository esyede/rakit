<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Hook;
use System\Carbon;
use System\Config;

class Redis extends Driver
{
    /**
     * Container the Redis instance.
     *
     * @var System\Redis
     */
    protected $redis;

    /**
     * Prefix key for redis storage.
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
     * @param System\Redis $redis
     * @param string|null  $key
     */
    public function __construct($redis, $key = null)
    {
        $this->redis = $redis;
        $this->key = $key ?: Config::get('job.key', 'rakit.job').':';
    }

    /**
     * Read a hash back as an associative array.
     *
     * @param string $key
     *
     * @return array
     */
    protected function hash($key)
    {
        /** @disregard */
        $reply = $this->redis->hgetall($key);

        if (! is_array($reply) || 0 === count($reply)) {
            return [];
        }

        if (array_keys($reply) !== range(0, count($reply) - 1)) {
            return $reply;
        }

        $result = [];
        $count = count($reply);

        for ($i = 0; $i + 1 < $count; $i += 2) {
            $result[$reply[$i]] = $reply[$i + 1];
        }

        return $result;
    }

    /**
     * Add a new job to the redis queue.
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
        $id = Str::ulid();
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $data = [
            'id' => $id,
            'name' => $name,
            'queue' => $queue,
            'without_overlapping' => $without_overlapping ? '1' : '0',
            'payloads' => serialize($payloads),
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        /** @disregard */
        $this->redis->hmset($this->key.'job_'.$name.'_'.$id, $data);
        $timestamp = Carbon::parse($scheduled_at)->timestamp;
        /** @disregard */
        $this->redis->zadd($this->key.'queue_'.$queue.':'.$name, $timestamp, $id);
        $this->log(sprintf('Job added: %s - #%s (queue: %s)', $name, $id, $queue));

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
        $list = $this->key.'queue_'.$queue.':'.$name;

        /** @disregard */
        if (! $this->redis->exists($list)) {
            return false;
        }

        /** @disregard */
        $ids = $this->redis->zrange($list, 0, -1);

        if (! empty($ids)) {
            foreach ($ids as $id) {
                $data = $this->hash($this->key.'job_'.$name.'_'.$id);

                if (
                    ! empty($data)
                    && isset($data['without_overlapping'])
                    && $data['without_overlapping'] === '1'
                ) {
                    if (isset($data['queue']) && $data['queue'] === $queue) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Delete job from redis queue.
     *
     * @param string      $name
     * @param string|null $queue
     *
     * @return bool
     */
    public function forget($name, $queue = null)
    {
        $name = Str::slug($name);

        if ($queue) {
            /** @disregard */
            $ids = $this->redis->zrange($this->key.'queue_'.$queue.':'.$name, 0, -1);

            if (! empty($ids)) {
                foreach ($ids as $id) {
                    /** @disregard */
                    $this->redis->del($this->key.'job_'.$name.'_'.$id);
                }
            }

            /** @disregard */
            $this->redis->del($this->key.'queue_'.$queue.':'.$name);
        } else {
            /** @disregard */
            $queues = $this->redis->keys($this->key.'queue_*:'.$name);

            if (! empty($queues)) {
                foreach ($queues as $queue) {
                    /** @disregard */
                    $ids = $this->redis->zrange($queue, 0, -1);

                    if (! empty($ids)) {
                        foreach ($ids as $id) {
                            /** @disregard */
                            $this->redis->del($this->key.'job_'.$name.'_'.$id);
                        }
                    }

                    /** @disregard */
                    $this->redis->del($queue);
                }
            }
        }

        $this->log(sprintf('Jobs deleted: %s (queue: %s)', $name, $queue ?: 'all'));
        return true;
    }

    /**
     * Run a specific job from redis.
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
        $lists = $queue ? [$this->key.'queue_'.$queue.':'.$name] : $this->redis->keys($this->key.'queue_*:'.$name);

        if (empty($lists)) {
            $this->log('Job queue does not exist');
            return false;
        }

        $jobs = [];

        foreach ($lists as $list) {
            /** @disregard */
            if (! $this->redis->exists($list)) {
                continue;
            }

            /** @disregard */
            foreach ((array) $this->redis->zrangebyscore($list, '-inf', $now) as $jid) {
                $jobs[] = ['list' => $list, 'jid' => $jid, 'key' => $this->key.'job_'.$name.'_'.$jid];
            }
        }

        list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
        $this->work($jobs, $retries, $sleep_ms);

        return true;
    }

    /**
     * Run all available jobs from redis.
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
        $all = [];

        if ($queues && is_array($queues) && ! empty($queues)) {
            foreach ($queues as $queue) {
                /** @disregard */
                $lists = $this->redis->keys($this->key.'queue_'.$queue.':*');
                $all = array_merge($all, (array) $lists);
            }
        } else {
            /** @disregard */
            $all = $this->redis->keys($this->key.'queue_*');
        }

        if (empty($all)) {
            $this->log('No job queues found');
            return false;
        }

        $jobs = [];

        foreach ($all as $list) {
            $parts = explode(':', str_replace($this->key.'queue_', '', $list));
            $default = isset($parts[0]) ? $parts[0] : 'default';
            $name = isset($parts[1]) ? $parts[1] : $default;

            /** @disregard */
            foreach ((array) $this->redis->zrangebyscore($list, '-inf', $now) as $jid) {
                $jobs[] = ['list' => $list, 'jid' => $jid, 'key' => $this->key.'job_'.$name.'_'.$jid];
            }
        }

        list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
        $this->work($jobs, $retries, $sleep_ms);

        return true;
    }

    /**
     * Claim a job with SET NX: only one worker creates the claim key, and it expires,
     * so a job whose worker died runs again.
     *
     * @param array $job
     *
     * @return array|bool
     */
    protected function claim($job)
    {
        $claim = $this->key.'claim:'.$job['jid'];

        /** @disregard */
        $reply = $this->redis->set($claim, '1', 'EX', $this->retry_after(), 'NX');

        if ('OK' !== $reply && true !== $reply) {
            return false;
        }

        $data = $this->hash($job['key']);

        if (empty($data)) {
            /** @disregard */
            $this->redis->del($claim);
            return false;
        }

        $this->claims[$job['jid']] = $job + ['claim' => $claim];
        $data['id'] = isset($data['id']) ? $data['id'] : $job['jid'];

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

        /** @disregard */
        $this->redis->zrem($job['list'], $job['jid']);
        /** @disregard */
        $this->redis->del($job['key']);
        /** @disregard */
        $this->redis->del($job['claim']);
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
     * Move failed job to failed jobs list.
     *
     * @param array      $data
     * @param \Throwable $exception
     */
    protected function move_to_failed($data, $exception)
    {
        $error = get_class($exception)
            .(('' === $exception->getMessage()) ? '' : ': '.$exception->getMessage())
            .' in '.$exception->getFile().':'.$exception->getLine();
        $data = array_merge($data, [
            'exception' => $error,
            'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        /** @disregard */
        $this->redis->hmset($this->key.'failed:'.$data['id'], $data);
        /** @disregard */
        $this->redis->lpush($this->key.'failed_jobs', $data['id']);
    }
}
