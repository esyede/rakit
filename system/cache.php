<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Cache
{
    /**
     * Berisi seluruh cache driver yang aktif.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Berisi registrar cache driver pihak ketiga.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Ambil instance cache driver.
     * Atau return driver default jika tidak ada driver yang dipilih.
     *
     * <code>
     *
     *      // Ambil instance driver default
     *      $driver = Cache::driver();
     *
     *      // Ambil instance driver tertentu
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
        $driver = is_null($driver) ? Config::get('cache.driver') : $driver;

        if (!isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Buat instance cache driver baru.
     *
     * @param string $driver
     *
     * @return \System\Cache\Drivers\Driver
     */
    protected static function factory($driver)
    {
        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver];
            return $resolver();
        }

        $key = Config::get('cache.key');

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
     * Daftarkan cache driver pihak ketiga.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic Method untuk memanggil method milik cache driver default.
     *
     * <code>
     *
     *      // Panggil method get() milik cache driver default.
     *      $name = Cache::get('name');
     *
     *      // Panggil method put() milik cache driver default.
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::driver(), $method], $parameters);
    }
}
