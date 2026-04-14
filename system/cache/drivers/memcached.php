<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;

class Memcached extends Sectionable
{
    /**
     * Contains the Memcached instance.
     *
     * @var \Memcached
     */
    public $memcached;

    /**
     * The cache key prefix from the configuration file.
     *
     * @var string
     */
    protected $key;

    /**
     * Make a new Memcached cache driver instance.
     *
     * @param \Memcached $memcached
     * @param string     $key
     */
    public function __construct(\Memcached $memcached, $key)
    {
        $this->key = $key;
        $this->memcached = $memcached;
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
        if ($this->sectionable($key)) {
            list($section, $key) = $this->parse($key);
            return $this->get_from_section($section, $key);
        }

        /** @disregard */
        $cache = $this->memcached->get($this->key . $key);

        if (false !== $cache) {
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
        if ($this->sectionable($key)) {
            list($section, $key) = $this->parse($key);
            return $this->put_in_section($section, $key, $value, $minutes);
        }

        /** @disregard */
        $this->memcached->set($this->key . $key, $value, $minutes * 60);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        if ($this->sectionable($key)) {
            list($section, $key) = $this->parse($key);

            if ('*' === $key) {
                $this->forget_section($section);
            } else {
                $this->forget_in_section($section, $key);
            }
        } else {
            /** @disregard */
            $this->memcached->delete($this->key . $key);
        }
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        /** @disregard */
        return $this->memcached->flush();
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
        $prefixed = $this->key . $key;

        /** @disregard */
        if ($this->memcached->add($prefixed, 1, $minutes * 60)) {
            return 1;
        }

        /** @disregard */
        $current = $this->memcached->increment($prefixed);
        return ($current !== false) ? (int) $current : 1;
    }

    /**
     * Remove an entire section from the cache.
     *
     * @return int|bool
     */
    public function forget_section($section)
    {
        /** @disregard */
        return $this->memcached->increment($this->key . $this->section_key($section));
    }

    /**
     * Get the section ID for a given section.
     *
     * @param string $section
     *
     * @return int
     */
    protected function section_id($section)
    {
        return $this->sear($this->section_key($section), function () {
            return Str::integers(1, 10000);
        });
    }

    /**
     * Get the section key for a given section.
     *
     * @param string $section
     *
     * @return string
     */
    protected function section_key($section)
    {
        return $section . '_section_key';
    }

    /**
     * Get the section item key for a given section and key.
     *
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    protected function section_item_key($section, $key)
    {
        return $section . '#' . $this->section_id($section) . '#' . $key;
    }
}
