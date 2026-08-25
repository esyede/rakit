<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

abstract class Driver
{
    /**
     * Check if an item exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract public function has($key);

    /**
     * Retrieve an item from the cache. If the item does not exist, return the default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $start = microtime(true);
        $item = $this->retrieve($key);
        $time = (microtime(true) - $start) * 1000;

        if (class_exists('\System\Foundation\Oops\Debugger') && class_exists('\System\Foundation\Oops\Collectors')) {
            if (! \System\Foundation\Oops\Debugger::$productionMode) {
                \System\Foundation\Oops\Collectors::trackCacheOperation(is_null($item) ? 'miss' : 'hit', $key, null, $time);
            }
        }

        return is_null($item) ? value($default) : $item;
    }

    /**
     * Retrieve an item from the cache and delete it. If the item does not exist, return the default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Retrieve an item from the cache driver.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract protected function retrieve($key);

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    abstract public function put($key, $value, $minutes);

    /**
     * Store an item in the cache indefinitely (or for 5 years).
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 2628000);
    }

    /**
     * Retrieve an item from the cache, or store the default value in the cache for a given number of minutes.
     *
     * @param string $key
     * @param int    $minutes
     * @param mixed  $default
     * @param string $function
     *
     * @return mixed
     */
    public function remember($key, $minutes, $default, $function = 'put')
    {
        if (! is_null($item = $this->get($key, null))) {
            return $item;
        }

        $this->{$function}($key, $default = value($default), $minutes);
        return $default;
    }

    /**
     * Retrieve an item from the cache, or store the default value in the cache indefinitely (or for 5 years).
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function sear($key, $default)
    {
        return $this->remember($key, null, $default, 'forever');
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    abstract public function forget($key);

    /**
     * Remove all items from the cache.
     */
    abstract public function flush();

    /**
     * Get the expiration time for a given number of minutes.
     *
     * @param int $minutes
     *
     * @return int
     */
    protected function expiration($minutes)
    {
        return time() + ($minutes * 60);
    }

    /**
     * Increment a numeric value in the cache.
     *
     * @param string $key
     * @param int    $minutes
     *
     * @return int
     */
    public function increment($key, $minutes = 1)
    {
        $new = ((int) ($this->get($key) ?: 0)) + 1;
        $this->put($key, $new, $minutes);
        return $new;
    }
}
