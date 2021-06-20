<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Email
{
    /**
     * Prioritas email.
     */
    const LOWEST = '5 (Lowest)';
    const LOW = '4 (Low)';
    const NORMAL = '3 (Normal)';
    const HIGH = '2 (High)';
    const HIGHEST = '1 (Highest)';

    /**
     * Berisi driver email yang saat ini sedang digunakan.
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
        $driver = is_null($driver) ? Config::get('email.driver') : $driver;

        if (! isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Buat instance driver email.
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
            case 'mail':     return new Email\Drivers\Mail(Config::get('email'));
            case 'smtp':     return new Email\Drivers\Smtp(Config::get('email'));
            case 'sendmail': return new Email\Drivers\Sendmail(Config::get('email'));
            case 'dummy':    return new Email\Drivers\Dummy(Config::get('email'));
            default:         throw new \Exception(sprintf('Unsupported email driver: %s', $driver));
        }
    }

    /**
     * Daftarkan email driver pihak ketiga
     * (Betul! email driver di framework ini extedable!).
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic Method untuk pemanggilan method pada driver email default.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::driver(), $method], $parameters);
    }
}
