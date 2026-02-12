<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Str;
use System\Package;
use System\Response;
use System\Redirect;
use System\View;

class Route
{
    /**
     * Contains the URI being responded to by the route.
     *
     * @var string
     */
    public $uri;

    /**
     * Contains the HTTP method used by the route.
     *
     * @var string
     */
    public $method;

    /**
     * Contains the package name that handles the route.
     *
     * @var string
     */
    public $package;

    /**
     * Contains the name of the controller used by the route.
     *
     * @var string
     */
    public $controller;

    /**
     * Contains the name of the controller action used by the route.
     *
     * @var string
     */
    public $controller_action;

    /**
     * Contains the action array of the route.
     *
     * @var mixed
     */
    public $action;

    /**
     * Contains the route parameters.
     *
     * @var array
     */
    public $parameters;

    /**
     * Constructor.
     *
     * @param string $method
     * @param string $uri
     * @param string $action
     * @param array  $parameters
     */
    public function __construct($method, $uri, $action, array $parameters = [])
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->action = $action;
        $this->package = Package::handles($uri);

        $this->parameters($action, $parameters);
    }

    /**
     * Set the route parameters, merging with default values if necessary.
     *
     * @param array $action
     * @param array $parameters
     */
    protected function parameters($action, array $parameters)
    {
        $defaults = (array) Arr::get($action, 'defaults');

        if (count($defaults) > count($parameters)) {
            $parameters = array_merge($parameters, array_slice($defaults, count($parameters)));
        }

        $this->parameters = $parameters;
    }

    /**
     * Call the route and return the response.
     *
     * @return Response
     */
    public function call()
    {
        $response = Middleware::run($this->middlewares('before'), [], true);
        $response = Response::prepare(is_null($response) ? $this->response() : $response);

        Middleware::run($this->middlewares('after'), [&$response]);

        return $response;
    }

    /**
     * Execute the route and return the raw response.
     * Unlike the call() method, no middleware will be executed.
     *
     * @return mixed
     */
    public function response()
    {
        $delegate = $this->delegate();

        if (!is_null($delegate)) {
            return Controller::call($delegate, $this->parameters);
        }

        $handler = $this->handler();

        if (!is_null($handler)) {
            return call_user_func_array($handler, $this->parameters);
        }
    }

    /**
     * Get the middlewares for the given event.
     *
     * @param string $event
     *
     * @return array
     */
    protected function middlewares($event)
    {
        $global = Package::prefix($this->package) . $event;
        $middlewares = array_unique([$event, $global]);

        if (isset($this->action[$event])) {
            $middlewares = array_merge($middlewares, Middleware::parse($this->action[$event]));
        }

        if ('before' === $event) {
            $middlewares = array_merge($middlewares, $this->patterns());
        }

        return [new Middlewares($middlewares)];
    }

    /**
     * Get the middlewares that match the current route URI patterns.
     *
     * @return array
     */
    protected function patterns()
    {
        $patterns = Middleware::$patterns;
        $middlewares = [];

        foreach ($patterns as $pattern => $middleware) {
            if (Str::is($pattern, $this->uri)) {
                if (is_array($middleware)) {
                    list($middleware, $callback) = array_values($middleware);
                    Middleware::register($middleware, $callback);
                }

                $middlewares[] = $middleware;
            }
        }

        return $middlewares;
    }

    /**
     * Get the controller action that handles the route.
     * If the action is not found, NULL will be returned.
     *
     * @return string
     */
    protected function delegate()
    {
        return Arr::get($this->action, 'uses', null);
    }

    /**
     * Get the Closure handler that handles the route.
     *
     * @return \Closure
     */
    protected function handler()
    {
        return Arr::first($this->action, function ($key, $value) {
            return ($value instanceof \Closure);
        });
    }

    /**
     * Check if the current route matches the given name (used for named routes).
     *
     * <code>
     *
     *      // Check if the current route is named 'login'
     *      if (Request::route()->is('login')) {
     *          // The current route is named 'login'
     *      }
     *
     * </code>
     *
     * @param string $name
     *
     * @return bool
     */
    public function is($name)
    {
        return Arr::get($this->action, 'as') === $name;
    }

    /**
     * Check if a route with the given name exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has($name)
    {
        return in_array($name, array_values(array_filter(data_get(static::lists(), '*.*.as', []))));
    }

    /**
     * Register a controller route.
     *
     * @param string|array $controllers
     * @param string|array $defaults
     */
    public static function controller($controllers, $defaults = 'index')
    {
        Router::controller($controllers, $defaults);
    }

    /**
     * Register a GET route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function get($route, $action)
    {
        Router::register('GET', $route, $action);
    }

    /**
     * Register a HEAD route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function head($route, $action)
    {
        Router::register('HEAD', $route, $action);
    }

    /**
     * Register a TRACE route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function trace($route, $action)
    {
        Router::register('TRACE', $route, $action);
    }

    /**
     * Register a CONNECT route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function connect($route, $action)
    {
        Router::register('CONNECT', $route, $action);
    }

    /**
     * Register a OPTIONS route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function options($route, $action)
    {
        Router::register('OPTIONS', $route, $action);
    }

    /**
     * Register a POST route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function post($route, $action)
    {
        Router::register('POST', $route, $action);
    }

    /**
     * Register a PUT route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function put($route, $action)
    {
        Router::register('PUT', $route, $action);
    }

    /**
     * Register a PATCH route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function patch($route, $action)
    {
        Router::register('PATCH', $route, $action);
    }

    /**
     * Register a DELETE route.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function delete($route, $action)
    {
        Router::register('DELETE', $route, $action);
    }

    /**
     * Register a resource route.
     *
     * @param string $controller
     * @param array  $options
     */
    public static function resource($controller, array $options = [])
    {
        Resource::make($controller, $options);
    }

    /**
     * Register a route that responds to any HTTP method.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function any($route, $action)
    {
        Router::register('*', $route, $action);
    }

    /**
     * Register a route group.
     *
     * @param array    $attributes
     * @param \Closure $callback
     */
    public static function group($attributes, \Closure $callback)
    {
        Router::group($attributes, $callback);
    }

    /**
     * Register a route group with a specific domain.
     *
     * @param string   $domain
     * @param \Closure $callback
     */
    public static function domain($domain, \Closure $callback)
    {
        static::group(['domain' => $domain], $callback);
    }

    /**
     * Register a shared route action.
     *
     * @param array $routes
     * @param mixed $action
     */
    public static function share(array $routes, $action)
    {
        Router::share($routes, $action);
    }

    /**
     * Register a middleware handler.
     *
     * @param string   $name
     * @param callable $handler
     */
    public static function middleware($name, callable $handler)
    {
        Middleware::register($name, $handler);
    }

    /**
     * Forward a request to a given URI and return the response.
     *
     * @param string $method
     * @param string $uri
     *
     * @return Response
     */
    public static function forward($method, $uri)
    {
        return Router::route(strtoupper((string) $method), $uri)->call();
    }

    /**
     * Register a view route.
     *
     * @param string $route
     * @param string $view
     * @param array  $data
     *
     * @return \System\View
     */
    public static function view($route, $view, array $data = [])
    {
        static::get($route, function () use ($view, $data) {
            return View::make($view, $data);
        });
    }

    /**
     * Register a redirect route.
     *
     * @param strng $route
     * @param strng $to
     * @param int   $status
     *
     * @return \System\Redirect
     */
    public static function redirect($route, $to, $status = 302)
    {
        static::get($route, function () use ($to, $status) {
            return Redirect::to($to, $status);
        });
    }

    /**
     * Get the list of all registered routes.
     *
     * @return array
     */
    public static function lists()
    {
        return Router::routes();
    }
}
