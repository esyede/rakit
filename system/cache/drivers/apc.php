<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

class APC extends Driver
{
    /**
     * Contains the cache key prefix from the configuration file.
     *
     * @var string
     */
    protected $key;

    /**
     * Make a new APC cache driver instance.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
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
        return !is_null($this->get($key));
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
        if (false !== ($cache = apc_fetch($this->key . $key))) {
            return $cache;
        }
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
        apc_store($this->key . $key, $value, $minutes * 60);
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
        $current = apc_inc($this->key . $key);

        if ($current !== false) {
            return (int) $current;
        }

        /** @disregard */
        apc_store($this->key . $key, 1, $minutes * 60);
        return 1;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        /** @disregard */
        apc_delete($this->key . $key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        /** @disregard */
        apc_clear_cache();
        /** @disregard */
        apc_clear_cache('user');
        /** @disregard */
        apc_clear_cache('opcode');
    }
}
