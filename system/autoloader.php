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
     * Class name suffixes mapped to their directory: User_Observer lives in
     * observers/user.php, User_Controller in controllers/user.php.
     *
     * @var array
     */
    public static $suffixes = [
        '_Observer' => 'observers',
        '_Transformer' => 'transformers',
    ];

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
     * How many path probes may be remembered before the cache is dropped.
     *
     * @var int
     */
    public static $limit = 10000;

    /**
     * Load a file based on the given class. Failures propagate deliberately.
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

        if (static::load_suffixed($class)) {
            return;
        }

        if (empty(static::$directories)) {
            $app = path('app');
            static::directories([
                $app . 'controllers',
                $app . 'models',
                $app . 'libraries',
                $app . 'commands',
                $app . 'jobs',
                $app . 'components',
            ]);
        }

        foreach (static::$namespaces as $namespace => $directory) {
            if ('' !== $namespace && $namespace === substr((string) $class, 0, strlen((string) $namespace))) {
                return static::load_namespaced($class, $namespace, $directory);
            }
        }

        static::load_psr($class);
    }

    /**
     * Load a suffixed class from the application or its package directory.
     * PSR-0 cannot: it would look for observers/user/observer.php, not observers/user.php.
     *
     * @param string $class
     *
     * @return bool
     */
    protected static function load_suffixed($class)
    {
        $class = strtolower((string) $class);

        if (strpbrk($class, '\\/.') !== false) {
            return false;
        }

        foreach (static::$suffixes as $suffix => $directory) {
            $suffix = strtolower($suffix);
            $length = strlen($suffix);

            if (strlen($class) <= $length || substr($class, -$length) !== $suffix) {
                continue;
            }

            $name = substr($class, 0, -$length);

            foreach (array_merge([DEFAULT_PACKAGE], Package::names()) as $package) {
                $prefix = strtolower(Package::class_prefix($package));

                if ('' !== $prefix && (0 !== strpos($name, $prefix) || strlen($name) <= strlen($prefix))) {
                    continue;
                }

                $file = Package::path($package) . $directory . DS . substr($name, strlen($prefix)) . '.php';

                if (is_file($file)) {
                    require_once $file;
                    return true;
                }
            }
        }

        return false;
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

        $directories = $directory ? array_map(function ($item) {
            return str_replace(['\\', '/'], DS, (string) $item);
        }, (array) $directory) : static::$directories;

        foreach ($directories as $folder) {
            foreach ([$folder . $lowercased . '.php', $folder . $file . '.php'] as $path) {
                if (! isset(static::$caches[$path])) {
                    static::remember($path, is_file($path));
                }

                if (! static::$caches[$path]) {
                    continue;
                }

                // Keyed by resolved file: two namespaces may share a short class name.
                if (! isset(static::$loaded[$path])) {
                    static::$loaded[$path] = true;
                    require $path;
                }

                return;
            }
        }
    }

    /**
     * Record whether a candidate path exists, clearing the cache once it is full.
     *
     * @param string $path
     * @param bool   $exists
     */
    protected static function remember($path, $exists)
    {
        if (static::$limit > 0 && count(static::$caches) >= static::$limit) {
            static::$caches = [];
        }

        static::$caches[$path] = $exists;
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
     * Register a batch of class aliases. Detects conflicts with built-in PHP classes.
     *
     * @param array $aliases
     */
    public static function aliases(array $aliases)
    {
        $conflicts = [];

        foreach (array_keys($aliases) as $alias) {
            if (class_exists($alias, false) || interface_exists($alias, false) || trait_exists($alias, false)) {
                $reflection = new \ReflectionClass($alias);

                if ($reflection->isInternal()) {
                    $conflicts[$alias] = $reflection->getExtensionName() ?: 'core';
                    unset($aliases[$alias]);
                }
            }
        }

        static::$aliases = array_merge(static::$aliases, $aliases);

        if (! empty($conflicts)) {
            $lines = [];

            foreach ($conflicts as $alias => $extension) {
                $lines[] = sprintf('"%s" (conflicts with extension "%s")', $alias, $extension);
            }

            $message = '[' . date('Y-m-d H:i:s') . '] unknown.EMERGENCY: '
                . '[Rakit] Class alias(es) skipped because they collide with built-in PHP classes: '
                . implode(', ', $lines)
                . '. PHP loads built-in classes before any userland autoloader runs, so these '
                . 'names cannot be aliased. Disable the conflicting extension or rename the alias '
                . 'in application/config/aliases.php. The fully-qualified target class remains '
                . 'available (e.g. via "use Vendor\\Namespace\\Target;").';

            if (defined('STDERR')) {
                fwrite(STDERR, $message . PHP_EOL);
            } else {
                try {
                    $path = path('storage') . 'logs' . DS . 'rakit.log.php';

                    // Spelled out instead of using the log driver: aliases still registering.
                    $guard = is_file($path) ? '' : "<?php defined('DS') or exit('No direct access.');?>" . PHP_EOL;

                    @file_put_contents($path, $guard . $message . PHP_EOL, LOCK_EX | (is_file($path) ? FILE_APPEND : 0));
                } catch (\Throwable $ex) {
                    error_log($message);
                } catch (\Exception $ex) {
                    error_log($message);
                }
            }
        }
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

        // Longest prefix first, so 'Foo\' cannot shadow 'Foo\Bar\'.
        uksort(static::$namespaces, function ($left, $right) {
            return mb_strlen((string) $right, '8bit') - mb_strlen((string) $left, '8bit');
        });
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
     * Format directory separators to match the OS.
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
