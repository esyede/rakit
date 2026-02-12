<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Routing\Router;

class Package
{
    /**
     * Contains list of packages.
     *
     * @var array
     */
    public static $packages = [];

    /**
     * Contains cached elements after parsing.
     *
     * @var array
     */
    public static $elements = [];

    /**
     * Contains list of packages that have been booted.
     *
     * @var array
     */
    public static $booted = [];

    /**
     * Contains list of packages that have routes.
     *
     * @var array
     */
    public static $routed = [];

    /**
     * Register a package.
     *
     * @param string       $package
     * @param string|array $config
     */
    public static function register($package, $config = [])
    {
        $defaults = ['handles' => null, 'autoboot' => false];

        if (is_string($config)) {
            $package = $config;
            $config = ['location' => $package];
        }

        if (!isset($config['location'])) {
            $config['location'] = $package;
        }

        static::$packages[$package] = array_merge($defaults, $config);

        if (isset($config['autoloads'])) {
            static::autoloads($package, $config);
        }
    }

    /**
     * Load a package by name.
     *
     * @param string $package
     */
    public static function boot($package)
    {
        if (static::booted($package)) {
            return;
        }

        if (!static::exists($package)) {
            throw new \Exception(sprintf('Package has not been installed: %s', $package));
        }

        if (($boot = static::option($package, 'boot')) instanceof \Closure) {
            call_user_func($boot);
        } elseif (is_file($path = static::path($package) . 'boot.php')) {
            require $path;
        }

        static::routes($package);

        Event::fire('rakit.booted: ' . $package);

        static::$booted[] = strtolower($package);
    }

    /**
     * Load the route file for the given package.
     *
     * @param string $package
     */
    public static function routes($package)
    {
        if (static::routed($package)) {
            return;
        }

        $directory = static::path($package);

        Router::$package = static::option($package, 'handles');

        if (!static::routed($package) && is_file($directory . 'routes.php')) {
            static::$routed[] = $package;
            require $directory . 'routes.php';

            // Load event, middleware and view composer files.
            array_map(function ($file) use ($directory) {
                if (is_file($directory . $file)) {
                    require $directory . $file;
                }
            }, ['events.php', 'middlewares.php', 'composers.php']);
        }
    }

    /**
     * Register the autoload configuration for the given package.
     *
     * @param string $package
     * @param array  $config
     */
    protected static function autoloads($package, array $config)
    {
        $path = rtrim(Package::path($package), DS);

        foreach ($config['autoloads'] as $type => $mappings) {
            $mappings = array_map(function ($mapping) use ($path) {
                return str_replace('(:package)', $path, $mapping);
            }, $mappings);

            Autoloader::{$type}($mappings);
        }
    }

    /**
     * Freeze (disable) a package so it cannot be booted.
     *
     * @param string $package
     */
    public static function freeze($package)
    {
        unset(static::$packages[$package]);
    }

    /**
     * Determine which package should handle the given URI.
     * The default package (application) will be returned if no other package is assigned.
     *
     * @param string $uri
     *
     * @return string
     */
    public static function handles($uri)
    {
        $uri = rtrim($uri, '/') . '/';

        foreach (static::$packages as $key => $value) {
            if (
                isset($value['handles'])
                && Str::starts_with($uri, $value['handles'] . '/')
                || '/' === $value['handles']
            ) {
                return $key;
            }
        }

        return DEFAULT_PACKAGE;
    }

    /**
     * Check if a package exists.
     *
     * @param string $package
     *
     * @return bool
     */
    public static function exists($package)
    {
        return DEFAULT_PACKAGE === $package || in_array(strtolower((string) $package), static::names());
    }

    /**
     * Check if a package has been booted for the current request.
     *
     * @param string $package
     */
    public static function booted($package)
    {
        return in_array(strtolower((string) $package), static::$booted);
    }

    /**
     * Check if a package has been routed for the current request.
     *
     * @param string $package
     */
    public static function routed($package)
    {
        return in_array(strtolower((string) $package), static::$routed);
    }

    /**
     * Get the prefix identifier for the given package.
     *
     * @param string $package
     *
     * @return string
     */
    public static function prefix($package)
    {
        return (DEFAULT_PACKAGE === $package) ? '' : $package . '::';
    }

    /**
     * Get the class prefix for the given package.
     *
     * @param string $package
     *
     * @return string
     */
    public static function class_prefix($package)
    {
        return (DEFAULT_PACKAGE === $package) ? '' : Str::classify($package) . '_';
    }

    /**
     * Return the path to the given package.
     *
     * <code>
     *
     *      // Return path to the 'admin' package
     *      $path = Package::path('admin');
     *
     *      // Return path to the 'application' package
     *      $path = Package::path('application');
     *
     * </code>
     *
     * @param string $package
     *
     * @return string
     */
    public static function path($package)
    {
        if (is_null($package) || DEFAULT_PACKAGE === $package) {
            return path('app');
        } elseif ($location = (string) Arr::get(static::$packages, $package . '.location')) {
            if (0 === strpos($location, 'path: ')) {
                return Str::finish(substr($location, 6), DS);
            }

            return Str::finish(path('package') . $location, DS);
        }
    }

    /**
     * Return the root path to the assets for the given package.
     *
     * @param string $package
     *
     * @return string
     */
    public static function assets($package)
    {
        return (is_null($package) || DEFAULT_PACKAGE === $package) ? '/' : '/packages/' . $package . '/';
    }

    /**
     * Get the name of the package based on the given identifier.
     *
     * <code>
     *
     *      // Returns 'admin' as the package name for the identifier
     *      $package = Package::name('admin::home.index');
     *
     * </code>
     *
     * @param string $identifier
     *
     * @return string
     */
    public static function name($identifier)
    {
        list($package, $element) = static::parse($identifier);
        return $package;
    }

    /**
     * Get the name of the element from the given identifier.
     *
     * <code>
     *
     *      // Returns "home.index" as the element name for the identifier
     *      $package = Package::element('admin::home.index');
     *
     * </code>
     *
     * @param string $identifier
     *
     * @return string
     */
    public static function element($identifier)
    {
        list($package, $element) = static::parse($identifier);
        return $element;
    }

    /**
     * Return the identifier of the package and element.
     *
     * <code>
     *
     *      // Retuns 'admin::home.index'
     *      $identifier = Package::identifier('admin', 'home.index');
     *
     *      // Retuns 'home.index'
     *      $identifier = Package::identifier('application', 'home.index');
     *
     * </code>
     *
     * @param string $package
     * @param string $element
     *
     * @return string
     */
    public static function identifier($package, $element)
    {
        return (is_null($package) || DEFAULT_PACKAGE === $package) ? $element : $package . '::' . $element;
    }

    /**
     * Return the name of the package.
     *
     * @param string $package
     *
     * @return string
     */
    public static function resolve($package)
    {
        return static::exists($package) ? $package : DEFAULT_PACKAGE;
    }

    /**
     * Parse identifier element and return package name and element.
     *
     * <code>
     *
     *      // Returns array [null, 'admin.user']
     *      $element = Package::parse('admin.user');
     *
     *      // Returns an array ['admin', 'user']
     *      $element = Package::parse('admin::user');
     *
     * </code>
     *
     * @param string $identifier
     *
     * @return array
     */
    public static function parse($identifier)
    {
        $identifier = (string) $identifier;

        if (!isset(static::$elements[$identifier])) {
            static::$elements[$identifier] = (false !== strpos($identifier, '::'))
                ? explode('::', strtolower($identifier))
                : [DEFAULT_PACKAGE, strtolower($identifier)];
        }

        return static::$elements[$identifier];
    }

    /**
     * Get the package information.
     *
     * @param string $package
     *
     * @return object
     */
    public static function get($package)
    {
        return Arr::get(static::$packages, $package);
    }

    /**
     * Get the package options.
     *
     * @param string $package
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function option($package, $option, $default = null)
    {
        $package = static::get($package);
        return is_null($package) ? value($default) : Arr::get($package, $option, $default);
    }

    /**
     * Get the list of installed package information.
     *
     * @return array
     */
    public static function all()
    {
        return static::$packages;
    }

    /**
     * Get the list of installed package names.
     *
     * @return array
     */
    public static function names()
    {
        return array_keys(static::$packages);
    }

    /**
     * Expand the path of a package.
     *
     * @param string $path
     *
     * @return string
     */
    public static function expand($path)
    {
        list($package, $element) = static::parse($path);
        return static::path($package) . $element;
    }
}
