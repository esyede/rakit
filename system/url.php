<?php

namespace System;

defined('DS') or exit('No direct access.');

class URL
{
    /**
     * Contains Base URL (from cache).
     *
     * @var string
     */
    public static $base;

    /**
     * Get full URI (including query string).
     *
     * @return string
     */
    public static function full()
    {
        return static::to(URI::full());
    }

    /**
     * Get current request URI.
     *
     * @return string
     */
    public static function current()
    {
        return static::to(URI::current(), false, false);
    }

    /**
     * Get the application home URL.
     *
     * @return string
     */
    public static function home()
    {
        return is_null(Routing\Router::find('home')) ? static::to('/') : static::to_route('home');
    }

    /**
     * Get the application base URL.
     *
     * @return string
     */
    public static function base()
    {
        if (!static::$base) {
            $base = Config::get('application.url');
            static::$base = ('' === $base) ? Request::foundation()->getRootUrl() : $base;
        }

        return static::$base;
    }

    /**
     * Create an URL to a location within application scope.
     *
     * <code>
     *
     *      // Create URL to user profile
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
        $base = static::base() . ($asset ? '' : '/' . $config['index']);

        if (!$asset && $locale && count($config['languages']) > 0) {
            if (in_array($config['language'], $config['languages'])) {
                $base = rtrim($base, '/') . '/' . $config['language'];
            }
        }

        $base = (Request::secure() || Str::starts_with(Config::get('application.url'), 'https://'))
            ? Str::replace_first('http://', 'https://', $base)
            : Str::replace_first('https://', 'http://', $base);

        return rtrim($base, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Create URL to the controller action.
     *
     * <code>
     *
     *      // Create URL to action 'index' in 'user' controller
     *      $url = URL::to_action('user@index');
     *
     *      // Create URL to action 'profile' in 'user' controller with parameters
     *      $url = URL::to_action('user@profile', ['budi']);
     *
     * </code>
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    public static function to_action($action, array $parameters = [])
    {
        $route = Routing\Router::uses($action);
        return is_null($route)
            ? static::convention($action, $parameters)
            : static::explicit($route, $action, $parameters);
    }

    /**
     * Create action URL from a route definition.
     *
     * @param array  $route
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    protected static function explicit($route, $action, array $parameters)
    {
        return static::to(static::transpose(key($route), $parameters));
    }

    /**
     * Create action URL based on convention.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    protected static function convention($action, array $parameters)
    {
        list($package, $action) = Package::parse($action);

        $package = Package::get($package);
        $root = isset($package['handles']) ? $package['handles'] : '';
        $parameters = implode('/', $parameters);

        $uri = $root . '/' . str_replace(['.', '@'], '/', $action);
        $uri = static::to(Str::finish($uri, '/') . $parameters);

        return trim($uri, '/');
    }

    /**
     * Create URL to the assets folder.
     *
     * @param string $url
     *
     * @return string
     */
    public static function to_asset($url)
    {
        return (static::valid($url) || static::valid('http:' . $url))
            ? $url
            : static::to('assets/' . ltrim($url, '/'), true);
    }

    /**
     * Create URL from named route.
     *
     * <code>
     *
     *      // Create URL from named route named 'profile'
     *      $url = URL::to_route('profile');
     *
     *      // Create URL from named route named 'profile' with parameters
     *      $url = URL::to_route('profile', [$name]);
     *
     * </code>
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    public static function to_route($name, array $parameters = [])
    {
        if (is_null($route = Routing\Router::find($name))) {
            throw new \Exception(sprintf('Error creating URL for undefined route: %s', $name));
        }

        return static::to(trim(static::transpose(key($route), $parameters), '/'));
    }

    /**
     * Create URL for switching language, keeping current page or not.
     *
     * @param string $language
     * @param bool   $reset
     *
     * @return string
     */
    public static function to_language($language, $reset = false)
    {
        $url = $reset ? URL::home() : URL::to(URI::current());
        return in_array($language, Config::get('application.languages'))
            ? str_replace('/' . Config::get('application.language') . '/', '/' . $language . '/', $url)
            : $url;
    }

    /**
     * Replace parameters in the given URI.
     *
     * @param string $uri
     * @param array  $parameters
     *
     * @return string
     */
    public static function transpose($uri, array $parameters)
    {
        foreach ($parameters as $parameter) {
            if (!is_null($parameter)) {
                $uri = preg_replace('/\(.+?\)/', $parameter, $uri, 1);
            }
        }

        return trim(preg_replace('/\(.+?\)/', '', $uri), '/');
    }

    /**
     * Check if the given URL is valid.
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
