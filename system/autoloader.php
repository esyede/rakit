<?php

namespace System;

defined('DS') or exit('No direct access.');

class Autoloader
{
    /**
     * Berisi mapping nama kelas dan path filenya.
     *
     * @var array
     */
    public static $mappings = [];

    /**
     * Berisi direktori yang menggunakan konvensi PSR-0.
     *
     * @var array
     */
    public static $directories = [];

    /**
     * Berisi mapping namespace dan path direktorinya.
     *
     * @var array
     */
    public static $namespaces = [];

    /**
     * Berisi mapping library dan direktori yang menggunakan konvensi 'Garis_bawah'.
     *
     * @var array
     */
    public static $underscored = [];

    /**
     * Berisi seluruh class alias yang didaftarkan ke autoloader.
     *
     * @var array
     */
    public static $aliases = [];

    /**
     * Cache untuk file yang sudah dimuat.
     *
     * @var array
     */
    protected static $loaded = [];



    /**
     * Cache untuk hasil is_file.
     *
     * @var array
     */
    protected static $caches = [];

    /**
     * Muat file berdasarkan class yang diberikan.
     * Method ini adalah autoloader default sistem.
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

            // Jika directories belum didaftarkan, daftar default
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
     * Muat class bernamespace dari direktori yang diberikan.
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
     * Coba resolve class menggunakan konvensi PSR-0.
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

            // Cache is_file untuk lowercase
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

            // Cache is_file untuk original
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
     * Daftarkan array class ke path map.
     *
     * @param array $mappings
     */
    public static function map(array $mappings)
    {
        static::$mappings = array_merge(static::$mappings, $mappings);
    }

    /**
     * Daftarkan class alias dengan autoloader.
     *
     * @param string $class
     * @param string $alias
     */
    public static function alias($class, $alias)
    {
        static::$aliases[$alias] = $class;
    }

    /**
     * Daftarkan direktori untuk di-autoload dengan konvensi PSR-0.
     *
     * @param array $directories
     */
    public static function directories(array $directories)
    {
        $directories = array_merge(static::$directories, static::format($directories));
        static::$directories = array_unique($directories);
    }

    /**
     * Map namespace ke direktori.
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
     * Daftarkan "namespace garis bawah" ke mapping direktori.
     *
     * @param array $mappings
     */
    public static function underscored(array $mappings)
    {
        static::namespaces($mappings, '_');
    }

    /**
     * Format array namespace ke direktori mapping.
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
     * Format directory-separator agar sesuai dengan OS di server.
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
     * Get statistics autoloader untuk debugging.
     *
     * @return array
     */
    public static function get_stats()
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
