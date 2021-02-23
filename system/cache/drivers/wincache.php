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
        return ! is_null($this->get($key));
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
        if (false !== ($cache = wincache_ucache_get($this->key.$key))) {
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
        wincache_ucache_add($this->key.$key, $value, $minutes * 60);
    }

    /**
     * Simpan item ke cache untuk selamanya (atau 5 tahun).
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        wincache_ucache_delete($this->key.$key);
    }
}
