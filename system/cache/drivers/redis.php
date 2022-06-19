<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct script access.');

use System\Redis as BaseRedis;

class Redis extends Driver
{
    /**
     * Berisi instance database redis.
     *
     * @var System\Redis
     */
    protected $redis;

    /**
     * Buat instance driver redis baru.
     *
     * @param System\Redis $redis
     */
    public function __construct(BaseRedis $redis)
    {
        $this->redis = $redis;
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
        return ! is_null($this->redis->get($key));
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
        $cache = $this->redis->get($key);
        return is_null($cache) ? null : unserialize($cache);
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
        $this->forever($key, $value);
        $this->redis->expire($key, $minutes * 60);
    }

    /**
     * Simpan item ke cache untuk selamanya (atau 5 tahun).
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        $this->redis->set($key, serialize($value));
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $this->redis->del($key);
    }
}
