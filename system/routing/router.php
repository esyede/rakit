<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Str;
use System\Package;

class Router
{
    /**
     * Berisi list nama route yang telah dicocokkan.
     *
     * @var array
     */
    public static $names = [];

    /**
     * Berisi list nama action route.
     *
     * @var array
     */
    public static $uses = [];

    /**
     * Berisi list seluruh route yang terdaftar.
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
     * Berisi list seluruh route 'fallback' yang terdaftar.
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
     * Berisi stack atribut route group.
     */
    public static $groups = [];

    /**
     * Berisi data atribut yang route group (merged).
     */
    public static $group;

    /**
     * Berisi nama paket untuk route saat ini.
     *
     * @var string
     */
    public static $package;

    /**
     * Jumlah maksimal segmen URI yang diizinkan sebagai argumen method.
     *
     * @var int
     */
    public static $segments = 5;

    /**
     * Nodes untuk trie routing (internal).
     *
     * @var array
     */
    public static $nodes = [];

    /**
     * Pola - pola regex yang didukung.
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
     * Pola - pola regex opsional yang didukung.
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
     * List HTTP request method.
     *
     * @var array
     */
    public static $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'TRACE', 'CONNECT', 'OPTIONS'];

    /**
     * Daftarkan sebuah action untuk menangani beberapa route sekaligus.
     *
     * <code>
     *
     *      // Daftarkan sebuah action untuk menangani sekelompok URI.
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
     * Daftarkan sebuah route group.
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
     * Daftarkan sebuah route.
     *
     * <code>
     *
     *      // Daftarkan sebuah route GET.
     *      Router::register('GET', '/', function() { return 'Home!'; } );
     *
     *      // Daftarkan sebuah action untuk menangani beberapa route sekaligus.
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
        // Inisialisasi trie jika belum ada
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

            $routes[$method][$uri] = is_array($action) ? $action : static::action($action);

            if (!is_null(static::$group)) {
                $routes[$method][$uri] += static::$group;
            }

            static::insert_node($method, $uri, $routes[$method][$uri]);
        }
    }

    /**
     * Merge semua group attributes.
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
     * Ubah action menjadi bentuk array action yang valid.
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
     * Daftarkan controller (auto-discovery).
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
     * Daftarkan sebuah route sebagai root controller.
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
     * Cari route berdasarkan nama yang diberikan.
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
     * Cari route berdasarkan action yang diberikan.
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
     * Cari route berdasarkan kecocokan nama method dan URI-nya.
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

        if (array_key_exists($uri, $routes)) {
            $action = $routes[$uri];
            if (isset($action['domain']) && !static::domain_matches($action['domain'], $domain)) {
                // Domain tidak cocok, lanjut ke match
            } else {
                return new Route($method, $uri, $action);
            }
        }

        if (!is_null($route = static::match($method, $uri, $domain))) {
            return $route;
        }
    }

    /**
     * Cari route dengan mencocokkan pola URI-nya.
     *
     * @param string $method
     * @param string $uri
     * @param string $domain
     *
     * @return Route
     */
    protected static function match($method, $uri, $domain = null)
    {
        // Coba match dari trie node dulu
        $result = static::match_node($method, $uri);

        if ($result) {
            $action = $result['action'];
            if (isset($action['domain']) && !static::domain_matches($action['domain'], $domain)) {
                // Domain tidak cocok, skip
            } else {
                // Convert associative params ke indexed array untuk konsistensi
                $params = array_values($result['params']);
                $pattern = isset($result['pattern_uri']) ? $result['pattern_uri'] : $uri;
                return new Route($method, $pattern, $action, $params);
            }
        }

        // Fallback ke regex loop
        $routes = static::method($method);

        foreach ($routes as $route => $action) {
            if (isset($action['domain']) && !static::domain_matches($action['domain'], $domain)) {
                continue;
            }
            if (Str::contains($route, '(')) {
                $pattern = '#^' . static::wildcards($route) . '$#u';

                if (preg_match($pattern, $uri, $parameters)) {
                    return new Route($method, $route, $action, array_slice($parameters, 1));
                }
            }
        }
    }

    /**
     * Cek apakah domain cocok dengan pattern domain route.
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

        // Jika pattern mengandung {}, ubah menjadi regex
        if (Str::contains($pattern, '{')) {
            $pattern = preg_quote($pattern, '#');
            $pattern = preg_replace('/\\\{([^}]+)\\\}/', '(?P<$1>[a-zA-Z0-9\.\-_]+)', $pattern);
            $pattern = '#^' . $pattern . '$#';
            return (bool) preg_match($pattern, $domain);
        }

        // Jika tidak ada wildcard, cocokkan langsung
        return $pattern === $domain;
    }

    /**
     * Ubah URI wildcard menjadi regex.
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
     * Ambil list seluruh route yang telah didaftarkan.
     * Fallback route ditaruh di bagian bawah.
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
     * Ambil seluruh route berdasarkan HTTP request method yang diberikan.
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
     * Ambil seluruh pola wildcard route.
     *
     * @return array
     */
    public static function patterns()
    {
        return array_merge(static::$patterns, static::$optional);
    }

    /**
     * Ulangi string pola URI sebanyak jumlah yang diberikan.
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
     * Inisialisasi trie nodes jika belum ada.
     */
    private static function init_nodes()
    {
        if (empty(static::$nodes)) {
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'TRACE', 'CONNECT', 'OPTIONS'];

            foreach ($methods as $method) {
                static::$nodes[$method] = ['children' => [], 'action' => null, 'is_param' => false, 'param_name' => null, 'pattern_uri' => null, 'uri' => null];
            }
        }
    }

    /**
     * Insert route ke trie internal.
     */
    private static function insert_node($method, $uri, $action)
    {
        static::init_nodes();
        $node = &static::$nodes[$method];
        $uri = trim($uri, '/');
        $uri = ('' === $uri) ? '/' : $uri;
        $segments = explode('/', $uri);

        // Jika URI adalah '/', segments kosong, set action langsung
        if (empty($segments) || (count($segments) === 1 && empty($segments[0]))) {
            $node['action'] = $action;
            $node['pattern_uri'] = $uri;
            $node['uri'] = $uri;
            return;
        }

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            // Cek jika segment adalah parameter (e.g., (:num))
            if (preg_match('/^\(:(\w+)\)$/', $segment, $matches)) {
                $param_name = $matches[1];
                $key = ':param';

                if (!isset($node['children'][$key])) {
                    $node['children'][$key] = ['children' => [], 'action' => null, 'is_param' => true, 'param_name' => $param_name, 'pattern_uri' => null, 'uri' => null];
                }

                $node = &$node['children'][$key];
            } else {
                if (!isset($node['children'][$segment])) {
                    $node['children'][$segment] = ['children' => [], 'action' => null, 'is_param' => false, 'param_name' => null, 'pattern_uri' => null, 'uri' => null];
                }

                $node = &$node['children'][$segment];
            }
        }

        $node['action'] = $action;
        $node['pattern_uri'] = $uri;
        $node['uri'] = $uri;
    }

    /**
     * Match URI dari trie internal dan ekstrak parameter.
     */
    private static function match_node($method, $uri)
    {
        if (!isset(static::$nodes[$method])) {
            return null;
        }

        $node = static::$nodes[$method];
        $uri = trim($uri, '/');
        $uri = ('' === $uri) ? '/' : $uri;
        $segments = explode('/', $uri);

        // Jika URI adalah '/', segments kosong, return action root
        if (empty($segments) || (count($segments) === 1 && empty($segments[0]))) {
            return ($node['action'] && $node['uri'] === $uri) ? ['action' => $node['action'], 'params' => [], 'pattern_uri' => $node['pattern_uri']] : null;
        }

        $params = [];

        foreach ($segments as $segment) {
            if (empty($segment)) {
                continue;
            }

            $found = false;

            if (isset($node['children'][$segment])) { // Coba cocokkan segment statis
                $node = $node['children'][$segment];
                $found = true;
            } elseif (isset($node['children'][':param'])) { // Jika tidak, coba parameter
                $param_node = $node['children'][':param'];
                $param_name = $param_node['param_name'];

                // Validasi parameter sesuai tipenya
                $valid = true;
                if ($param_name === 'num') {
                    $valid = preg_match('/^[0-9]+$/', $segment);
                } elseif ($param_name === 'alpha') {
                    $valid = preg_match('/^[a-zA-Z]+$/', $segment);
                } elseif ($param_name === 'alnum') {
                    $valid = preg_match('/^[a-zA-Z0-9]+$/', $segment);
                }
                // 'any' tidak perlu validasi, terima semua

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

        return (isset($node['action']) && !empty($node['action']) && $node['uri'] === $uri)
            ? ['action' => $node['action'], 'params' => $params, 'pattern_uri' => $node['pattern_uri']]
            : null;
    }
}
