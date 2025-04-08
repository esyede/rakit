<?php

namespace System;

defined('DS') or die('No direct access.');

class Config
{
    /**
     * Berisi semua item konfigurasi.
     * Array konfigurasi diberi key berdsarkan paket dan file pemiliknya.
     *
     * @var array
     */
    public static $items = [];

    /**
     * Berisi cache hasil parsing item konfigurasi.
     *
     * @var array
     */
    public static $cache = [];

    /**
     * Nama event untuk config loader.
     *
     * @var string
     */
    const LOADER = 'rakit.config.loader';

    /**
     * Periksa apakah item konfigurasi ada atau tidak.
     *
     * <code>
     *
     *      // Periksa apakah file config bernama 'session.php' ada
     *      $exists = Config::has('session');
     *
     *      // Cek apakah opsi 'timezone' ada di file konfigurasi 'application.php'
     *      $exists = Config::has('application.timezone');
     *
     * </code>
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has($key)
    {
        return !is_null(static::get($key));
    }

    /**
     * Ambil item konfigurasi.
     *
     * <code>
     *
     *      // Ambil config milik 'session.php'
     *      $session = Config::get('session');
     *
     *      // Ambil item 'first' di file config 'names.php' milik paket 'admin'
     *      $name = Config::get('admin::names.first');
     *
     *      // Ambil item 'timezone' di file config 'application.php'
     *      $timezone = Config::get('application.timezone');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        list($package, $file, $item) = static::parse($key);

        if (!static::load($package, $file)) {
            return value($default);
        }

        $items = static::$items[$package][$file];
        return is_null($item) ? $items : Arr::get($items, $item, $default);
    }

    /**
     * Ambil seluruh item konfigurasi.
     *
     * @return array
     */
    public static function all()
    {
        return static::$items;
    }

    /**
     * Set item konfigurasi.
     *
     * <code>
     *
     *      // Set array konfigurasi 'session'
     *      Config::set('session', $new_value);
     *
     *      // Set item konfigurasi milik paket 'admin'
     *      Config::set('admin::names.first', 'Budi');
     *
     *      // Set item 'timezone' milik file config 'application.php'
     *      Config::set('application.timezone', 'UTC');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function set($key, $value)
    {
        list($package, $file, $item) = static::parse($key);

        static::load($package, $file);

        if (is_null($item)) {
            Arr::set(static::$items[$package], $file, $value);
        } else {
            Arr::set(static::$items[$package][$file], $item, $value);
        }
    }

    /**
     * Parse sebuah key dan return paket, file, dan segmen keynya.
     * Item konfiguasi dinamai menggunakan konvensi [nama paket]::[nama file].[nama item].
     *
     * @param string $key
     *
     * @return array
     */
    protected static function parse($key)
    {
        if (!array_key_exists($key, static::$cache)) {
            $package = Package::name($key);
            $items = explode('.', Package::element($key));
            $data = (is_array($items) && count($items) >= 2) ? implode('.', array_slice($items, 1)) : null;
            static::$cache[$key] = [$package, $items[0], $data];
        }

        return static::$cache[$key];
    }

    /**
     * Muat semua item dari sebuah file konfigurasi.
     *
     * @param string $package
     * @param string $file
     *
     * @return bool
     */
    public static function load($package, $file)
    {
        if (!isset(static::$items[$package][$file])) {
            $config = Event::first(static::LOADER, [$package, $file]);

            if (is_array($config) && count($config) > 0) {
                static::$items[$package][$file] = $config;
            }
        }

        return isset(static::$items[$package][$file]);
    }

    /**
     * Muat item konfigurasi milik sebuah file.
     *
     * @param string $package
     * @param string $file
     *
     * @return array
     */
    public static function file($package, $file)
	{
        $config = [];
        $env = Request::env();
        $paths = [Package::path($package) . 'config' . DS];

		if (!empty($env)) {
			$paths[] = $paths[count($paths) - 1] . $env . DS;
		}

		foreach ($paths as $path) {
            if (!empty($path) && is_file($path = $path . $file . '.php')) {
                $config = array_merge($config, (array) require $path);
            }
		}

		return $config;
	}
}
