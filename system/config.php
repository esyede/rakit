<?php

namespace System;

defined('DS') or die('No direct access.');

class Config
{
    /**
     * Contains all configuration items.
     * Configuration array is keyed by package and owner file.
     *
     * @var array
     */
    public static $items = [];

    /**
     * Contains cached results of parsed configuration items.
     *
     * @var array
     */
    public static $cache = [];

    /**
     * Contains cached results of loaded configuration files.
     *
     * @var array
     */
    public static $files = [];

    /**
     * Contains cached results of Config::get().
     *
     * @var array
     */
    public static $gets = [];

    /**
     * Event name for config loader.
     *
     * @var string
     */
    const LOADER = 'rakit.config.loader';

    /**
     * Check if configuration item exists.
     *
     * <code>
     *
     *      // Check if config file named 'session.php' exists
     *      $exists = Config::has('session');
     *
     *      // Check if 'timezone' option exists in config file 'application.php'
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
     * Get configuration item.
     *
     * <code>
     *
     *      // Get the 'session' config item
     *      $session = Config::get('session');
     *
     *      // Get the 'first' config item from 'names.php' file in the 'admin' package
     *      $name = Config::get('admin::names.first');
     *
     *      // Get the 'timezone' config item from 'application.php' file in the 'application' package
     *      $timezone = Config::get('application::application.timezone');
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
     * Get all configuration items.
     *
     * @return array
     */
    public static function all()
    {
        return static::$items;
    }

    /**
     * Set a configuration item.
     *
     * <code>
     *
     *      // Set array to 'session' configuration file
     *      Config::set('session', $new_value);
     *
     *      // Set configuration item of 'admin' package
     *      Config::set('admin::names.first', 'Budi');
     *
     *      // Set 'timezone' configuration item of 'application' package
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
        foreach (static::$gets as $name => $data) {
            list($pkg, $node, $unused) = static::parse($name);

            if ($pkg === $package && $node === $file) {
                unset(static::$gets[$name]);
            }
        }
    }

    /**
     * Parse a key and return package, file, and key segments.
     * Configuration items are named using this convention: [package name]::[file name].[item name].
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
     * Load all items from a configuration file.
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
     * Load configuration items from a file.
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
