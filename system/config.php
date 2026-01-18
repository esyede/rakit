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
     * Cache untuk hasil loading file konfigurasi.
     *
     * @var array
     */
    public static $files = [];

    /**
     * Cache untuk hasil Config::get().
     *
     * @var array
     */
    public static $gets = [];

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

        if (isset(static::$gets[$key])) {
            $cached = static::$gets[$key];
            $path = Package::path($package) . 'config' . DS . $file . '.php';

            if (is_file($path) && filemtime($path) > $cached['mtime']) {
                foreach (static::$gets as $k => $v) {
                    list($pkg, $node, $unused) = static::parse($k);

                    if ($pkg === $package && $node === $file) {
                        unset(static::$gets[$k]);
                    }
                }
            } else {
                return $cached['value'];
            }
        }

        if (!static::load($package, $file)) {
            return value($default);
        }

        $items = static::$items[$package][$file];
        $result = is_null($item) ? $items : Arr::get($items, $item, $default);
        $path = Package::path($package) . 'config' . DS . $file . '.php';

        static::$gets[$key] = ['value' => $result, 'mtime' => is_file($path) ? filemtime($path) : 0];
        return $result;
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

        // Invalidate cache for all keys in the same package and file
        foreach (static::$gets as $k => $v) {
            list($pkg, $node, $unused) = static::parse($k);

            if ($pkg === $package && $node === $file) {
                unset(static::$gets[$k]);
            }
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
        if (
            strpos($package, '..') !== false || strpos($package, '/') !== false || strpos($package, '\\') !== false ||
            strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false
        ) {
            return [];
        }

        $key = $package . '::' . $file;

        if (isset(static::$files[$key])) {
            $cached = static::$files[$key];
            $env = Request::env();
            $paths = [Package::path($package) . 'config' . DS];

            if (!empty($env)) {
                $paths[] = $paths[count($paths) - 1] . $env . DS;
            }

            $latest = 0;

            foreach ($paths as $path) {
                if (!empty($path) && is_file($filePath = $path . $file . '.php')) {
                    $mtime = filemtime($filePath);
                    $latest = ($mtime > $latest) ? $mtime : $latest;
                }
            }

            if ($latest <= $cached['mtime']) {
                return $cached['data'];
            }
        }

        $config = [];
        $env = Request::env();
        $paths = [Package::path($package) . 'config' . DS];

        if (!empty($env)) {
            $paths[] = $paths[count($paths) - 1] . $env . DS;
        }

        $latest = 0;
        foreach ($paths as $path) {
            if (!empty($path) && is_file($path = $path . $file . '.php')) {
                try {
                    $loaded = require $path;
                    $config = array_merge($config, (array) $loaded);
                    $mtime = filemtime($path);
                    $latest = ($mtime > $latest) ? $mtime : $latest;
                } catch (\Throwable $e) {
                    return [];
                } catch (\Exception $e) {
                    return [];
                }
            }
        }

        static::$files[$key] = ['data' => $config, 'mtime' => $latest];
        return $config;
    }
}
