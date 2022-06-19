<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct script access.');

use System\Cache\Drivers\Redis as CacheRedis;

class Redis extends Driver
{
    /**
     * Berisi instance driver cache Redis.
     *
     * @var System\Cache\Drivers\Redis
     */
    protected $redis;

    /**
     * Buat instance baru driver session Redis.
     *
     * @param System\Cache\Drivers\Redis $redis
     */
    public function __construct(CacheRedis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Muat session berdasarkan ID yang diberikan.
     * Jika session tidak ditemukan, NULL akan direturn.
     *
     * @param string $id
     *
     * @return array
     */
    public function load($id)
    {
        return $this->redis->get($id);
    }

    /**
     * Simpan session.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    public function save(array $session, array $config, $exists)
    {
        $this->redis->put($session['id'], $session, $config['lifetime']);
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $this->redis->forget($id);
    }
}
