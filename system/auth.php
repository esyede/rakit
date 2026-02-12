<?php

namespace System;

defined('DS') or exit('No direct access.');

class Auth
{
    /**
     * Contains the current auth driver.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Contains the third-party auth driver registrar.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Get the current auth driver instance.
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
     * Make a new auth driver instance.
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
     * Register a third-party auth driver registrar.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic method for calling methods on the default auth driver.
     *
     * <code>
     *
     *      // Call user() method on the default auth driver.
     *      $user = Auth::user();
     *
     *      // Call check() method on the default auth driver.
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
