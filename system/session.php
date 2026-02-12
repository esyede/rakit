<?php

namespace System;

defined('DS') or exit('No direct access.');

class Session
{
    /**
     * The CSRF token name stored in session.
     *
     * @var string
     */
    const TOKEN = 'csrf_token';

    /**
     * Contains the instance session (singleton).
     *
     * @var Session\Payload
     */
    public static $instance;

    /**
     * Contains the list of third-party session drivers.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Create payload session and load session.
     */
    public static function load()
    {
        $config = Config::get('session');

        // Override PHP session configuration
        ini_set('session.gc_maxlifetime', $config['lifetime'] * 60);
        ini_set('session.cookie_lifetime', $config['expire_on_close'] ? 0 : $config['lifetime'] * 60);
        ini_set('session.cookie_path', $config['path']);
        ini_set('session.cookie_domain', $config['domain'] ?: '');
        ini_set('session.cookie_secure', $config['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.serialize_handler', $config['serialize']);

        if (!is_null($config['sid_length']) && PHP_VERSION_ID >= 70100) {
            ini_set('session.sid_length', $config['sid_length']);
        }

        // Set save path for file driver
        if ($config['driver'] === 'file') {
            ini_set('session.save_path', path('storage') . 'sessions' . DS);
        }

        static::start($config['driver']);
        static::$instance->load(Cookie::get($config['cookie']));

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(false);
        }
    }

    /**
     * Create a new instance of the session payload.
     *
     * @param string $driver
     */
    public static function start($driver)
    {
        if (!is_string($driver) || empty($driver)) {
            throw new \Exception('Session driver must be a non-empty string');
        }

        static::$instance = new Session\Payload(static::factory($driver));
    }

    /**
     * Create a new instance of the session driver.
     *
     * @param string $driver
     *
     * @return Session\Drivers\Driver
     */
    public static function factory($driver)
    {
        if (!is_string($driver) || empty($driver)) {
            throw new \Exception('Session driver must be a non-empty string');
        }

        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver];
            return $resolver();
        }

        switch ($driver) {
            case 'apc':
                return new Session\Drivers\APC(Cache::driver('apc'));

            case 'cookie':
                return new Session\Drivers\Cookie();

            case 'database':
                return new Session\Drivers\Database(Database::connection());

            case 'file':
                return new Session\Drivers\File(path('storage') . 'sessions' . DS);

            case 'memcached':
                return new Session\Drivers\Memcached(Cache::driver('memcached'));

            case 'memory':
                return new Session\Drivers\Memory();

            case 'redis':
                return new Session\Drivers\Redis(Cache::driver('redis'));

            default:
                throw new \Exception(sprintf('Unsupported session driver: %s', $driver));
        }
    }

    /**
     * Get the current session instance.
     *
     * <code>
     *
     *      // Get the current session instance and retrieve an item
     *      Session::instance()->get('name');
     *
     *      // Get the current session instance and put an item into session
     *      Session::instance()->put('name', 'Budi');
     *
     * </code>
     *
     * @return Session\Payload
     */
    public static function instance()
    {
        if (static::started()) {
            return static::$instance;
        }

        throw new \Exception('A driver must be set before using the session.');
    }

    /**
     * Check if the session has started.
     *
     * @return bool
     */
    public static function started()
    {
        return !is_null(static::$instance);
    }

    /**
     * Register a third-party session driver.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic method for calling methods on the current session instance statically.
     *
     * <code>
     *
     *      // Get an item from session
     *      $value = Session::get('name');
     *
     *      // Put an item into session
     *      Session::put('name', 'Budi');
     *      Session::instance()->put('name', 'Budi');
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::instance(), $method], $parameters);
    }
}
