<?php

namespace System;

defined('DS') or exit('No direct access.');

class Autoloader
{
    /**
     * Contains class name and file path mappings.
     *
     * @var array
     */
    public static $mappings = [];

    /**
     * Contains directories using PSR-0 convention.
     *
     * @var array
     */
    public static $directories = [];

    /**
     * Contains namespaces and directory mappings.
     *
     * @var array
     */
    public static $namespaces = [];

    /**
     * Contains library and directory mappings using 'underscore' convention.
     *
     * @var array
     */
    public static $underscored = [];

    /**
     * Contains class aliases.
     *
     * @var array
     */
    public static $aliases = [];

    /**
     * Cache for loaded files.
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * Cache for file existence checks.
     *
     * @var array
     */
    protected static $caches = [];

    /**
     * Load a file based on the given class.
     * This method is the default autoloader.
     *
     * @param string $class
     */
    public static function load($class)
    {
        try {
            if (isset(static::$aliases[$class])) {
                return class_alias(static::$aliases[$class], $class);
            } elseif (isset(static::$mappings[$class])) {
                require static::$mappings[$class];
                return;
            }

            // If directories are not registered, register defaults
            if (empty(static::$directories)) {
                static::directories([
                    path('app') . 'controllers',
                    path('app') . 'models',
                    path('app') . 'libraries',
                    path('app') . 'commands',
                    path('app') . 'jobs',
                ]);
            }

            foreach (static::$namespaces as $namespace => $directory) {
                if ('' !== $namespace && $namespace === substr((string) $class, 0, strlen((string) $namespace))) {
                    return static::load_namespaced($class, $namespace, $directory);
                }
            }

            static::load_psr($class);
        } catch (\Throwable $e) {
            return;
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Load a namespace-based class.
     *
     * @param string $class
     * @param string $namespace
     * @param string $directory
     */
    protected static function load_namespaced($class, $namespace, $directory)
    {
        return static::load_psr(substr((string) $class, strlen((string) $namespace)), $directory);
    }

    /**
     * Resolve a class using PSR-0 conventions.
     *
     * @param string $class
     * @param string $directory
     */
    protected static function load_psr($class, $directory = null)
    {
        $file = str_replace(['\\', '_', '/'], DS, (string) $class);
        $lowercased = strtolower($file);

        if (strpos($file, '..') !== false || strpos($file, '/') === 0 || strpos($file, '\\') === 0) {
            return;
        }

        if (isset(static::$loaded[$file]) || isset(static::$loaded[$lowercased])) {
            return;
        }

        $directories = $directory ? array_map(function ($item) {
            return str_replace(['\\', '/'], DS, (string) $item);
        }, (array) $directory) : static::$directories;

        foreach ($directories as $directory) {
            $lowercase_path = $directory . $lowercased . '.php';
            $original_path = $directory . $file . '.php';

            if (!isset(static::$caches[$lowercase_path])) {
                static::$caches[$lowercase_path] = is_file($lowercase_path);
            }

            if (static::$caches[$lowercase_path]) {
                try {
                    require $lowercase_path;
                    static::$loaded[$lowercased] = $lowercase_path;
                    return;
                } catch (\Throwable $e) {
                    return;
                } catch (\Exception $e) {
                    return;
                }
            }

            if (!isset(static::$caches[$original_path])) {
                static::$caches[$original_path] = is_file($original_path);
            }

            if (static::$caches[$original_path]) {
                try {
                    require $original_path;
                    static::$loaded[$file] = $original_path;
                    return;
                } catch (\Throwable $e) {
                    return;
                } catch (\Exception $e) {
                    return;
                }
            }
        }
    }

    /**
     * Register array class to path map.
     *
     * @param array $mappings
     */
    public static function map(array $mappings)
    {
        static::$mappings = array_merge(static::$mappings, $mappings);
    }

    /**
     * Register class alias with autoloader.
     *
     * @param string $class
     * @param string $alias
     */
    public static function alias($class, $alias)
    {
        static::$aliases[$alias] = $class;
    }

    /**
     * Register directory for autoload with PSR-0 convention.
     *
     * @param array $directories
     */
    public static function directories(array $directories)
    {
        $directories = array_merge(static::$directories, static::format($directories));
        static::$directories = array_unique($directories);
    }

    /**
     * Map namespace to directory mapping.
     *
     * @param array  $mappings
     * @param string $append
     */
    public static function namespaces(array $mappings, $append = '\\')
    {
        $mappings = static::format_mappings($mappings, $append);
        static::$namespaces = array_merge($mappings, static::$namespaces);
    }

    /**
     * Register "underscore namespace" to directory mapping.
     *
     * @param array $mappings
     */
    public static function underscored(array $mappings)
    {
        static::namespaces($mappings, '_');
    }

    /**
     * Format namespaces to directory mapping.
     *
     * @param array  $mappings
     * @param string $append
     *
     * @return array
     */
    protected static function format_mappings(array $mappings, $append)
    {
        $namespaces = [];

        foreach ($mappings as $namespace => $directory) {
            $namespace = trim($namespace, $append) . $append;
            unset(static::$namespaces[$namespace]);
            $namespaces[$namespace] = head(static::format((array) $directory));
        }

        return $namespaces;
    }

    /**
     * Format directory-separator to match OS.
     * (Windows = \, Linux/Mac = /).
     *
     * @param array $directories
     *
     * @return array
     */
    protected static function format(array $directories)
    {
        return array_map(function ($directory) {
            return rtrim($directory, DS) . DS;
        }, $directories);
    }



    /**
     * Get statistics for debugging.
     *
     * @return array
     */
    public static function stats()
    {
        return [
            'loaded_files' => count(static::$loaded),
            'mappings' => count(static::$mappings),
            'namespaces' => count(static::$namespaces),
            'directories' => count(static::$directories),
            'aliases' => count(static::$aliases),
            'caches' => count(static::$caches),
        ];
    }
}
