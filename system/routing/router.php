<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Str;
use System\Package;

class Router
{
    /**
     * Contains list of route names.
     *
     * @var array
     */
    public static $names = [];

    /**
     * Contains list of route actions.
     *
     * @var array
     */
    public static $uses = [];

    /**
     * Contains list of all registered routes.
     * Grouped by HTTP request method.
     *
     * @var array
     */
    public static $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
        'HEAD' => [],
        'TRACE' => [],
        'CONNECT' => [],
        'OPTIONS' => [],
    ];

    /**
     * Contains list of all registered 'fallback' routes.
     * Grouped by HTTP request method.
     *
     * @var array
     */
    public static $fallback = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
        'HEAD' => [],
        'TRACE' => [],
        'CONNECT' => [],
        'OPTIONS' => [],
    ];

    /**
     * Contains list of route groups.
     */
    public static $groups = [];

    /**
     * Contains current route group attributes.
     */
    public static $group;

    /**
     * Contains package name for the current route.
     *
     * @var string
     */
    public static $package;

    /**
     * Maximum number of segments for controller auto-discovery.
     *
     * @var int
     */
    public static $segments = 5;

    /**
     * Contains node list for trie structure (internal).
     *
     * @var array
     */
    public static $nodes = [];

    /**
     * List of supported regex patterns.
     *
     * @var array
     */
    public static $patterns = [
        '(:alpha)' => '([a-zA-Z]+)',
        '(:num)' => '([0-9]+)',
        '(:alnum)' => '([a-zA-Z0-9]+)',
        '(:any)' => '([a-zA-Z0-9\.\-_%=]{1,255})',
        '(:segment)' => '([^/]{1,255})',
        '(:all)' => '(.{1,1000})',
    ];

    /**
     * List of supported optional regex patterns.
     *
     * @var array
     */
    public static $optional = [
        '/(:alpha?)' => '(?:/([a-zA-Z]+)',
        '/(:num?)' => '(?:/([0-9]+)',
        '/(:alnum?)' => '(?:/([a-zA-Z0-9]+)',
        '/(:any?)' => '(?:/([a-zA-Z0-9\.\-_%=]+)',
        '/(:segment?)' => '(?:/([^/]+)',
        '/(:all?)' => '(?:/(.*)',
    ];

    /**
     * List of HTTP request methods.
     *
     * @var array
     */
    public static $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'TRACE', 'CONNECT', 'OPTIONS'];

    /**
     * Register a shared action for multiple routes.
     *
     * <code>
     *
     *      // Register a shared action for multiple URIs.
     *      Router::share([['GET', '/'], ['POST', '/']], 'home@index');
     *
     * </code>
     *
     * @param array $routes
     * @param mixed $action
     */
    public static function share(array $routes, $action)
    {
        foreach ($routes as $route) {
            static::register($route[0], $route[1], $action);
        }
    }

    /**
     * Register a route group with shared attributes.
     *
     * @param array    $attributes
     * @param \Closure $handler
     */
    public static function group(array $attributes, \Closure $handler)
    {
        array_push(static::$groups, $attributes);
        static::$group = static::merge_groups();
        call_user_func($handler);
        array_pop(static::$groups);
        static::$group = static::merge_groups();
    }

    /**
     * Register a new route.
     *
     * <code>
     *
     *      // Register a GET route.
     *      Router::register('GET', '/', function() { return 'Home!'; } );
     *
     *      // Register a shared action for multiple routes.
     *      Router::register(['GET', '/', 'GET /home'], function() { return 'Home!'; } );
     *
     * </code>
     *
     * @param string|array $method
     * @param string       $route
     * @param mixed        $action
     */
    public static function register($method, $route, $action)
    {
        // Initialize trie nodes
        static::init_nodes();

        $route = Str::characterify($route);
        $digits = is_string($route) && '' !== $route && !preg_match('/[^0-9]/', $route);

        $route = $digits ? '(' . $route . ')' : $route;
        $route = is_string($route) ? explode(', ', $route) : $route;

        if (is_array($method)) {
            foreach ($method as $http) {
                static::register($http, $route, $action);
            }

            return;
        }

        $route = (array) $route;

        foreach ($route as $uri) {
            if ('*' === $method) {
                foreach (static::$methods as $method) {
                    static::register($method, $route, $action);
                }

                continue;
            }

            $uri = ltrim(str_replace('(:package)', (string) static::$package, $uri), '/');
            $uri = ('' === $uri) ? '/' : $uri;

            // Handle prefix from group
            if (!is_null(static::$group) && isset(static::$group['prefix'])) {
                $prefix = trim(static::$group['prefix'], '/');
                if (!empty($prefix)) {
                    $uri = $prefix . '/' . ltrim($uri, '/');
                }
            }

            $uri = rtrim($uri, '/');
            $uri = ('' === $uri) ? '/' : $uri;

            if ('(' === $uri[0]) {
                $routes = &static::$fallback;
            } else {
                $routes = &static::$routes;
            }

            // Use composite key (domain||uri) for domain-scoped routes to prevent
            // key collision when multiple routes share the same URI path.
            $group_domain = (!is_null(static::$group) && isset(static::$group['domain']))
                ? static::$group['domain']
                : null;
            $route_key = $group_domain ? ($group_domain . '||' . $uri) : $uri;

            $routes[$method][$route_key] = is_array($action) ? $action : static::action($action);

            if (!is_null(static::$group)) {
                $routes[$method][$route_key] += static::$group;
            }

            static::insert_node($method, $uri, $routes[$method][$route_key]);
        }
    }

    /**
     * Merge all route group attributes.
     *
     * @return array|null
     */
    protected static function merge_groups()
    {
        if (empty(static::$groups)) {
            return null;
        }

        $groups = [];

        foreach (static::$groups as $group) {
            $groups = array_merge($groups, $group);
        }

        return $groups;
    }

    /**
     * Generate action array from array, string, or Closure.
     *
     * @param mixed $action
     *
     * @return array
     */
    protected static function action($action)
    {
        return is_string($action)
            ? ['uses' => $action]
            : (($action instanceof \Closure) ? [$action] : (array) $action);
    }

    /**
     * Register controller routes automatically based on package controllers.
     *
     * @param string|array $controllers
     * @param string|array $defaults
     */
    public static function controller($controllers, $defaults = 'index')
    {
        $controllers = (array) $controllers;

        foreach ($controllers as $identifier) {
            list($package, $controller) = Package::parse($identifier);

            $root = Package::option($package, 'handles');
            $controller = str_replace('.', '/', $controller);

            if (Str::ends_with($controller, 'home')) {
                static::root($identifier, $controller, $root);
            }

            $wildcards = static::repeat('(:any?)', static::$segments);
            $pattern = trim($root . '/' . $controller . '/' . $wildcards, '/');
            $uses = $identifier . '@(:1)';

            static::register('*', $pattern, compact('uses', 'defaults'));
        }
    }

    /**
     * Register root route for a controller.
     *
     * @param string $identifier
     * @param string $controller
     * @param string $root
     */
    protected static function root($identifier, $controller, $root)
    {
        $home = ('home' === $controller) ? '' : dirname((string) $controller);
        $pattern = trim($root . '/' . $home, '/');

        static::register('*', $pattern ?: '/', ['uses' => $identifier . '@index']);
    }

    /**
     * Find a route by its name.
     *
     * @param string $name
     *
     * @return array
     */
    public static function find($name)
    {
        if (isset(static::$names[$name])) {
            return static::$names[$name];
        }

        if (0 === count(static::$names)) {
            $packages = Package::names();

            foreach ($packages as $package) {
                Package::routes($package);
            }
        }

        $routings = static::routes();

        foreach ($routings as $method => $routes) {
            foreach ($routes as $key => $value) {
                if (isset($value['as']) && $value['as'] === $name) {
                    return static::$names[$name] = [$key => $value];
                }
            }
        }
    }

    /**
     * Find a route by its action.
     *
     * @param string $action
     *
     * @return array
     */
    public static function uses($action)
    {
        if (isset(static::$uses[$action])) {
            return static::$uses[$action];
        }

        Package::routes(Package::name($action));

        $routings = static::routes();

        foreach ($routings as $method => $routes) {
            foreach ($routes as $key => $value) {
                if (isset($value['uses']) && $action === $value['uses']) {
                    return static::$uses[$action] = [$key => $value];
                }
            }
        }
    }

    /**
     * Find a route by its method and URI.
     *
     * @param string $method
     * @param string $uri
     * @param string $domain
     *
     * @return Route
     */
    public static function route($method, $uri, $domain = null)
    {
        Package::boot(Package::handles($uri));

        $uri = ltrim($uri, '/');
        $uri = ('' === $uri) ? '/' : $uri;

        $routes = (array) static::method($method);


        // Check domain-specific exact match first (composite key: domain||uri)
        foreach ($routes as $key => $action) {
            if (!isset($action['domain'])) {
                continue;
            }
            $key_uri = (strpos($key, '||') !== false) ? substr($key, strpos($key, '||') + 2) : $key;
            if ($key_uri === $uri && static::domain_matches($action['domain'], $domain)) {
                return new Route($method, $uri, $action);
            }
        }

        // Fall back to non-domain exact match
        if (array_key_exists($uri, $routes)) {
            $action = $routes[$uri];
            if (!isset($action['domain'])) {
                return new Route($method, $uri, $action);
            }
        }

        if (!is_null($route = static::match($method, $uri, $domain))) {
            return $route;
        }
    }

    /**
     * Find a route by matching URI patterns.
     *
     * @param string $method
     * @param string $uri
     * @param string $domain
     *
     * @return Route
     */
    protected static function match($method, $uri, $domain = null)
    {
        // Try to match using trie first (domain-aware)
        $result = static::match_node($method, $uri, $domain);

        if ($result) {
            $action = $result['action'];
            if (isset($action['domain']) && !static::domain_matches($action['domain'], $domain)) {
                // Domain does not match, continue to regex matching
            } else {
                // Convert associative params to indexed array
                $params = array_values($result['params']);
                $pattern = isset($result['pattern_uri']) ? $result['pattern_uri'] : $uri;
                return new Route($method, $pattern, $action, $params);
            }
        }

        // Fallback to regex matching
        $routes = static::method($method);

        foreach ($routes as $route_key => $action) {
            if (isset($action['domain']) && !static::domain_matches($action['domain'], $domain)) {
                continue;
            }
            // Extract actual URI pattern from composite key (domain||uri)
            $route = (strpos($route_key, '||') !== false)
                ? substr($route_key, strpos($route_key, '||') + 2)
                : $route_key;
            if (Str::contains($route, '(')) {
                $pattern = '#^' . static::wildcards($route) . '$#u';

                if (preg_match($pattern, $uri, $parameters)) {
                    return new Route($method, $route, $action, array_slice($parameters, 1));
                }
            }
        }
    }

    /**
     * Check if domain matches the given pattern.
     *
     * @param string $pattern
     * @param string $domain
     *
     * @return bool
     */
    protected static function domain_matches($pattern, $domain)
    {
        if (is_null($pattern) || is_null($domain)) {
            return $pattern === $domain;
        }

        // When pattern contains wildcards like {subdomain}, compare using regex
        if (Str::contains($pattern, '{')) {
            $pattern = preg_quote($pattern, '#');
            $pattern = preg_replace('/\\\{([^}]+)\\\}/', '(?P<$1>[a-zA-Z0-9\.\-_]+)', $pattern);
            $pattern = '#^' . $pattern . '$#';
            return (bool) preg_match($pattern, $domain);
        }

        // No wildcards, direct comparison
        return $pattern === $domain;
    }

    /**
     * Convert URI wildcards to regex.
     *
     * @param string $key
     *
     * @return string
     */
    protected static function wildcards($key)
    {
        list($search, $replace) = Arr::divide(static::$optional);

        $key = str_replace($search, $replace, $key, $count);
        $key = strtr($key, static::$patterns);
        $key .= ($count > 0) ? str_repeat(')?', $count) : '';

        return $key;
    }

    /**
     * Get all registered routes.
     * Fallback routes are placed at the bottom.
     *
     * @return array
     */
    public static function routes()
    {
        $routes = static::$routes;

        foreach (static::$methods as $method) {
            if (!isset($routes[$method])) {
                $routes[$method] = [];
            }

            $fallback = Arr::get(static::$fallback, $method, []);
            $routes[$method] = array_merge($routes[$method], $fallback);
        }

        return $routes;
    }

    /**
     * Get all registered routes for a specific method.
     *
     * @param string $method
     *
     * @return array
     */
    public static function method($method)
    {
        $routes = Arr::get(static::$routes, $method, []);
        return array_merge($routes, Arr::get(static::$fallback, $method, []));
    }

    /**
     * Get all supported patterns.
     *
     * @return array
     */
    public static function patterns()
    {
        return array_merge(static::$patterns, static::$optional);
    }

    /**
     * Repeat a pattern for controller auto-discovery.
     *
     * @param string $pattern
     * @param int    $times
     *
     * @return string
     */
    protected static function repeat($pattern, $times)
    {
        return implode('/', array_fill(0, $times, $pattern));
    }

    /**
     * Inisialize trie nodes.
     */
    private static function init_nodes()
    {
        if (empty(static::$nodes)) {
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'TRACE', 'CONNECT', 'OPTIONS'];

            foreach ($methods as $method) {
                static::$nodes[$method] = ['children' => [], 'action' => null, 'actions' => [], 'is_param' => false, 'param_name' => null, 'pattern_uri' => null, 'uri' => null];
            }
        }
    }

    /**
     * Insert route to internal trie.
     */
    private static function insert_node($method, $uri, $action)
    {
        static::init_nodes();
        $node = &static::$nodes[$method];
        $uri = trim($uri, '/');
        $uri = ('' === $uri) ? '/' : $uri;
        $segments = explode('/', $uri);

        if (empty($segments) || (count($segments) === 1 && empty($segments[0]))) {
            $node['action'] = $action;
            $node['actions'][] = $action;
            $node['pattern_uri'] = $uri;
            $node['uri'] = $uri;
            return;
        }

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            if (preg_match('/^\(:(\w+)\)$/', $segment, $matches)) {
                $param_name = $matches[1];
                $key = ':param';

                if (!isset($node['children'][$key])) {
                    $node['children'][$key] = ['children' => [], 'action' => null, 'actions' => [], 'is_param' => true, 'param_name' => $param_name, 'pattern_uri' => null, 'uri' => null];
                }

                $node = &$node['children'][$key];
            } else {
                if (!isset($node['children'][$segment])) {
                    $node['children'][$segment] = ['children' => [], 'action' => null, 'actions' => [], 'is_param' => false, 'param_name' => null, 'pattern_uri' => null, 'uri' => null];
                }

                $node = &$node['children'][$segment];
            }
        }

        $node['action'] = $action;
        $node['actions'][] = $action;
        $node['pattern_uri'] = $uri;
        $node['uri'] = $uri;
    }

    /**
     * Match URI from internal trie and extract the parameters.
     */
    private static function match_node($method, $uri, $domain = null)
    {
        if (!isset(static::$nodes[$method])) {
            return null;
        }

        $node = static::$nodes[$method];
        $uri = trim($uri, '/');
        $uri = ('' === $uri) ? '/' : $uri;
        $segments = explode('/', $uri);

        if (empty($segments) || (count($segments) === 1 && empty($segments[0]))) {
            return ($node['action'] && $node['uri'] === $uri) ? ['action' => $node['action'], 'params' => [], 'pattern_uri' => $node['pattern_uri']] : null;
        }

        $params = [];

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            $found = false;

            if (isset($node['children'][$segment])) {
                $node = $node['children'][$segment];
                $found = true;
            } elseif (isset($node['children'][':param'])) {
                $param_node = $node['children'][':param'];
                $param_name = $param_node['param_name'];
                $valid = true;

                if ($param_name === 'num') {
                    $valid = preg_match('/^[0-9]+$/', $segment);
                } elseif ($param_name === 'alpha') {
                    $valid = preg_match('/^[a-zA-Z]+$/', $segment);
                } elseif ($param_name === 'alnum') {
                    $valid = preg_match('/^[a-zA-Z0-9]+$/', $segment);
                }

                if ($valid) {
                    $node = $param_node;
                    $params[$param_name] = $segment;
                    $found = true;
                }
            }

            if (!$found) {
                return null;
            }
        }

        if (!empty($node['actions']) && $node['uri'] === $uri) {
            foreach ($node['actions'] as $action) {
                if (isset($action['domain']) && static::domain_matches($action['domain'], $domain)) {
                    return ['action' => $action, 'params' => $params, 'pattern_uri' => $node['pattern_uri']];
                }
            }

            foreach ($node['actions'] as $action) {
                if (!isset($action['domain'])) {
                    return ['action' => $action, 'params' => $params, 'pattern_uri' => $node['pattern_uri']];
                }
            }
        }

        return null;
    }
}
