<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Hook;

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

        if (
            class_exists('\System\Foundation\Oops\Debugger', false)
            && ! \System\Foundation\Oops\Debugger::$productionMode
        ) {
            if (! \System\Foundation\Oops\Debugger::$productionMode) {
                \System\Foundation\Oops\Collectors::trackCacheOperation(
                    is_null($item) ? 'miss' : 'hit',
                    $key,
                    null,
                    $time
                );
            }
        }

        Hook::fire(is_null($item) ? 'rakit.cache.missed' : 'rakit.cache.hit', [$key, $time]);

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
     * @param string   $key
     * @param mixed    $value
     * @param int|null $minutes
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
     * Get an item from the cache, storing the default for the given minutes when it is missing.
     *
     * @param string   $key
     * @param int|null $minutes
     * @param mixed    $default
     * @param string   $function
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
     * Get an item from the cache, storing the default for five years when it is missing.
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
     * @param string   $key
     * @param int|null $minutes
     * @param int      $by
     *
     * @return int
     */
    public function increment($key, $minutes = 1, $by = 1)
    {
        $new = ((int) ($this->get($key) ?: 0)) + (int) $by;
        $this->put($key, $new, $minutes);
        return $new;
    }

    /**
     * Decrement a numeric value in the cache.
     *
     * @param string   $key
     * @param int|null $minutes
     * @param int      $by
     *
     * @return int
     */
    public function decrement($key, $minutes = 1, $by = 1)
    {
        return $this->increment($key, $minutes, -1 * (int) $by);
    }

    /**
     * Store a value only when the key is not there yet. TRUE when it was stored.
     * Atomic on the file, database, redis, memcached and apc drivers.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     *
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        return $this->add_seconds($key, $value, (int) $minutes * 60);
    }

    /**
     * Store a value for some seconds, only when the key is not there yet. Drivers that
     * can do it atomically override this; the fallback is a check, then a write.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds
     *
     * @return bool
     */
    protected function add_seconds($key, $value, $seconds)
    {
        if (! is_null($this->retrieve($key))) {
            return false;
        }

        $this->put($key, $value, max(1, (int) ceil($seconds / 60)));
        return true;
    }

    /**
     * Get several values at once, key => value, NULL for the missing ones.
     *
     * @param array $keys
     *
     * @return array
     */
    public function many(array $keys)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * Store several values at once.
     *
     * @param array $values
     * @param int   $minutes
     */
    public function put_many(array $values, $minutes)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $minutes);
        }
    }

    /**
     * Get a lock: Cache::lock('report', 10)->get(function () { ... }).
     *
     * @param string      $name
     * @param int         $seconds
     * @param string|null $owner
     *
     * @return \System\Cache\Lock
     */
    public function lock($name, $seconds = 10, $owner = null)
    {
        return new \System\Cache\Lock($this, $name, $seconds, $owner);
    }

    /**
     * Take a lock for its owner. Used by Cache\Lock.
     *
     * @param string $name
     * @param string $owner
     * @param int    $seconds
     *
     * @return bool
     */
    public function acquire_lock($name, $owner, $seconds)
    {
        return $this->add_seconds('lock:'.$name, (string) $owner, max(1, (int) $seconds));
    }

    /**
     * Release a lock, only when the owner still holds it: a lock that expired and was
     * taken by someone else is left alone. Used by Cache\Lock.
     *
     * @param string $name
     * @param string $owner
     *
     * @return bool
     */
    public function release_lock($name, $owner)
    {
        if ((string) $owner !== $this->retrieve('lock:'.$name)) {
            return false;
        }

        $this->forget('lock:'.$name);
        return true;
    }
}
