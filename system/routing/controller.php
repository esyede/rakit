<?php

namespace System\Routing;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Str;
use System\View;
use System\Input;
use System\Event;
use System\Package;
use System\Request;
use System\Response;
use System\Redirect;
use System\Container;
use System\Validator;

abstract class Controller
{
    /**
     * Nama event untuk controller factory rakit.
     *
     * @var string
     */
    const FACTORY = 'rakit.controller.factory';

    /**
     * Berisi layout yang sedang digunakan oleh controller.
     *
     * @var string
     */
    public $layout;

    /**
     * Berisi nama paket pemilik controller.
     *
     * @var string
     */
    public $package;

    /**
     * Indikasi bahwa controller menggunakan RESTful routing.
     *
     * @var bool
     */
    public $restful = false;

    /**
     * Berisi list middleware yang dilampirkan ke controller ini.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Buat instance Controller baru.
     */
    public function __construct()
    {
        if (!is_null($this->layout)) {
            $this->layout = $this->layout();
        }
    }

    /**
     * Deteksi seluruh controller milik paket yang diberikan.
     *
     * @param string $package
     * @param string $directory
     *
     * @return array
     */
    public static function detect($package = DEFAULT_PACKAGE, $directory = null)
    {
        $root = Package::path($package) . 'controllers';
        $directory = is_null($directory) ? $root : $directory;
        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        $controllers = [];

        foreach ($items as $item) {
            if ($item->isDir()) {
                $nested = static::detect($package, $item->getRealPath());
                $controllers = array_merge($controllers, $nested);
            } else {
                $controller = str_replace([$root . DS, '.php'], '', $item->getRealPath());
                $controller = str_replace(DS, '.', $controller);
                $controllers[] = Package::identifier($package, $controller);
            }
        }

        return $controllers;
    }

    /**
     * Panggil sebuah method action milik controller.
     *
     * <code>
     *
     *      // Panggil method User_Controller::show()
     *      $response = Controller::call('user@show');
     *
     *      // Panggil method User_Admin_Controller::profile() dan oper parameter
     *      $response = Controller::call('user.admin@profile', [$name]);
     *
     * </code>
     *
     * @param string $destination
     * @param array  $parameters
     *
     * @return Response
     */
    public static function call($destination, array $parameters = [])
    {
        static::references($destination, $parameters);
        list($package, $destination) = Package::parse($destination);

        Package::boot($package);

        list($name, $method) = explode('@', $destination);
        $controller = static::resolve($package, $name);

        if (!is_null($route = Request::route())) {
            $route->controller = $name;
            $route->controller_action = $method;
        }

        return is_null($controller)
            ? Event::first('404')
            : $controller->execute($method, $parameters);
    }

    /**
     * Ganti seluruh back-reference milik rute.
     *
     * @param string $destination
     * @param array  $parameters
     *
     * @return array
     */
    protected static function references(&$destination, array &$parameters)
    {
        foreach ($parameters as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $destination = str_replace('(:' . ($key + 1) . ')', $value, $destination, $count);

            if ($count > 0) {
                unset($parameters[$key]);
            }
        }

        return [$destination, $parameters];
    }

    /**
     * Resolve nama paket dan nama controller menjadi instance kelas controller.
     *
     * @param string $package
     * @param string $controller
     *
     * @return Controller
     */
    public static function resolve($package, $controller)
    {
        if (!static::load($package, $controller)) {
            return;
        }

        $identifier = Package::identifier($package, $controller);
        $identifier = 'controller: ' . $identifier;

        if (Container::registered($identifier)) {
            return Container::resolve($identifier);
        }

        $controller = static::format($package, $controller);

        return Event::exists(static::FACTORY)
            ? Event::first(static::FACTORY, [$controller])
            : new $controller();
    }

    /**
     * Muat file controller.
     *
     * @param string $package
     * @param string $controller
     *
     * @return bool
     */
    protected static function load($package, $controller)
    {
        $controller = strtolower(str_replace(['.', '/'], DS, (string) $controller));
        $controller = Package::path($package) . 'controllers' . DS . $controller . '.php';

        if (is_file($controller)) {
            require_once $controller;
            return true;
        }

        return false;
    }

    /**
     * Format identifier paket dan controller menjadi nama kelas controller.
     *
     * @param string $package
     * @param string $controller
     *
     * @return string
     */
    protected static function format($package, $controller)
    {
        return Package::class_prefix($package) . Str::classify($controller) . '_Controller';
    }

    /**
     * Eksekusi method controller dengan parameter yang diberikan.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return Response
     */
    public function execute($method, array $parameters = [])
    {
        $middlewares = $this->middlewares('before', $method);
        $response = Middleware::run($middlewares, [], true);

        if (is_null($response)) {
            $this->before();
            $response = $this->response($method, $parameters);
        }

        $response = Response::prepare($response);

        $this->after($response);

        Middleware::run($this->middlewares('after', $method), [$response]);

        return $response;
    }

    /**
     * Eksekusi action controller dan return responnya.
     *
     * Berbeda dengan method execute(), tidak akan ada middleware yang akan dijalankan
     * dan respon dari action controllertidak akan diubah sebelum ia direturn.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function response($method, array $parameters = [])
    {
        $action = $this->restful ? strtolower(Request::method()) . '_' . $method : 'action_' . $method;
        $response = call_user_func_array([$this, $action], $parameters);
        return (is_null($response) && !is_null($this->layout)) ? $this->layout : $response;
    }

    /**
     * Lampirkan middleware ke controller.
     *
     * <code>
     *
     *      // Set sebuah middleware after 'foo' untuk seluruh method di controller
     *      $this->middleware('before', 'foo');
     *
     *      // Set middleware after 'foo' dan 'bar' hanya untuk bebrapa method saja
     *      $this->middleware('after', 'foo|bar')->only(['user', 'profile']);
     *
     * </code>
     *
     * @param string       $event
     * @param string|array $middlewares
     * @param mixed        $parameters
     *
     * @return middlewareator
     */
    protected function middleware($event, $middlewares, $parameters = null)
    {
        $this->middlewares[$event][] = new Middlewares($middlewares, $parameters);
        return $this->middlewares[$event][count($this->middlewares[$event]) - 1];
    }

    /**
     * Ambil list nama middleware yang dilampirkan ke method.
     *
     * @param string $event
     * @param string $method
     *
     * @return array
     */
    protected function middlewares($event, $method)
    {
        if (!isset($this->middlewares[$event])) {
            return [];
        }

        $middlewares = [];

        foreach ($this->middlewares[$event] as $middleware) {
            if ($middleware->applies($method)) {
                $middlewares[] = $middleware;
            }
        }

        return $middlewares;
    }

    /**
     * Definisikan view layout untuk controller saat ini.
     *
     * @return View
     */
    public function layout()
    {
        $layout = (string) $this->layout;
        return (0 === strpos($layout, 'name: ')) ? View::of(substr($layout, 6)) : View::make($layout);
    }

    /**
     * Validasi input.
     *
     * @param array $rules
     *
     * @return \System\Redirect|null
     */
    public function validate(array $rules)
    {
        if (!Arr::associative($rules)) {
            throw new \Exception('Validation rules should be an associative array.');
        }

        $validation = Validator::make(Input::all(), $rules);

        if ($validation->fails()) {
            return Redirect::back()->with_input()->with_errors($validation);
        }
    }

    /**
     * Method ini akan terpanggil sebelum setiap request ke
     * controller ini dieksekusi.
     */
    public function before()
    {
        // ..
    }

    /**
     * Method ini akan terpanggil sebelum setiap request ke
     * controller ini dieksekusi.
     *
     * @param Response $response
     */
    public function after($response)
    {
        // ..
    }

    /**
     * Handle request yang tidak cocok dengan definsi rute yang ada.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return Response
     */
    public function __call($method, array $parameters)
    {
        return Response::error(404);
    }

    /**
     * Resolve item dari Container secara dinamis.
     *
     * @param string $method
     *
     * @return mixed
     *
     * <code>
     *
     *      // Resolve object yang terdaftar di container (cara 1)
     *      $mailer = $this->mailer;
     *
     *      // Resolve object yang terdaftar di container (cara 2)
     *      $mailer = Container::resolve('mailer');
     *
     * </code>
     */
    public function __get($key)
    {
        return Container::registered($key) ? Container::resolve($key) : null;
    }
}
