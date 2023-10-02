<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;

abstract class Sectionable extends Driver
{
    /**
     * Menunjukkan bahwa section caching implisit berdasarkan keynya.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * Pembatas untuk section key implisit.
     *
     * @var string
     */
    public $delimiter = '::';

    /**
     * Ambil potongan item section dari driver cache.
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
     * Simpan potongan item section ke cache.
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
     * Simpan potongan item section ke cache selamanya (atau 5 tahun).
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
     * Ambil potongan item section dari cache, atau simpan dan return nilai defaultnya.
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
        $key = $this->section_item_key($section, $key);
        return $this->remember($key, $default, $minutes, $function);
    }

    /**
     * Ambil potongan item section dari cache, atau simpan nilai defaultnya.
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
     * Hapus potongan item section dari cache.
     *
     * @param string $section
     * @param string $key
     */
    public function forget_in_section($section, $key)
    {
        return $this->forget($this->section_item_key($section, $key));
    }

    /**
     * Ambil nama key item section milik section dan key tertentu.
     *
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    abstract protected function section_item_key($section, $key);

    /**
     * Hapus keseluruhan section dari cache.
     *
     * @param string $section
     *
     * @return int|bool
     */
    abstract public function forget_section($section);

    /**
     * Indikasi bahwa key bisa terdiri dari beberapa section.
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
     * Cek apakah key terdiri dari beberapa section.
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
     * Ambil section dan key milik sebuah item.
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
