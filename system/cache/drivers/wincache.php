<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct script access.');

class WinCache extends Driver
{
    /**
     * Nama key cache dari file konfigurasi.
     *
     * @var string
     */
    protected $key;

    /**
     * Buat instance driver wincache baru.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
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
        $cache = wincache_ucache_get($this->key . $key);
        return (false === $cache) ? null : $cache;
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
        return wincache_ucache_add($this->key . $key, $value, $minutes * 60);
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        return wincache_ucache_delete($this->key . $key);
    }
}
