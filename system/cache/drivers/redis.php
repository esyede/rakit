<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Redis as BaseRedis;

class Redis extends Driver
{
    /**
     * Contains the Redis instance.
     *
     * @var System\Redis
     */
    protected $redis;

    /**
     * Make a new Redis cache driver instance.
     *
     * @param System\Redis $redis
     */
    public function __construct(BaseRedis $redis)
    {
        $this->redis = $redis;
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
        return !is_null($this->redis->get($key));
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
        $cache = $this->redis->get($key);

        if (null === $cache) {
            return null;
        }

        set_error_handler(function () {});
        $value = @unserialize($cache);
        restore_error_handler();

        if ($value === false && $cache !== serialize(false)) {
            try {
                /** @disregard */
                $this->redis->del($key);
            } catch (\Throwable $e) {
                // ignore error
            } catch (\Exception $e) {
                // ignore error
            }

            return null;
        }

        return $value;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * <code>
     *
     *      // Store an item in the cache for 15 minutes
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        /** @disregard */
        $this->redis->set($key, serialize($value));
        /** @disregard */
        $this->redis->expire($key, $minutes * 60);
    }

    /**
     * Increment a numeric value in the cache (atomic).
     *
     * @param string $key
     * @param int    $minutes
     *
     * @return int
     */
    public function increment($key, $minutes = 1)
    {
        /** @disregard */
        $current = (int) $this->redis->incr($key);

        if ($current === 1) {
            /** @disregard */
            $this->redis->expire($key, $minutes * 60);
        }

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
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        /** @disregard */
        $this->redis->del($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        /** @disregard */
        $this->redis->flushall();
    }
}
