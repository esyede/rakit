<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;

class Memcached extends Sectionable
{
    /**
     * Berisi instance Memcache.
     *
     * @var \Memcached
     */
    public $memcached;

    /**
     * Nama key cache dari file konfigurasi.
     *
     * @var string
     */
    protected $key;

    /**
     * Buat instance driver memcached baru.
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
     * Cek apakah item ada di cache.
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
     * Ambil item dari driver cache.
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
     * Simpan item ke cache untuk beberapa menit.
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit.
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
     * Hapus item dari cache.
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
     * Hapus seluruh item cache.
     */
    public function flush()
    {
        /** @disregard */
        return $this->memcached->flush();
    }

    /**
     * Hapus keseluruhan section dari cache.
     *
     * @param string $section
     *
     * @return int|bool
     */
    public function forget_section($section)
    {
        /** @disregard */
        return $this->memcached->increment($this->key . $this->section_key($section));
    }

    /**
     * Ambil ID section saat ini milik section tertentu.
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
     * Ambil nama key milik section tertentu.
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
     * Ambil nama key item section milik section dan key tertentu.
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
