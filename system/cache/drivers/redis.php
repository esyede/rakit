<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

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
        /** @disregard */
        return !is_null($this->redis->get($key));
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
        /** @disregard */
        $cache = $this->redis->get($key);

        if (null === $cache) {
            return null;
        }

        set_error_handler(function () {});
        $value = @unserialize($cache);
        restore_error_handler();

        if ($value === false && $cache !== serialize(false)) {
            try {
                /** @disregard */
                $this->redis->del($key);
            } catch (\Exception $e) {
                // ignore error
            }

            return null;
        }

        return $value;
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
        /** @disregard */
        $this->redis->set($key, serialize($value));
        /** @disregard */
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
        $this->put($key, $value, 2628000);
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        /** @disregard */
        $this->redis->del($key);
    }

    /**
     * Hapus seluruhitem cache.
     */
    public function flush()
    {
        /** @disregard */
        $this->redis->flushall();
    }
}
