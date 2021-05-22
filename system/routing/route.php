<?php

namespace System\Routing;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Str;
use System\URI;
use System\Package;
use System\Request;
use System\Response;
use System\Redirect;
use System\View;

class Route
{
    /**
     * Berisi URI yang sedang direspon oleh route.
     *
     * @var string
     */
    public $uri;

    /**
     * Berisi HTTP request method yang sedang direspon oleh route.
     *
     * @var string
     */
    public $method;

    /**
     * Berisi nama paket tempat rute didefinisikan.
     *
     * @var string
     */
    public $package;

    /**
     * Berisi nama controller yang digunakan oleh route.
     *
     * @var string
     */
    public $controller;

    /**
     * Berisi nama action controller yang digunakan oleh route.
     *
     * @var string
     */
    public $controller_action;

    /**
     * Berisi nama action milik si route.
     *
     * @var mixed
     */
    public $action;

    /**
     * Berisi parameter yang akan dioper ke callback route.
     *
     * @var array
     */
    public $parameters;

    /**
     * Buat instaance kelas route baru.
     *
     * @param string $method
     * @param string $uri
     * @param array  $action
     * @param array  $parameters
     */
    public function __construct($method, $uri, $action, $parameters = [])
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->action = $action;
        $this->package = Package::handles($uri);

        $this->parameters($action, $parameters);
    }

    /**
     * Set array parameter ke value yang valid.
     *
     * @param array $action
     * @param array $parameters
     */
    protected function parameters($action, $parameters)
    {
        $defaults = (array) Arr::get($action, 'defaults');

        if (count($defaults) > count($parameters)) {
            $defaults = array_slice($defaults, count($parameters));
            $parameters = array_merge($parameters, $defaults);
        }

        $this->parameters = $parameters;
    }

    /**
     * Eksekusi route beserta middleware miliknya dan return responnya.
     *
     * @return Response
     */
    public function call()
    {
        $response = Middleware::run($this->middlewares('before'), [], true);

        if (is_null($response)) {
            $response = $this->response();
        }

        $response = Response::prepare($response);

        Middleware::run($this->middlewares('after'), [&$response]);

        return $response;
    }

    /**
     * Eksekusi route dan return responnya.
     * Berbeda dengan method call(), tidak ada middleware yang akan dieksekusi.
     *
     * @return mixed
     */
    public function response()
    {
        $delegate = $this->delegate();

        if (! is_null($delegate)) {
            return Controller::call($delegate, $this->parameters);
        }

        $handler = $this->handler();

        if (! is_null($handler)) {
            return call_user_func_array($handler, $this->parameters);
        }
    }

    /**
     * Ambil middleware yang dilampirkan ke route untuk event tertentu.
     *
     * @param string $event
     *
     * @return array
     */
    protected function middlewares($event)
    {
        $global = Package::prefix($this->package).$event;
        $middlewares = array_unique([$event, $global]);

        if (isset($this->action[$event])) {
            $assigned = Middleware::parse($this->action[$event]);
            $middlewares = array_merge($middlewares, $assigned);
        }

        if ('before' === $event) {
            $middlewares = array_merge($middlewares, $this->patterns());
        }

        return [new Middlewares($middlewares)];
    }

    /**
     * Ambil pola middleware untuk route.
     *
     * @return array
     */
    protected function patterns()
    {
        $middlewares = [];

        foreach (Middleware::$patterns as $pattern => $middleware) {
            if (Str::is($pattern, $this->uri)) {
                if (is_array($middleware)) {
                    list($middleware, $callback) = array_values($middleware);

                    Middleware::register($middleware, $callback);
                }

                $middlewares[] = $middleware;
            }
        }

        return (array) $middlewares;
    }

    /**
     * Ambil nama action controller milik route
     * Jika actionnya tidak ditemukan, NULL akan direturn.
     *
     * @return string
     */
    protected function delegate()
    {
        return Arr::get($this->action, 'uses', null);
    }

    /**
     * Ambil closure yang menangani route.
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
     * Periksa apakah rute saat ini sesuai dengan nama yang diberikan.
     * (Digunakan pada named-route).
     *
     * <code>
     *
     *      // Periksa apakah rute saat ini bernama 'login'
     *      if (Request::route()->is('login')) {
     *          // Rute saat ini bernama 'login'
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
     * Daftarkan controller (auto-discovery).
     *
     * @param string|array $controllers
     * @param string|array $defaults
     */
    public static function controller($controllers, $defaults = 'index')
    {
        Router::controller($controllers, $defaults);
    }

    /**
     * Daftarkan sebuah route GET.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function get($route, $action)
    {
        Router::register('GET', $route, $action);
    }

    /**
     * Daftarkan sebuah route POST.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function post($route, $action)
    {
        Router::register('POST', $route, $action);
    }

    /**
     * Daftarkan sebuah route PUT.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function put($route, $action)
    {
        Router::register('PUT', $route, $action);
    }

    /**
     * Daftarkan sebuah route DELETE.
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function delete($route, $action)
    {
        Router::register('DELETE', $route, $action);
    }

    /**
     * Daftarkan sebuah route untuk semua tipe request (GET, POST, PUT, DELETE).
     *
     * @param string|array $route
     * @param mixed        $action
     */
    public static function any($route, $action)
    {
        Router::register('*', $route, $action);
    }

    /**
     * Daftarkan sebuah route group.
     *
     * @param array    $attributes
     * @param \Closure $callback
     */
    public static function group($attributes, \Closure $callback)
    {
        Router::group($attributes, $callback);
    }

    /**
     * Daftarkan sebuah action untuk menangani beberapa route sekaligus.
     *
     * @param array $routes
     * @param mixed $action
     */
    public static function share(array $routes, $action)
    {
        Router::share($routes, $action);
    }

    /**
     * Daftarkan sebuah middleware.
     *
     * @param string $name
     * @param mixed  $callback
     */
    public static function middleware($name, $callback)
    {
        Middleware::register($name, $callback);
    }

    /**
     * Panggil route yang diberikan dan return hasilnya (tanpa output ke browser).
     *
     * @param string $method
     * @param string $uri
     *
     * @return Response
     */
    public static function forward($method, $uri)
    {
        return Router::route(strtoupper($method), $uri)->call();
    }

    /**
     * Daftarkan sebuah view route.
     *
     * @param string $route
     * @param string $view
     * @param array  $data
     *
     * @return \System\View
     */
    public static function view($route, $view, $data = [])
    {
        static::get($route, function () use ($view, $data) {
            return View::make($view, $data);
        });
    }

    /**
     * Daftarkan sebuah redirect route.
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
}
