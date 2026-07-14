<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

use System\Routing\Router;
use System\Routing\Route;
use System\Optional;

class Collectors
{
    /**
     * Collected data storage.
     *
     * @var array
     */
    private static $data = [
        'request' => [],
        'routes' => [],
        'events' => [],
        'views' => [],
        'cache' => [],
        'logs' => [],
        'timers' => [],
    ];

    /**
     * Cache operation tracking.
     *
     * @var bool
     */
    private static $trackCache = false;

    /**
     * View rendering tracking.
     *
     * @var bool
     */
    private static $trackViews = false;

    /**
     * Event tracking.
     *
     * @var bool
     */
    private static $trackEvents = false;

    /**
     * Initialize all collectors.
     *
     * @return void
     */
    public static function initialize()
    {
        static::$trackCache = true;
        static::$trackViews = true;
        static::$trackEvents = true;
        static::$data['views'] = [];
        static::$data['events'] = [];
        static::$data['logs'] = [];
        static::$data['timers'] = [];
        static::$data['cache'] = [
            'driver' => config('cache.driver', 'file'),
            'config' => [],
            'operations' => [],
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * Collect request/response data.
     *
     * @return array
     */
    public static function collectRequest()
    {
        return [
            'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI',
            'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A',
            'protocol' => isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1',
            'status_code' => http_response_code(),
            'headers' => function_exists('getallheaders') ? getallheaders() : [],
            'response_headers' => headers_list(),
        ];
    }

    /**
     * Collect current session data.
     *
     * @return array
     */
    public static function collectSession()
    {
        $result = [
            'started' => false,
            'driver' => config('session.driver', 'file'),
            'id' => null,
            'token' => null,
            'last_activity' => null,
            'data' => [],
            'flash_new' => [],
            'flash_old' => [],
        ];

        if (!class_exists('System\\Session') || !\System\Session::started()) {
            return $result;
        }

        $result['started'] = true;

        try {
            $payload = \System\Session::instance();
            $session = (isset($payload->session) && is_array($payload->session)) ? $payload->session : [];

            $result['id'] = isset($session['id']) ? $session['id'] : null;
            $result['last_activity'] = isset($session['last_activity']) ? $session['last_activity'] : null;
            $result['token'] = method_exists($payload, 'token') ? $payload->token() : null;

            $all = $payload->all();
            $all = is_array($all) ? $all : [];

            if (isset($all[':new:'])) {
                $result['flash_new'] = (array) $all[':new:'];
                unset($all[':new:']);
            }

            if (isset($all[':old:'])) {
                $result['flash_old'] = (array) $all[':old:'];
                unset($all[':old:']);
            }

            $result['data'] = $all;
        } catch (\Throwable $e) {
            // skip errors.
        } catch (\Exception $e) {
            // skip errors.
        }

        return $result;
    }

    /**
     * Collect current route information.
     *
     * @return array
     */
    public static function collectRoutes()
    {
        $currentRoute = null;
        $currentUri = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : null;
        $currentMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        // Try to get current route from Router
        if (class_exists('System\Routing\Router')) {
            // Attempt to find the matched route
            if (isset(Router::$routes[$currentMethod])) {
                foreach (Router::$routes[$currentMethod] as $uri => $route) {
                    if ($route instanceof Route && static::matchRoute($uri, $currentUri)) {
                        $optional = new Optional($route);
                        $currentRoute = [
                            'uri' => $uri,
                            'method' => $currentMethod,
                            'action' => $route->action,
                            'filters' => $optional->filters ?: [],
                            'parameters' => $optional->parameters ?: [],
                        ];

                        // Get route name if exists
                        foreach (Router::$names as $routeUri => $name) {
                            if ($routeUri === $uri) {
                                $currentRoute['name'] = $name;
                                break;
                            }
                        }

                        break;
                    }
                }
            }
        }

        return ['current_route' => $currentRoute];
    }

    /**
     * Simple route matching helper.
     *
     * @param string $pattern
     * @param string $uri
     *
     * @return bool
     */
    private static function matchRoute($pattern, $uri)
    {
        if ($pattern === $uri) {
            return true;
        }

        // Simple pattern matching for dynamic routes
        $pattern = preg_replace('/\(:num\)/', '([0-9]+)', $pattern);
        $pattern = preg_replace('/\(:any\)/', '([^/]+)', $pattern);
        $pattern = preg_replace('/\(:alpha\)/', '([a-zA-Z]+)', $pattern);
        $pattern = preg_replace('/\(:alnum\)/', '([a-zA-Z0-9]+)', $pattern);

        return preg_match('#^' . $pattern . '$#', $uri);
    }

    /**
     * Track a cache operation.
     *
     * @param string $type
     * @param string $key
     * @param mixed  $value
     * @param float  $time
     * @param array  $extra
     *
     * @return void
     */
    public static function trackCacheOperation($type, $key, $value = null, $time = 0, array $extra = [])
    {
        if (!static::$trackCache) {
            return;
        }

        $operation = [
            'type' => $type,
            'key' => $key,
            'time' => $time,
        ];

        if ($value !== null) {
            $operation['value'] = $value;
        }

        $operation = array_merge($operation, $extra);
        static::$data['cache']['operations'][] = $operation;

        // Update counters
        switch (strtolower($type)) {
            case 'hit': static::$data['cache']['hits']++; break;
            case 'miss': static::$data['cache']['misses']++; break;
            case 'write':
            case 'put': static::$data['cache']['writes']++; break;
            case 'delete':
            case 'forget': static::$data['cache']['deletes']++; break;
        }
    }

    /**
     * Track a view rendering.
     *
     * @param string $name
     * @param string $path
     * @param array  $data
     * @param float  $time
     * @param int    $size
     *
     * @return void
     */
    public static function trackView($name, $path = null, array $data = [], $time = 0, $size = 0)
    {
        if (!static::$trackViews) {
            return;
        }

        static::$data['views'][] = [
            'name' => $name,
            'path' => $path,
            'data' => $data,
            'time' => $time,
            'size' => $size,
        ];
    }

    /**
     * Track an event.
     *
     * @param string $name
     * @param array  $data
     * @param float  $time
     *
     * @return void
     */
    public static function trackEvent($name, array $data = [], $time = null)
    {
        if (!static::$trackEvents) {
            return;
        }

        static::$data['events'][] = [
            'id' => uniqid('event_'),
            'name' => $name,
            'data' => $data,
            'time' => $time ?: microtime(true),
        ];
    }

    /**
     * Add a log entry.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     * @param string $file
     * @param int    $line
     *
     * @return void
     */
    public static function addLog($level, $message, array $context = [], $file = null, $line = null)
    {
        static::$data['logs'][] = [
            'id' => uniqid('log_'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => time(),
            'file' => $file,
            'line' => $line,
        ];
    }

    /**
     * Add a timer record (untuk panel Timeline).
     *
     * @param string $name
     * @param float  $duration Durasi dalam milidetik
     * @param float  $start    Offset mulai dari awal request (ms)
     *
     * @return void
     */
    public static function addTimer($name, $duration, $start = 0)
    {
        static::$data['timers'][$name] = ['duration' => $duration, 'start' => $start];
    }

    /**
     * Get collected data for a specific panel.
     *
     * @param string $panel
     *
     * @return array
     */
    public static function getData($panel)
    {
        switch ($panel) {
            case 'messages':
                return [
                    'logs' => static::$data['logs'],
                    'error_count' => count(array_filter(static::$data['logs'], function ($log) {
                        return in_array(strtolower($log['level']), ['error', 'critical', 'emergency', 'alert']);
                    })),
                ];

            case 'cache':
                if (class_exists('System\Cache')) {
                    try {
                        $driver = config('cache.driver', 'file');
                        static::$data['cache']['driver'] = $driver;
                        static::$data['cache']['config'] = static::getCacheConfig($driver);
                    } catch (\Throwable $e) {
                        // skip errors.
                    } catch (\Exception $e) {
                        // skip errors.
                    }
                }
                return ['cache' => static::$data['cache']];

            case 'views':    return ['views' => static::$data['views']];
            case 'timeline': return ['timers' => static::$data['timers']];
            case 'events':   return ['events' => static::$data['events']];
            default:         return isset(static::$data[$panel]) ? static::$data[$panel] : [];
        }
    }

    /**
     * Get cache driver configuration.
     *
     * @param string $driver
     *
     * @return array
     */
    private static function getCacheConfig($driver)
    {
        $config = [];

        switch ($driver) {
            case 'file':
                $config['path'] = config('cache.path', path('storage') . 'caches' . DS);
                break;

            case 'memcached':
                $config['servers'] = config('cache.memcached.servers', []);
                break;

            case 'redis':
                $config['host'] = config('cache.redis.host', '127.0.0.1');
                $config['port'] = config('cache.redis.port', 6379);
                break;

            case 'database':
                $config['connection'] = config('cache.database.connection', config('database.default'));
                $config['table'] = config('cache.database.table', 'caches');
                break;

            case 'apc':
            case 'apcu':
                $config['enabled'] = extension_loaded('apc') || extension_loaded('apcu');
                break;
        }

        return $config;
    }

    /**
     * Get all collected data.
     *
     * @return array
     */
    public static function getAllData()
    {
        return static::$data;
    }

    /**
     * Reset all collected data.
     *
     * @return void
     */
    public static function reset()
    {
        static::$data = [
            'request' => [],
            'routes' => [],
            'events' => [],
            'views' => [],
            'cache' => [],
            'logs' => [],
            'timers' => [],
        ];

        static::initialize();
    }

    /**
     * Check if tracking is enabled.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isTracking($type)
    {
        switch ($type) {
            case 'cache':  return static::$trackCache;
            case 'views':  return static::$trackViews;
            case 'events': return static::$trackEvents;
            default:       return false;
        }
    }
}
