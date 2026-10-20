<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Redis as BaseRedis;

class Redis extends Driver
{
    /**
     * Contains the Redis instance.
     *
     * @var \System\Redis
     */
    protected $redis;

    /**
     * Contains the prefix every key of this cache is stored under.
     *
     * @var string
     */
    protected $key;

    /**
     * Make a new Redis cache driver instance.
     *
     * @param \System\Redis $redis
     * @param string        $key
     */
    public function __construct(BaseRedis $redis, $key = '')
    {
        $this->redis = $redis;
        $this->key = (string) $key;
    }

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        /** @disregard */
        return ! is_null($this->redis->get($this->key.$key));
    }

    /**
     * Retrieve an item from the cache driver.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function retrieve($key)
    {
        /** @disregard */
        $cache = $this->redis->get($this->key.$key);

        if (null === $cache) {
            return;
        }

        // Counters are stored as plain integers so INCR can work on them.
        if (preg_match('/^-?\d+$/', $cache)) {
            return (int) $cache;
        }

        set_error_handler(function () {});
        $value = @unserialize($cache);
        restore_error_handler();

        if ($value === false && $cache !== serialize(false)) {
            try {
                /** @disregard */
                $this->redis->del($this->key.$key);
            } catch (\Throwable $e) {
                // ignore error
            } catch (\Exception $e) {
                // ignore error
            }

            return;
        }

        return $value;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        /** @disregard */
        $this->redis->set($this->key.$key, is_int($value) ? (string) $value : serialize($value));
        /** @disregard */
        $this->redis->expire($this->key.$key, $minutes * 60);
    }

    /**
     * Increment a numeric value in the cache (atomic).
     *
     * @param string $key
     * @param int    $minutes
     * @param int    $by
     *
     * @return int
     */
    public function increment($key, $minutes = 1, $by = 1)
    {
        $by = (int) $by;

        try {
            /** @disregard */
            $current = (int) $this->redis->incrby($this->key.$key, $by);
        } catch (\Throwable $e) {
            $current = $this->recount($key, $minutes, $by);
        } catch (\Exception $e) {
            $current = $this->recount($key, $minutes, $by);
        }

        /** @disregard */
        if (-1 === (int) $this->redis->ttl($this->key.$key)) {
            /** @disregard */
            $this->redis->expire($this->key.$key, $minutes * 60);
        }

        return $current;
    }

    /**
     * Rewrite a serialized value into the plain integer INCR needs, for older data.
     *
     * @param string $key
     * @param int    $minutes
     *
     * @return int
     */
    protected function recount($key, $minutes, $by = 1)
    {
        $current = ((int) $this->retrieve($key)) + (int) $by;
        $this->put($key, $current, $minutes);

        return $current;
    }

    /**
     * Store an item in the cache indefinitely (or 5 years).
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        $this->put($key, $value, 2628000);
    }

    /**
     * Store a value only when the key is not there yet, with SET NX.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds
     *
     * @return bool
     */
    protected function add_seconds($key, $value, $seconds)
    {
        /** @disregard */
        $reply = $this->redis->set(
            $this->key.$key,
            is_int($value) ? (string) $value : serialize($value),
            'EX',
            max(1, (int) $seconds),
            'NX'
        );

        return 'OK' === $reply || true === $reply;
    }

    /**
     * Release a lock its owner still holds: compare and delete in one script.
     *
     * @param string $name
     * @param string $owner
     *
     * @return bool
     */
    public function release_lock($name, $owner)
    {
        $script = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";
        /** @disregard */
        return 1 === (int) $this->redis->eval($script, 1, $this->key.'lock:'.$name, serialize((string) $owner));
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        /** @disregard */
        $this->redis->del($this->key.$key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        if ('' === $this->key) {
            /** @disregard */
            $this->redis->flushdb();
            return;
        }

        /** @disregard */
        $keys = (array) $this->redis->keys($this->key.'*');

        foreach ($keys as $key) {
            /** @disregard */
            $this->redis->del($key);
        }
    }
}
