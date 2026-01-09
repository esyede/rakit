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
        $id = Str::nanoid();
        $data = [
            'id' => $id,
            'name' => $name,
            'payloads' => serialize($payloads),
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $key = $this->key . 'job_' . $name . '_' . $id;
        $list = $this->key . 'queue_' . $name;
        /** @disregard */
        $this->redis->hmset($key, $data);

        $timestamp = Carbon::parse($scheduled_at)->timestamp;
        /** @disregard */
        $this->redis->zadd($list, $timestamp, $id);

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
        $list = $this->key . 'queue_' . $name;
        /** @disregard */
        $ids = $this->redis->zrange($list, 0, -1);

        foreach ($ids as $id) {
            /** @disregard */
            $this->redis->del($this->key . 'job_' . $name . '_' . $id);
        }

        /** @disregard */
        $this->redis->del($list);
        $this->log(sprintf('Jobs deleted: %s', $name));
        return true;
    }

    /**
     * Jalankan antrian job di redis.
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
        $list = $this->key . 'queue_' . $name;

        /** @disregard */
        if (!$this->redis->exists($list)) {
            $this->log('Job queue does not exist');
            return false;
        }

        $retries = (int) (($retries > 1) ? $retries : 1);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : 0);
        $now = Carbon::now()->timestamp;
        $ready = $this->redis->zrangebyscore($list, '-inf', $now, ['withscores' => false]);
        $successful = [];

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
                        Event::fire('rakit.jobs.run: ' . $data['name'], unserialize($data['payloads']));
                        $successful[] = $jid;
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

        foreach ($successful as $jid) {
            $key = $this->key . 'job_' . $name . '_' . $jid;
            /** @disregard */
            $this->redis->zrem($list, $jid);
            /** @disregard */
            $this->redis->del($key);
        }

        return true;
    }

    /**
     * Jalankan semua job di redis.
     *
     * @param int $retries
     * @param int $sleep_ms
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0)
    {
        /** @disregard */
        $queues = $this->redis->keys($this->key . 'queue_*');

        if (empty($queues)) {
            $this->log('No job queues found');
            return false;
        }

        $retries = (int) (($retries > 1) ? $retries : 1);
        $sleep_ms = (int) (($sleep_ms > 0) ? $sleep_ms : 0);
        $now = Carbon::now()->timestamp;
        $successful = [];

        foreach ($queues as $queue) {
            $name = str_replace([$this->key . 'queue_', $this->key], '', $queue);
            /** @disregard */
            $ready = $this->redis->zrangebyscore($queue, '-inf', $now, ['withscores' => false]);

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
                            Event::fire('rakit.jobs.run: ' . $data['name'], unserialize($data['payloads']));
                            $successful[] = ['queue' => $queue, 'jid' => $jid, 'key' => $key];
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
        }

        foreach ($successful as $job) {
            /** @disregard */
            $this->redis->zrem($job['queue'], $job['jid']);
            /** @disregard */
            $this->redis->del($job['key']);
        }

        return true;
    }
}
