<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Event;
use System\Carbon;
use System\Config;

class Redis extends Driver
{
    /**
     * Berisi instance database redis.
     *
     * @var System\Redis
     */
    protected $redis;

    /**
     * Prefix key untuk redis.
     *
     * @var string
     */
    protected $key = 'rakit.job:';

    /**
     * Buat instance driver redis baru.
     *
     * @param System\Redis $redis
     * @param string|null  $key
     */
    public function __construct($redis, $key = null)
    {
        $this->redis = $redis;
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
        $this->redis->hmset($this->key . 'job_' . $name . '_' . $id, $data);
        $timestamp = Carbon::parse($scheduled_at)->timestamp;
        /** @disregard */
        $this->redis->zadd($this->key . 'queue_' . $queue . ':' . $name, $timestamp, $id);
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
        $list = $this->key . 'queue_' . $queue . ':' . $name;

        /** @disregard */
        if (!$this->redis->exists($list)) {
            return false;
        }

        /** @disregard */
        $ids = $this->redis->zrange($list, 0, -1);

        if (!empty($ids)) {
            foreach ($ids as $id) {
                /** @disregard */
                $data = $this->redis->hgetall($this->key . 'job_' . $name . '_' . $id);

                if (!empty($data) && isset($data['without_overlapping']) && $data['without_overlapping'] === '1') {
                    if (isset($data['queue']) && $data['queue'] === $queue) {
                        return true;
                    }
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

        if ($queue) {
            /** @disregard */
            $ids = $this->redis->zrange($this->key . 'queue_' . $queue . ':' . $name, 0, -1);

            if (!empty($ids)) {
                foreach ($ids as $id) {
                    /** @disregard */
                    $this->redis->del($this->key . 'job_' . $name . '_' . $id);
                }
            }

            /** @disregard */
            $this->redis->del($this->key . 'queue_' . $queue . ':' . $name);
        } else {
            /** @disregard */
            $queues = $this->redis->keys($this->key . 'queue_*:' . $name);

            if (!empty($queues)) {
                foreach ($queues as $queue) {
                    /** @disregard */
                    $ids = $this->redis->zrange($queue, 0, -1);

                    if (!empty($ids)) {
                        foreach ($ids as $id) {
                            /** @disregard */
                            $this->redis->del($this->key . 'job_' . $name . '_' . $id);
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
     * Jalankan antrian job di redis.
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
        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $now = Carbon::now()->timestamp;
        $successful = [];
        $failed = [];
        $lists = $queue
            ? [$this->key . 'queue_' . $queue . ':' . $name]
            : $this->redis->keys($this->key . 'queue_*:' . $name);

        if (empty($lists)) {
            $this->log('Job queue does not exist');
            return false;
        }

        foreach ($lists as $list) {
            /** @disregard */
            if (!$this->redis->exists($list)) {
                continue;
            }

            /** @disregard */
            $ready = $this->redis->zrangebyscore($list, '-inf', $now);

            if (!empty($ready)) {
                foreach ($ready as $jid) {
                    $key = $this->key . 'job_' . $name . '_' . $jid;
                    /** @disregard */
                    $data = $this->redis->hgetall($key);

                    if (!empty($data)) {
                        $attempts = 0;
                        $success = false;

                        while ($attempts < $retries && !$success) {
                            $attempts++;

                            try {
                                Event::fire('rakit.jobs.process', [$data]);
                                $successful[] = ['list' => $list, 'jid' => $jid, 'key' => $key];
                                $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));
                                $success = true;
                            } catch (\Throwable $e) {
                                if ($attempts >= $retries) {
                                    $failed[] = ['data' => $data, 'exception' => $e, 'list' => $list, 'jid' => $jid, 'key' => $key];
                                    $this->log(sprintf('Job failed: %s - #%s ::: %s (after %d attempts)', $data['name'], $data['id'], $e->getMessage(), $attempts), 'error');
                                } else {
                                    $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));

                                    if ($sleep_ms > 0) {
                                        usleep($sleep_ms * 1000);
                                    }
                                }
                            } catch (\Exception $e) {
                                if ($attempts >= $retries) {
                                    $failed[] = ['data' => $data, 'exception' => $e, 'list' => $list, 'jid' => $jid, 'key' => $key];
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
            }
        }

        if (!empty($successful)) {
            foreach ($successful as $job) {
                /** @disregard */
                $this->redis->zrem($job['list'], $job['jid']);
                /** @disregard */
                $this->redis->del($job['key']);
            }
        }

        if (!empty($failed)) {
            foreach ($failed as $job) {
                $this->move_to_failed($job['data'], $job['exception']);
                /** @disregard */
                $this->redis->zrem($job['list'], $job['jid']);
                /** @disregard */
                $this->redis->del($job['key']);
            }
        }

        return true;
    }

    /**
     * Jalankan semua job di redis.
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
        $retries = (int) (($retries > 1) ? $retries : $config['max_retries']);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : $config['sleep_ms']);
        $now = Carbon::now()->timestamp;
        $successful = [];
        $failed = [];
        $all = [];

        if ($queues && is_array($queues) && !empty($queues)) {
            foreach ($queues as $queue) {
                /** @disregard */
                $lists = $this->redis->keys($this->key . 'queue_' . $queue . ':*');
                $all = array_merge($all, $lists);
            }
        } else {
            /** @disregard */
            $all = $this->redis->keys($this->key . 'queue_*');
        }

        if (empty($all)) {
            $this->log('No job queues found');
            return false;
        }

        foreach ($all as $queue) {
            $parts = explode(':', str_replace($this->key . 'queue_', '', $queue));
            $default = isset($parts[0]) ? $parts[0] : 'default';
            $name = isset($parts[1]) ? $parts[1] : $default;

            /** @disregard */
            $ready = $this->redis->zrangebyscore($queue, '-inf', $now);

            if (!empty($ready)) {
                foreach ($ready as $jid) {
                    $key = $this->key . 'job_' . $name . '_' . $jid;
                    /** @disregard */
                    $data = $this->redis->hgetall($key);

                    if (!empty($data)) {
                        $attempts = 0;
                        $success = false;

                        while ($attempts < $retries && !$success) {
                            $attempts++;

                            try {
                                Event::fire('rakit.jobs.process', [$data]);
                                $successful[] = ['queue' => $queue, 'jid' => $jid, 'key' => $key];
                                $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $data['name'], $data['id'], $attempts));
                                $success = true;
                            } catch (\Exception $e) {
                                if ($attempts >= $retries) {
                                    $failed[] = ['data' => $data, 'exception' => $e, 'queue' => $queue, 'jid' => $jid, 'key' => $key];
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
            }
        }

        if (!empty($successful)) {
            foreach ($successful as $job) {
                /** @disregard */
                $this->redis->zrem($job['queue'], $job['jid']);
                /** @disregard */
                $this->redis->del($job['key']);
            }
        }

        if (!empty($failed)) {
            foreach ($failed as $job) {
                $this->move_to_failed($job['data'], $job['exception']);
                /** @disregard */
                $this->redis->zrem($job['queue'], $job['jid']);
                /** @disregard */
                $this->redis->del($job['key']);
            }
        }

        return true;
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
        /** @disregard */
        $this->redis->hmset($this->key . 'failed:' . $data['id'], $data);
        /** @disregard */
        $this->redis->lpush($this->key . 'failed_jobs', $data['id']);
    }
}
