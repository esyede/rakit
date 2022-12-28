<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Auth
{
    /**
     * Berisi driver auth yang saat ini sedang digunakan.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Berisi registrar driver pihak ketiga.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Ambil instance auth driver.
     *
     * @param string $driver
     *
     * @return Driver
     */
    public static function driver($driver = null)
    {
        $driver = is_null($driver) ? Config::get('auth.driver') : $driver;

        if (!isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Buat instance driver auth.
     *
     * @param string $driver
     *
     * @return Driver
     */
    protected static function factory($driver)
    {
        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver];
            return $resolver();
        }

        switch ($driver) {
            case 'magic':
                return new Auth\Drivers\Magic(Config::get('auth.table'));

            case 'facile':
                return new Auth\Drivers\Facile(Config::get('auth.model'));

            default:
                throw new \Exception(sprintf('Unsupported auth driver: %s', $driver));
        }
    }

    /**
     * Daftarkan auth driver pihak ketiga
     * (Betul! auth driver di framework ini extedable!).
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic Method untuk pemanggilan method pada driver auth default.
     *
     * <code>
     *
     *      // Panggil method user() milik driver default.
     *      $user = Auth::user();
     *
     *      // Panggil method check() milik driver default.
     *      Auth::check();
     *
     * </code>
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::driver(), $method], $parameters);
    }
}
