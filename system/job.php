<?php

namespace System;

use Memcached;

defined('DS') or exit('No direct access.');

class Job
{
    /**
     * Berisi seluruh job driver yang aktif.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Berisi registrar job driver pihak ketiga.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Ambil instance job driver.
     * Atau return driver default jika tidak ada driver yang dipilih.
     *
     * <code>
     *
     *      // Ambil instance driver default
     *      $driver = Job::driver();
     *
     *      // Ambil instance driver tertentu
     *      $driver = Job::driver('database');
     *
     * </code>
     *
     * @param string $driver
     *
     * @return \System\Job\Drivers\Driver
     */
    public static function driver($driver = null)
    {
        $driver = is_null($driver) ? Config::get('job.driver') : $driver;

        if (!isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Buat instance job driver baru.
     *
     * @param string $driver
     *
     * @return \System\Job\Drivers\Driver
     */
    protected static function factory($driver)
    {
        switch ($driver) {
            case 'file':
                return new Job\Drivers\File(path('storage') . 'jobs' . DS);

            case 'database':
                return new Job\Drivers\Database();

            case 'redis':
                return new Job\Drivers\Redis(Redis::db());

            case 'memcached':
                return new Job\Drivers\Memcached(Memcached::connection());

            default:
                throw new \Exception(sprintf('Unsupported job driver: %s', $driver));
        }
    }

    /**
     * Daftarkan job driver pihak ketiga.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic Method untuk memanggil method milik job driver default.
     *
     * <code>
     *
     *      // Panggil method push() milik job driver default.
     *      Job::push('send-email', ['to' => 'user@example.com']);
     *
     *      // Panggil method process() milik job driver default.
     *      Job::process('send-email');
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::driver(), $method], $parameters);
    }
}
