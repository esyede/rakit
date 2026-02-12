<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Package;

class Middleware
{
    /**
     * Contains the list of registered middlewares.
     *
     * @var array
     */
    public static $middlewares = [];

    /**
     * Contains the list of registered middleware patterns.
     *
     * @var array
     */
    public static $patterns = [];

    /**
     * Contains the list of middleware aliases.
     *
     * @var array
     */
    public static $aliases = [];

    /**
     * Register a middleware.
     *
     * <code>
     *
     *      // Register a middleware via closure
     *      Middleware::register('before', function() { });
     *
     *      // Register a middleware via callback
     *      Middleware::register('before', ['ClassName', 'method']);
     *
     * </code>
     *
     * @param string $name
     * @param mixed  $handler
     */
    public static function register($name, callable $handler)
    {
        $name = (string) (isset(static::$aliases[$name]) ? static::$aliases[$name] : $name);

        if (0 === strpos($name, 'pattern: ')) {
            $patterns = explode(', ', substr($name, 9));

            foreach ($patterns as $pattern) {
                static::$patterns[$pattern] = $handler;
            }
        } else {
            static::$middlewares[$name] = $handler;
        }
    }

    /**
     * Make an alias for a middleware.
     * This makes it easier to shorten the call to a package's built-in middleware.
     *
     *
     * @param string $middleware
     * @param string $alias
     */
    public static function alias($middleware, $alias)
    {
        static::$aliases[$alias] = $middleware;
    }

    /**
     * Parse middlewares from string or array to array.
     *
     * @param string|array $middlewares
     *
     * @return array
     */
    public static function parse($middlewares)
    {
        return is_string($middlewares) ? explode('|', $middlewares) : (array) $middlewares;
    }

    /**
     * Call the given middlewares.
     *
     * @param array $collections
     * @param array $pass
     * @param bool  $override
     *
     * @return mixed
     */
    public static function run(array $collections, array $pass = [], $override = false)
    {
        foreach ($collections as $collection) {
            foreach ($collection->middlewares as $middleware) {
                list($middleware, $parameters) = $collection->get($middleware);

                Package::boot(Package::name($middleware));

                if (!isset(static::$middlewares[$middleware])) {
                    continue;
                }

                $callback = static::$middlewares[$middleware];
                $response = call_user_func_array($callback, array_merge($pass, $parameters));

                if (!is_null($response) && $override) {
                    return $response;
                }
            }
        }
    }
}
