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
     * Contains the middleware groups, name => middlewares.
     *
     * @var array
     */
    public static $groups = [];

    /**
     * Register a middleware. The handler is a callable, except for a pattern, which may
     * instead name the middlewares to attach, as 'a|b' or as an array.
     *
     * @param string $name
     * @param mixed  $handler
     */
    public static function register($name, $handler)
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
     * Alias a middleware, to shorten a call to a package's own.
     *
     * @param string $middleware
     * @param string $alias
     */
    public static function alias($middleware, $alias)
    {
        static::$aliases[$alias] = $middleware;
    }

    /**
     * Define a middleware group: 'before' => 'web' then runs every middleware in it.
     *
     * @param string       $name
     * @param string|array $middlewares
     */
    public static function group($name, $middlewares)
    {
        static::$groups[$name] = is_string($middlewares) ? explode('|', $middlewares) : (array) $middlewares;
    }

    /**
     * Parse middlewares from string or array to array, expanding the groups.
     *
     * @param string|array $middlewares
     * @param array        $seen
     *
     * @return array
     */
    public static function parse($middlewares, array $seen = [])
    {
        $middlewares = is_string($middlewares) ? explode('|', $middlewares) : (array) $middlewares;

        if (empty(static::$groups)) {
            return $middlewares;
        }

        $expanded = [];

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware) || ! isset(static::$groups[$middleware])) {
                $expanded[] = $middleware;
                continue;
            }

            if (in_array($middleware, $seen, true)) {
                throw new \Exception(sprintf('Middleware group includes itself: %s', $middleware));
            }

            $seen[] = $middleware;
            $expanded = array_merge($expanded, static::parse(static::$groups[$middleware], $seen));
        }

        return $expanded;
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

                if (! isset(static::$middlewares[$middleware])) {
                    if ($collection->optional) {
                        continue;
                    }

                    throw new \Exception(sprintf('Undefined middleware: %s', $middleware));
                }

                $callback = static::$middlewares[$middleware];
                $response = call_user_func_array($callback, array_merge($pass, $parameters));

                if (! is_null($response) && $override) {
                    return $response;
                }
            }
        }
    }
}
