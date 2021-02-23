<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Memcached
{
    /**
     * Berisi instance koneksi Memcached.
     *
     * @var Memcached
     */
    protected static $connection;

    /**
     * Ambil instance koneksi Memcached.
     *
     * <code>
     *
     *      // Ambil instance koneksi Memcached lalu ambil sebuah item dari cache.
     *      $name = Memcached::connection()->get('name');
     *
     *      // Ambil instance koneksi Memcached lalu taruh sebuah item ke cache.
     *      Memcached::connection()->set('name', 'Budi');
     *
     * </code>
     *
     * @return Memcached
     */
    public static function connection()
    {
        if (is_null(static::$connection)) {
            static::$connection = static::connect(Config::get('cache.memcached'));
        }

        return static::$connection;
    }

    /**
     * Buat instance koneksi Memcached baru.
     *
     * @param array $servers
     *
     * @return Memcached
     */
    protected static function connect($servers)
    {
        $memcache = new \Memcached();

        foreach ($servers as $server) {
            $memcache->addServer($server['host'], $server['port'], $server['weight']);
        }

        if (false === $memcache->getVersion()) {
            throw new \Exception('Could not establish memcached connection.');
        }

        return $memcache;
    }

    /**
     * Oper seluruh pemanggilan method lain ke Memcached secara dinamis.
     *
     * <code>
     *
     *      // Ambil sebuah item dari instance Memcached.
     *      $name = Memcached::get('name');
     *
     *      // Taruh sebuah item ke Memcache server.
     *      Memcached::set('name', 'Budi');
     *
     * </code>
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::connection(), $method], $parameters);
    }
}
