<?php

namespace System;

defined('DS') or exit('No direct access.');

class Email
{
    /**
     * Email priorities.
     */
    const LOWEST = '5 (Lowest)';
    const LOW = '4 (Low)';
    const NORMAL = '3 (Normal)';
    const HIGH = '2 (High)';
    const HIGHEST = '1 (Highest)';

    /**
     * Contains the current email driver instance.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Contains the email driver registrar.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Get the current email driver instance.
     *
     * @param string $driver
     *
     * @return Driver
     */
    public static function driver($driver = null)
    {
        $driver = is_null($driver) ? Config::get('email.driver') : $driver;

        if (!isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Create an email driver instance.
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

        $email = Config::get('email');

        switch ($driver) {
            case 'mail':
                return new Email\Drivers\Mail($email);

            case 'smtp':
                return new Email\Drivers\Smtp($email);

            case 'sendmail':
                return new Email\Drivers\Sendmail($email);

            case 'dummy':
                return new Email\Drivers\Log($email);

            default:
                throw new \Exception(sprintf('Unsupported email driver: %s', $driver));
        }
    }

    /**
     * Register a custom email driver.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Reset the email driver cache to prevent memory leaks in long-running applications.
     */
    public static function reset()
    {
        static::$drivers = [];
    }

    /**
     * Magic method for calling methods on the default email driver.
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
