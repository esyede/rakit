<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Session
{
    /**
     * Nama string CSRF token yang disimpan di session.
     *
     * @var string
     */
    const TOKEN = 'csrf_token';

    /**
     * Berisi instance session (singleton).
     *
     * @var Session\Payload
     */
    public static $instance;

    /**
     * Berisi list registrar driver pihak ketiga.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Buat payload session dan muat sessionnya.
     */
    public static function load()
    {
        static::start(Config::get('session.driver'));
        static::$instance->load(Cookie::get(Config::get('session.cookie')));
    }

    /**
     * Buat instance payload session baru.
     *
     * @param string $driver
     */
    public static function start($driver)
    {
        static::$instance = new Session\Payload(static::factory($driver));
    }

    /**
     * Buat instance driver session baru.
     *
     * @param string $driver
     *
     * @return Session\Drivers\Driver
     */
    public static function factory($driver)
    {
        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver];
            return $resolver();
        }

        switch ($driver) {
            case 'apc':       return new Session\Drivers\APC(Cache::driver('apc'));
            case 'cookie':    return new Session\Drivers\Cookie();
            case 'database':  return new Session\Drivers\Database(Database::connection());
            case 'file':      return new Session\Drivers\File(path('storage').'sessions'.DS);
            case 'memcached': return new Session\Drivers\Memcached(Cache::driver('memcached'));
            case 'memory':    return new Session\Drivers\Memory();
            case 'redis':     return new Session\Drivers\Redis(Cache::driver('redis'));
            default:          throw new \Exception(sprintf('Unsupported session driver: %s', $driver));
        }
    }

    /**
     * Ambil instance payload session yang sedang aktif.
     *
     * <code>
     *
     *      // Ambil instance session lalu ambil sebuah item
     *      Session::instance()->get('name');
     *
     *      // Ambil instance session lalu taruh sebuah item kedalam session
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
     * Cek apakah session sudah dimulai atau belum.
     *
     * @return bool
     */
    public static function started()
    {
        return ! is_null(static::$instance);
    }

    /**
     * Daftarkan sebuah driver session pihak ketiga.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic Method untuk memanggil method milik instance session secara statis.
     *
     * <code>
     *
     *      // Ambil item dari session
     *      $value = Session::get('name');
     *
     *      // Taruh item ke session (cara 1)
     *      $value = Session::put('name', 'Budi');
     *
     *      // Taruh item ke session (cara 2)
     *      $value = Session::instance()->put('name', 'Budi');
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::instance(), $method], $parameters);
    }
}
