<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Cache\Drivers\Memcached as CacheMemcached;

class Memcached extends Driver
{
    /**
     * Berisi instance driver cache Memcached.
     *
     * @var System\Cache\Drivers\Memcached
     */
    private $memcached;

    /**
     * Buat instance baru driver session Memcached.
     *
     * @param System\Cache\Drivers\Memcached $memcached
     */
    public function __construct(CacheMemcached $memcached)
    {
        $this->memcached = $memcached;
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
        return $this->memcached->get($id);
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
        $this->memcached->put($session['id'], $session, $config['lifetime']);
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $this->memcached->forget($id);
    }
}
