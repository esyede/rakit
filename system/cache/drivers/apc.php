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
     * Whether the APCu function names are the ones available.
     *
     * @var bool|null
     */
    protected static $apcu;

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
     * Check whether this runtime speaks APCu rather than the original APC.
     *
     * @return bool
     */
    protected static function apcu()
    {
        if (is_null(static::$apcu)) {
            static::$apcu = function_exists('apcu_fetch');
        }

        return static::$apcu;
    }

    /**
     * Check whether an APC (or APCu) extension is usable at all.
     *
     * @return bool
     */
    public static function usable()
    {
        return function_exists('apcu_fetch') || function_exists('apc_fetch');
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
        $success = false;

        /** @disregard */
        $cache = static::apcu()
            ? apcu_fetch($this->key . $key, $success)
            : apc_fetch($this->key . $key, $success);

        return $success ? $cache : null;
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
        static::apcu()
            ? apcu_store($this->key . $key, $value, $minutes * 60)
            : apc_store($this->key . $key, $value, $minutes * 60);
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
        $current = static::apcu() ? apcu_inc($this->key . $key) : apc_inc($this->key . $key);

        if ($current !== false) {
            return (int) $current;
        }

        $this->put($key, 1, $minutes);

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
        static::apcu() ? apcu_delete($this->key . $key) : apc_delete($this->key . $key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        if (static::apcu()) {
            /** @disregard */
            apcu_clear_cache();
            return;
        }

        /** @disregard */
        apc_clear_cache('user');
    }
}
