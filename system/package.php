<?php

namespace System;

defined('DS') or exit('No direct script access.');

use System\Routing\Router;

class Package
{
    /**
     * Berisi list paket milik aplikasi.
     *
     * @var array
     */
    public static $packages = [];

    /**
     * Berisi cache elemen-elemen paket setelah diparsing.
     *
     * @var array
     */
    public static $elements = [];

    /**
     * Berisi list paket yang telah di-boot.
     *
     * @var array
     */
    public static $booted = [];

    /**
     * Berisi list paket yang membawa file routes.php tersendiri.
     *
     * @var array
     */
    public static $routed = [];

    /**
     * Daftarkan paket ke aplikasi.
     *
     * @param string $package
     * @param array  $config
     */
    public static function register($package, $config = [])
    {
        $defaults = ['handles' => null, 'autoboot' => false];

        if (is_string($config)) {
            $package = $config;
            $config = ['location' => $package];
        }

        if (! isset($config['location'])) {
            $config['location'] = $package;
        }

        static::$packages[$package] = array_merge($defaults, $config);

        if (isset($config['autoloads'])) {
            static::autoloads($package, $config);
        }
    }

    /**
     * Muat paket dengan menjalankan file boot-up miliknya (file 'boot.php')
     * Jika sebelumnya si paket sudah di-boot, langkah ini akan di-skip.
     *
     * @param string $package
     */
    public static function boot($package)
    {
        if (static::booted($package)) {
            return;
        }

        if (! static::exists($package)) {
            throw new \Exception(sprintf('Package has not been installed: %s', $package));
        }

        if (($boot = static::option($package, 'boot')) instanceof \Closure) {
            call_user_func($boot);
        } elseif (is_file($path = static::path($package).'boot.php')) {
            require $path;
        }

        static::routes($package);

        Event::fire('rakit.booted: '.$package);

        static::$booted[] = strtolower($package);
    }

    /**
     * Muat file routes untuk paket yang diberikan.
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

        if (! static::routed($package) && is_file($directory.'routes.php')) {
            static::$routed[] = $package;
            require $directory.'routes.php';

            // Muat file event, middleware dan view composer.
            $files = ['events.php', 'middlewares.php', 'composers.php'];
            array_map(function ($file) use ($directory) {
                if (is_file($directory.$file)) {
                    require $directory.$file;
                }
            }, $files);
        }
    }

    /**
     * Daftarkan konfigurasi autoloading untuk paket yang diberikan.
     *
     * @param string $package
     * @param array  $config
     */
    protected static function autoloads($package, $config)
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
     * Bekukan sebuah paket agar tidak bisa di-boot.
     *
     * @param string $package
     */
    public static function freeze($package)
    {
        unset(static::$packages[$package]);
    }

    /**
     * Tentukan paket apa yang harus menangani URI yang diberikan.
     * Package default (application) akan direturn jika belum ada paket lain yang ditugaskan.
     *
     * @param string $uri
     *
     * @return string
     */
    public static function handles($uri)
    {
        $uri = rtrim($uri, '/').'/';

        foreach (static::$packages as $key => $value) {
            if (isset($value['handles'])
            && Str::starts_with($uri, $value['handles'].'/')
            || '/' === $value['handles']) {
                return $key;
            }
        }

        return DEFAULT_PACKAGE;
    }

    /**
     * Cek ada atau tidaknya suatu paket didalam direktori packages/.
     *
     * @param string $package
     *
     * @return bool
     */
    public static function exists($package)
    {
        return (DEFAULT_PACKAGE === $package || in_array(strtolower($package), static::names()));
    }

    /**
     * Cek apakah paket sudah di-boot atau belum untuk request saat ini.
     *
     * @param string $package
     */
    public static function booted($package)
    {
        return in_array(strtolower($package), static::$booted);
    }

    /**
     * Cek apakah file routes milik paket yang diberikan sudah dimuat atau belum.
     *
     * @param string $package
     */
    public static function routed($package)
    {
        return in_array(strtolower($package), static::$routed);
    }

    /**
     * Ambil prefix identifier untuk paket yang diberikan.
     *
     * @param string $package
     *
     * @return string
     */
    public static function prefix($package)
    {
        return (DEFAULT_PACKAGE === $package) ? '' : $package.'::';
    }

    /**
     * Ambil prefix kelas untuk paket yang diberikan.
     *
     * @param string $package
     *
     * @return string
     */
    public static function class_prefix($package)
    {
        return (DEFAULT_PACKAGE === $package) ? '' : Str::classify($package).'_';
    }

    /**
     * Mereturn path ke paket yang diberikan.
     *
     * <code>
     *
     *      // Mereturn path ke paket yang diberikan 'admin'
     *      $path = Package::path('admin');
     *
     *      // Mereturn konstanta path('app') sebagai paket default
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
        } elseif ($location = Arr::get(static::$packages, $package.'.location')) {
            if (Str::starts_with($location, 'path: ')) {
                return Str::finish(substr($location, 6), DS);
            }

            return Str::finish(path('package').$location, DS);
        }
    }

    /**
     * Mereturn root path aset untuk paket yang diberikan.
     *
     * @param string $package
     *
     * @return string
     */
    public static function assets($package)
    {
        return (is_null($package) || DEFAULT_PACKAGE === $package) ? '/' : '/packages/'.$package.'/';
    }

    /**
     * Ambil nama paket berdasarkan identifier yang diberikan.
     *
     * <code>
     *
     *      // Mereturn 'admin' sebagai nama paket untuk identifier
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
     * Ambil nama elemen dari identifier yang diberikan.
     *
     * <code>
     *
     *      // Returns "home.index" as the element name for the identifier
     *      $package = Package::package('admin::home.index');
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
     * Rekonstruksi identifier dari sebuah paket dan elemen yang diberikan.
     *
     * <code>
     *
     *      // Mereturn 'admin::home.index'
     *      $identifier = Package::identifier('admin', 'home.index');
     *
     *      // Mereturn 'home.index'
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
        return (is_null($package) || DEFAULT_PACKAGE === $package) ? $element : $package.'::'.$element;
    }

    /**
     * Mereturn nama paket jika paketnya ada, mereturn default paket jika tidak ada.
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
     * Parse identifier elemen dan return nama paket dan elemennya.
     *
     * <code>
     *
     *      // Mereturn array [null, 'admin.user']
     *      $element = Package::parse('admin.user');
     *
     *      // Memparsing 'admin::user' menjadi array ['admin', 'user']
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
        if (! isset(static::$elements[$identifier])) {
            if (false !== strpos($identifier, '::')) {
                $element = explode('::', strtolower($identifier));
            } else {
                $element = [DEFAULT_PACKAGE, strtolower($identifier)];
            }

            static::$elements[$identifier] = $element;
        }

        return static::$elements[$identifier];
    }

    /**
     * Ambil informasi sebuah paket.
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
     * Ambil opsi sebuah paket.
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
     * Ambil list informasi paket yang terinstall.
     *
     * @return array
     */
    public static function all()
    {
        return static::$packages;
    }

    /**
     * Ambil list nama paket yang terinstall.
     *
     * @return array
     */
    public static function names()
    {
        return array_keys(static::$packages);
    }

    /**
     * Expand path paket.
     *
     * @param string $path
     *
     * @return string
     */
    public static function expand($path)
    {
        list($package, $element) = static::parse($path);
        return static::path($package).$element;
    }
}
