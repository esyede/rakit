<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Str;
use System\Package;
use System\Response;
use System\Redirect;
use System\View;
use System\Config;
use System\Request;
use System\Session;
use System\URI;

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
     * Contains the route bindings, wildcard name => resolver.
     *
     * @var array
     */
    protected static $binders = [];

    /**
     * Contains the column of each model binding, wildcard name => column or NULL.
     *
     * @var array
     */
    protected static $columns = [];

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
     * @param string|array $action
     * @param array        $parameters
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
        $response = $this->forged();
        $response = is_null($response) ? Middleware::run($this->middlewares('before'), [], true) : $response;
        $response = Response::prepare(is_null($response) ? $this->response() : $response);

        Middleware::run($this->middlewares('after'), [&$response]);

        return $response;
    }

    /**
     * Refuse a request that changes data without the session's CSRF token, for every
     * route: forgetting 'before' => 'csrf' on one must not leave it open. Skipped for
     * stateless routes (no session, so no cookie to ride on), for the csrf_except
     * patterns, for 'csrf' => false routes, and when application.csrf_global is off.
     *
     * @return Response|null
     */
    protected function forged()
    {
        if (
            false === Arr::get((array) $this->action, 'csrf')
            || ! Config::get('application.csrf_global', true)
            || ! Session::started()
            || in_array(Request::real_method(), ['GET', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'], true)
        ) {
            return null;
        }

        foreach ((array) Config::get('application.csrf_except', []) as $pattern) {
            if (Str::is($pattern, URI::current())) {
                return null;
            }
        }

        return Request::forged() ? Response::error(422) : null;
    }

    /**
     * Execute the route and return the raw response, without running any middleware.
     *
     * @return mixed
     */
    public function response()
    {
        $parameters = $this->bindings();

        if ($parameters instanceof Response) {
            return $parameters;
        }

        $delegate = $this->delegate();

        if (! is_null($delegate)) {
            return Controller::call($delegate, $parameters);
        }

        $handler = $this->handler();

        if (! is_null($handler)) {
            return call_user_func_array($handler, $parameters);
        }
    }

    /**
     * Resolve the parameters of bound wildcards, (:user) for Route::model('user', ..).
     * Each wildcard captures one parameter, so the n-th wildcard of the URI is the
     * n-th parameter. A value the resolver does not find answers 404.
     *
     * @return array|Response
     */
    protected function bindings()
    {
        if (empty(static::$binders) || ! preg_match_all('#\(:([a-zA-Z_][a-zA-Z0-9_]*)\??\)#', $this->uri, $names)) {
            return $this->parameters;
        }

        $parameters = $this->parameters;

        foreach ($names[1] as $index => $name) {
            if (
                ! isset(static::$binders[$name])
                || ! array_key_exists($index, $parameters)
                || is_null($parameters[$index])
                || '' === $parameters[$index]
            ) {
                continue;
            }

            $bound = call_user_func(static::$binders[$name], $parameters[$index]);

            if (is_null($bound) || false === $bound) {
                return Response::error(404);
            }

            $parameters[$index] = $bound;
        }

        return $parameters;
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
        $global = new Middlewares(array_unique([$event, Package::prefix($this->package).$event]));
        $global->optional = true;

        $declared = [];

        if (isset($this->action[$event])) {
            $declared = array_merge($declared, Middleware::parse($this->action[$event]));
        }

        if ('before' === $event) {
            $declared = array_merge($declared, $this->patterns());
        }

        $collections = [$global];

        if (count($declared) > 0) {
            $collections[] = new Middlewares($declared);
        }

        return $collections;
    }

    /**
     * Get the middlewares that match the current route URI patterns.
     *
     * @return array
     */
    protected function patterns()
    {
        $middlewares = [];

        foreach (Middleware::$patterns as $pattern => $middleware) {
            if (! Str::is($pattern, $this->uri)) {
                continue;
            }

            if (is_array($middleware)) {
                list($name, $callback) = array_values($middleware);
                Middleware::register($name, $callback);
                $middlewares[] = $name;
                continue;
            }

            if (! is_string($middleware)) {
                $name = 'pattern#'.$pattern;
                Middleware::register($name, $middleware);
                $middlewares[] = $name;
                continue;
            }

            $middlewares = array_merge($middlewares, Middleware::parse($middleware));
        }

        return $middlewares;
    }

    /**
     * Get the controller action handling the route, or NULL when there is none.
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
     * @param string $name
     *
     * @return bool
     */
    public function is($name)
    {
        return Arr::get($this->action, 'as') === $name;
    }

    /**
     * Bind a wildcard to a Facile model: (:user) then passes User::find($value), or
     * the first row whose $column equals the value. A missing row answers 404.
     *
     * @param string      $name
     * @param string      $model
     * @param string|null $column
     */
    public static function model($name, $model, $column = null)
    {
        static::bind($name, function ($value) use ($model, $column) {
            return is_null($column) ? $model::find($value) : $model::where($column, '=', $value)->first();
        });

        static::$columns[$name] = $column;
    }

    /**
     * Get the value a model stands for in a URL: its binding column for (:name),
     * else its primary key.
     *
     * @param \System\Database\Facile\Model $model
     * @param string|null                    $name
     *
     * @return mixed
     */
    public static function route_key($model, $name = null)
    {
        $column = (! is_null($name) && isset(static::$columns[$name])) ? static::$columns[$name] : null;
        return is_null($column) ? $model->get_key() : $model->{$column};
    }

    /**
     * Bind a wildcard to a resolver: (:name) in a URI then passes what the closure
     * returns for the matched value. NULL or FALSE answers 404. Define bindings
     * before the routes that use them.
     *
     * @param string   $name
     * @param \Closure $resolver
     */
    public static function bind($name, \Closure $resolver)
    {
        if (
            ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string) $name)
            || in_array($name, ['alpha', 'num', 'alnum', 'any', 'segment', 'all'], true)
        ) {
            throw new \InvalidArgumentException(sprintf('Invalid route binding name: %s', $name));
        }

        static::$binders[$name] = $resolver;
        Router::$patterns['(:'.$name.')'] = '([^/]{1,255})';
        Router::$optional['/(:'.$name.'?)'] = '(?:/([^/]{1,255}))?';
    }

    /**
     * Forget a binding.
     *
     * @param string $name
     */
    public static function unbind($name)
    {
        unset(
            static::$binders[$name],
            static::$columns[$name],
            Router::$patterns['(:'.$name.')'],
            Router::$optional['/(:'.$name.'?)']
        );
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
     * Register a resource for an API: the routes of resource() without 'create' and
     * 'edit', which only serve HTML forms.
     *
     * @param string $controller
     * @param array  $options
     */
    public static function api_resource($controller, array $options = [])
    {
        $except = isset($options['except']) ? (array) $options['except'] : [];
        $options['except'] = array_unique(array_merge($except, ['create', 'edit']));

        Resource::make($controller, $options);
    }

    /**
     * Register a resource there is one of, with no ID in its URIs (a profile, the
     * settings): show, edit and update. Takes the 'only', 'except' and 'controller'
     * options of resource().
     *
     * @param string $controller
     * @param array  $options
     */
    public static function singleton($controller, array $options = [])
    {
        $options = array_intersect_key($options, ['only' => 0, 'except' => 0, 'controller' => 0]);

        Resource::make($controller, $options + [
            ['method' => 'get', 'route' => '', 'as' => ':name.show', 'uses' => ':name@show'],
            ['method' => 'get', 'route' => '/edit', 'as' => ':name.edit', 'uses' => ':name@edit'],
            ['method' => 'put', 'route' => '', 'as' => ':name.update', 'uses' => ':name@update'],
        ]);
    }

    /**
     * Define a middleware group, so 'before' => 'web' runs all of its middlewares.
     *
     * @param string       $name
     * @param string|array $middlewares
     */
    public static function middleware_group($name, $middlewares)
    {
        Middleware::group($name, $middlewares);
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
     * @param string $name
     * @param mixed  $handler
     */
    public static function middleware($name, $handler)
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
        $route = Router::route(strtoupper((string) $method), $uri);

        return is_null($route) ? Response::error(404) : $route->call();
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
     * @param string $route
     * @param string $to
     * @param int    $status
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
