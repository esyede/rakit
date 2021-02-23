<?php

namespace System;

defined('DS') or exit('No direct script access.');

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
     * Muat file berdasarkan class yang diberikan.
     * Method ini adalah autoloader default sistem.
     *
     * @param string $class
     */
    public static function load($class)
    {
        if (isset(static::$aliases[$class])) {
            return class_alias(static::$aliases[$class], $class);
        } elseif (isset(static::$mappings[$class])) {
            require static::$mappings[$class];
            return;
        }

        foreach (static::$namespaces as $namespace => $directory) {
            if ('' !== $namespace && $namespace === substr($class, 0, strlen($namespace))) {
                return static::load_namespaced($class, $namespace, $directory);
            }
        }

        static::load_psr($class);
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
        return static::load_psr(substr($class, strlen($namespace)), $directory);
    }

    /**
     * Coba resolve class menggunakan konvensi PSR-0.
     *
     * @param string $class
     * @param string $directory
     */
    protected static function load_psr($class, $directory = null)
    {
        $file = str_replace(['\\', '_', '/'], DS, $class);
        $lowercased = strtolower($file);

        $directories = $directory ? $directory : static::$directories;
        $directories = (array) $directories;

        foreach ($directories as $directory) {
            if (is_file($path = $directory.$lowercased.'.php')) {
                return require $path;
            } elseif (is_file($path = $directory.$file.'.php')) {
                return require $path;
            }
        }
    }

    /**
     * Daftarkan array class ke path map.
     *
     * @param array $mappings
     */
    public static function map($mappings)
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
     * @param string|array $directory
     */
    public static function directories($directory)
    {
        $directories = static::format($directory);
        $directories = array_merge(static::$directories, $directories);

        static::$directories = array_unique($directories);
    }

    /**
     * Map namespace ke direktori.
     *
     * @param array  $mappings
     * @param string $append
     */
    public static function namespaces($mappings, $append = '\\')
    {
        $mappings = static::format_mappings($mappings, $append);
        static::$namespaces = array_merge($mappings, static::$namespaces);
    }

    /**
     * Daftarkan "namespace garis bawah" ke mapping direktori.
     *
     * @param array $mappings
     */
    public static function underscored($mappings)
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
    protected static function format_mappings($mappings, $append)
    {
        foreach ($mappings as $namespace => $directory) {
            $namespace = trim($namespace, $append).$append;
            unset(static::$namespaces[$namespace]);
            $namespaces[$namespace] = head(static::format($directory));
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
    protected static function format($directories)
    {
        $directories = (array) $directories;

        return array_map(function ($directory) {
            return rtrim($directory, DS).DS;
        }, $directories);
    }
}
