<?php

namespace System;

defined('DS') or exit('No direct script access.');

use System\Routing\Router;
use System\Routing\Route;

class URL
{
    /**
     * Berisi Base URL (diambil dari cache).
     *
     * @var string
     */
    public static $base;

    /**
     * Berisi full URI (termasuk query string).
     *
     * @return string
     */
    public static function full()
    {
        return static::to(URI::full());
    }

    /**
     * Ambil full URL untuk request saat ini.
     *
     * @return string
     */
    public static function current()
    {
        return static::to(URI::current(), false, false);
    }

    /**
     * Ambil URL root aplikasi.
     *
     * @return string
     */
    public static function home()
    {
        $route = Router::find('home');
        return is_null($route) ? static::to('/') : static::to_route('home');
    }

    /**
     * Ambi Base URL aplikasi.
     *
     * @return string
     */
    public static function base()
    {
        if (! isset(static::$base)) {
            static::$base = ('' !== ($base = Config::get('application.url')))
                ? $base
                : Request::foundation()->getRootUrl();
        }

        return static::$base;
    }

    /**
     * Buat URL aplikasi.
     *
     * <code>
     *
     *      // Buat URL ke lokasi didalam lingkup aplikasi
     *      $url = URL::to('user/profile');
     *
     * </code>
     *
     * @param string $url
     * @param bool   $https
     * @param bool   $asset
     * @param bool   $locale
     *
     * @return string
     */
    public static function to($url = '', $asset = false, $locale = true)
    {
        if (static::valid($url) || Str::starts_with($url, '#')) {
            return $url;
        }

        $config = Config::get('application');

        $root = static::base();
        $root .= $asset ? '' : '/'.$config['index'];

        if (! $asset && $locale && count($languages) > 0) {
            if (in_array($config['language'], $config['languages'])) {
                $root = rtrim($root, '/').'/'.$config['language'];
            }
        }

        $root = Request::secure()
            ? Str::replace_first('http://', 'https://', $root)
            : Str::replace_first('https://', 'http://', $root);

        return rtrim($root, '/').'/'.ltrim($url, '/');
    }

    /**
     * Buat URK ke action miik controller.
     *
     * <code>
     *
     *      // Buat URK ke action 'index' miik controller 'user'
     *      $url = URL::to_action('user@index');
     *
     *      // Buat URL ke http://situsku.com/user/profile/budi
     *      $url = URL::to_action('user@profile', ['budi']);
     *
     * </code>
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    public static function to_action($action, $parameters = [])
    {
        $route = Router::uses($action);

        if (is_null($route)) {
            return static::convention($action, $parameters);
        }

        return static::explicit($route, $action, $parameters);
    }

    /**
     * But action URL dari sebuah definisi route.
     *
     * @param array  $route
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    protected static function explicit($route, $action, $parameters)
    {
        return static::to(static::transpose(key($route), $parameters));
    }

    /**
     * Buat action URI berdasarkan konvensi.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    protected static function convention($action, $parameters)
    {
        list($package, $action) = Package::parse($action);

        $package = Package::get($package);
        $root = isset($package['handles']) ? $package['handles'] : '';
        $parameters = implode('/', $parameters);

        $uri = $root.'/'.str_replace(['.', '@'], '/', $action);
        $uri = static::to(Str::finish($uri, '/').$parameters);

        return trim($uri, '/');
    }

    /**
     * Buat URL ke sebuah aset.
     *
     * @param string $url
     *
     * @return string
     */
    public static function to_asset($url)
    {
        if (static::valid($url) || static::valid('http:'.$url)) {
            return $url;
        }

        return static::to('assets/'.ltrim($url, '/'), true);
    }

    /**
     * Buat URL from dari named route.
     *
     * <code>
     *
     *      // Buat URL from dari named route bernama 'profile'
     *      $url = URL::to_route('profile');
     *
     *      // Buat URL from dari named route bernama 'profile' dengan  parameter wildcard
     *      $url = URL::to_route('profile', [$username]);
     *
     * </code>
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    public static function to_route($name, $parameters = [])
    {
        if (is_null($route = Router::find($name))) {
            throw new \Exception(sprintf('Error creating URL for undefined route: %s', $name));
        }

        $uri = trim(static::transpose(key($route), $parameters), '/');

        return static::to($uri);
    }

    /**
     * Ambil URL untuk beralih bahasa, menjaga halaman saat ini atau tidak.
     *
     * @param string $language Nama bahasa baru
     * @param bool   $reset    Navigasi harus direset ulang atau tidak?
     *
     * @return string
     */
    public static function to_language($language, $reset = false)
    {
        $url = $reset ? URL::home() : URL::to(URI::current());

        if (! in_array($language, Config::get('application.languages'))) {
            return $url;
        }

        $from = '/'.Config::get('application.language').'/';
        $to = '/'.$language.'/';

        return str_replace($from, $to, $url);
    }

    /**
     * Ganti parameter di URI yang diberikan.
     *
     * @param string $uri
     * @param array  $parameters
     *
     * @return string
     */
    public static function transpose($uri, $parameters)
    {
        $parameters = (array) $parameters;

        foreach ($parameters as $parameter) {
            if (! is_null($parameter)) {
                $uri = preg_replace('/\(.+?\)/', $parameter, $uri, 1);
            }
        }

        $uri = preg_replace('/\(.+?\)/', '', $uri);

        return trim($uri, '/');
    }

    /**
     * Periksa apakah URL yang diberikan valid atau tidak.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function valid($url)
    {
        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }
}
