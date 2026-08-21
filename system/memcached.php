<?php

namespace System;

defined('DS') or exit('No direct access.');

class Memcached
{
    /**
     * Contains the Memcached connection instance.
     *
     * Note: public, like every other driver registry in the framework
     * (Cache::$drivers, Redis::$databases, ...), so the cached connection can be
     * dropped in a long running process.
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
            // Note: a missing extension used to surface as 'Class Memcached not
            // found' from inside connect(), and a missing config section as a
            // TypeError on its array parameter.
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
            // Note: 'weight' is optional in the configuration file.
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
