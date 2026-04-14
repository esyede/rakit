<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;

abstract class Sectionable extends Driver
{
    /**
     * Indicates whether the driver should implicitly handle sectioned keys.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * The delimiter used to separate sections and keys in a sectioned key.
     *
     * @var string
     */
    public $delimiter = '::';

    /**
     * Get a section item from the cache.
     *
     * @param string $section
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get_from_section($section, $key, $default = null)
    {
        return $this->get($this->section_item_key($section, $key), $default);
    }

    /**
     * Store a section item in the cache for a given number of minutes.
     *
     * @param string $section
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put_in_section($section, $key, $value, $minutes)
    {
        $this->put($this->section_item_key($section, $key), $value, $minutes);
    }

    /**
     * Store a section item in the cache indefinitely (or 5 years).
     *
     * @param string $section
     * @param string $key
     * @param mixed  $value
     */
    public function forever_in_section($section, $key, $value)
    {
        return $this->forever($this->section_item_key($section, $key), $value);
    }

    /**
     * Get a section item from the cache, or store the default value.
     *
     * @param string $section
     * @param string $key
     * @param mixed  $default
     * @param int    $minutes
     * @param string $function
     *
     * @return mixed
     */
    public function remember_in_section($section, $key, $default, $minutes, $function = 'put')
    {
        return $this->remember($this->section_item_key($section, $key), $default, $minutes, $function);
    }

    /**
     * Get a section item from the cache, or return the default value.
     *
     * @param string $section
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function sear_in_section($section, $key, $default)
    {
        return $this->sear($this->section_item_key($section, $key), $default);
    }

    /**
     * Remove a section item from the cache.
     *
     * @param string $section
     * @param string $key
     */
    public function forget_in_section($section, $key)
    {
        return $this->forget($this->section_item_key($section, $key));
    }

    /**
     * Get the key for an item in a section.
     *
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    abstract protected function section_item_key($section, $key);

    /**
     * Remove all items from a section.
     *
     * @param string $section
     *
     * @return int|bool
     */
    abstract public function forget_section($section);

    /**
     * Check if the given key is sectionable.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function sectionable($key)
    {
        return $this->implicit && $this->sectioned($key);
    }

    /**
     * Check if the given key is sectioned.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function sectioned($key)
    {
        return Str::contains($key, '::');
    }

    /**
     * Get the section and key from a sectioned key.
     *
     * @param string $key
     *
     * @return array
     */
    protected function parse($key)
    {
        return explode('::', $key, 2);
    }
}
