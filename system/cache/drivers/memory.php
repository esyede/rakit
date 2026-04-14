<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Arr;

class Memory extends Sectionable
{
    /**
     * Contains the cached items.
     *
     * @var string
     */
    public $storage = [];

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

        return Arr::get($this->storage, $key);
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

        Arr::set($this->storage, $key, $value);
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
            Arr::forget($this->storage, $key);
        }
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        $this->storage = [];
    }

    /**
     * Remove an item from a section.
     *
     * @param string $section
     *
     * @return int|bool
     */
    public function forget_section($section)
    {
        Arr::forget($this->storage, 'section#' . $section);
    }

    /**
     * Get the key for an item in a section.
     *
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    protected function section_item_key($section, $key)
    {
        return 'section#' . $section . '.' . $key;
    }
}
