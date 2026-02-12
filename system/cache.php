<?php

namespace System;

defined('DS') or exit('No direct access.');

class Cache
{
    /**
     * Contains all active cache drivers.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Contains all third-party cache driver registrars.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Processed cache key prefix.
     *
     * @var string|null
     */
    private static $processed_key = null;

    /**
     * Get processed cache key prefix.
     *
     * @return string
     */
    protected static function processed_key()
    {
        if (static::$processed_key === null) {
            $key = (string) Config::get('cache.key');
            static::$processed_key = ((strlen($key) > 0 && Str::ends_with($key, '.')) ? rtrim($key, '.') : $key) . '.';
        }

        return static::$processed_key;
    }

    /**
     * Get the cache driver instance.
     * Or return default driver if no driver is selected.
     *
     * <code>
     *
     *      // Get the default cache driver instance
     *      $driver = Cache::driver();
     *
     *      // Get the memcached cache driver instance
     *      $driver = Cache::driver('memcached');
     *
     * </code>
     *
     * @param string $driver
     *
     * @return \System\Cache\Drivers\Driver
     */
    public static function driver($driver = null)
    {
        if (!is_null($driver) && (!is_string($driver) || empty($driver))) {
            throw new \Exception('Cache driver must be a non-empty string');
        }

        $driver = is_null($driver) ? Config::get('cache.driver') : $driver;

        if (!isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Make a new cache driver instance.
     *
     * @param string $driver
     *
     * @return \System\Cache\Drivers\Driver
     */
    protected static function factory($driver)
    {
        if (!is_string($driver) || empty($driver)) {
            throw new \Exception('Cache driver must be a non-empty string');
        }

        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver];
            return $resolver();
        }

        $key = static::processed_key();

        switch ($driver) {
            case 'apc':
                return new Cache\Drivers\APC($key);

            case 'file':
                return new Cache\Drivers\File(path('storage') . 'cache' . DS);

            case 'memcached':
                return new Cache\Drivers\Memcached(Memcached::connection(), $key);

            case 'memory':
                return new Cache\Drivers\Memory();

            case 'redis':
                return new Cache\Drivers\Redis(Redis::db());

            case 'database':
                return new Cache\Drivers\Database($key);

            default:
                throw new \Exception(sprintf('Unsupported cache driver: %s', $driver));
        }
    }

    /**
     * Register a third-party cache driver.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Call methods on the default cache driver.
     *
     * <code>
     *
     *      // Call the get method on the default cache driver.
     *      $name = Cache::get('name');
     *
     *      // Call the put() method on the default cache driver.
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::driver(), $method], $parameters);
    }
}
