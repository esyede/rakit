<?php

namespace System;

defined('DS') or exit('No direct access.');

class Memcached
{
    /**
     * Contains the Memcached connection instance.
     *
     * @var \Memcached
     */
    public static $connection;

    /**
     * Get the Memcached connection instance.
     *
     * <code>
     *
     *      // Get the Memcached connection instance and retrieve an item from cache.
     *      $name = Memcached::connection()->get('name');
     *
     *      // Get the Memcached connection instance and store an item in cache.
     *      Memcached::connection()->set('name', 'Budi');
     *
     * </code>
     *
     * @return \Memcached
     */
    public static function connection()
    {
        if (!static::$connection) {
            if (!class_exists('\Memcached')) {
                throw new \Exception('The memcached extension is not installed or not enabled.');
            }

            $servers = Config::get('cache.memcached');

            if (!is_array($servers) || empty($servers)) {
                throw new \Exception('No memcached server configured in cache.memcached.');
            }

            static::$connection = static::connect($servers);
        }

        return static::$connection;
    }

    /**
     * Create a new Memcached connection instance.
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
            $memcached->addServer(
                $server['host'],
                $server['port'],
                isset($server['weight']) ? $server['weight'] : 0
            );
        }

        /** @disregard */
        if (false === $memcached->getVersion()) {
            throw new \Exception('Could not establish memcached connection.');
        }

        return $memcached;
    }

    /**
     * Magic method to handle static calls to the Memcached instance.
     *
     * <code>
     *
     *      // Get an item from the Memcached instance.
     *      $name = Memcached::get('name');
     *
     *      // Store an item in the Memcached instance.
     *      Memcached::set('name', 'Budi');
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::connection(), $method], $parameters);
    }
}
