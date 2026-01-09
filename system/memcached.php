<?php

namespace System;

defined('DS') or exit('No direct access.');

class Memcached
{
    /**
     * Berisi instance koneksi Memcached.
     *
     * @var \Memcached
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
     * @return \Memcached
     */
    public static function connection()
    {
        if (!static::$connection) {
            static::$connection = static::connect(Config::get('cache.memcached'));
        }

        return static::$connection;
    }

    /**
     * Buat instance koneksi Memcached baru.
     *
     * @param array $servers
     *
     * @return \Memcached
     */
    protected static function connect(array $servers)
    {
        $memcached = new \Memcached();

        foreach ($servers as $server) {
            /** @disregard */
            $memcached->addServer($server['host'], $server['port'], $server['weight']);
        }

        /** @disregard */
        if (false === $memcached->getVersion()) {
            throw new \Exception('Could not establish memcached connection.');
        }

        return $memcached;
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
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::connection(), $method], $parameters);
    }
}
