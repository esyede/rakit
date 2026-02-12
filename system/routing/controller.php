<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

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
     * The event name for controller factory.
     *
     * @var string
     */
    const FACTORY = 'rakit.controller.factory';

    /**
     * Contains the layout view for the controller.
     *
     * @var string
     */
    public $layout;

    /**
     * Contains the package identifier for the controller.
     *
     * @var string
     */
    public $package;

    /**
     * Indicates whether the controller is RESTful.
     *
     * @var bool
     */
    public $restful = false;

    /**
     * Contains the middlewares attached to the controller.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!is_null($this->layout)) {
            $this->layout = $this->layout();
        }
    }

    /**
     * Detect all controllers in a package.
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
     * Call a controller action statically.
     *
     * <code>
     *
     *      // Call User_Controller::show()
     *      $response = Controller::call('user@show');
     *
     *      // Call User_Admin_Controller::profile() and pass parameters
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
     * Replace back references in the destination string with parameter values.
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
     * Resolve a package name and controller name into a controller instance.
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
     * Load the controller file.
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
     * Format the package identifier and controller name into a class name.
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
     * Execute a controller action with given parameters and return the response.
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
     * Execute a controller action and return it's raw response.
     *
     * Unlike the execute() method, no middleware will be run
     * and the response from the controller action will not be modified before it is returned.
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
     * Attach middleware to the controller.
     *
     * <code>
     *
     *      // Set a middleware after 'foo' for all methods in the controller
     *      $this->middleware('before', 'foo');
     *
     *      // Set middleware after 'foo' and 'bar' for only some methods
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
     * Get the middlewares for a specific event and method.
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
     * Define the layout view for the controller.
     *
     * @return View
     */
    public function layout()
    {
        $layout = (string) $this->layout;
        return (0 === strpos($layout, 'name: ')) ? View::of(substr($layout, 6)) : View::make($layout);
    }

    /**
     * Validate the current request input against the given rules.
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
     * This method will be called before every request to this controller is executed.
     */
    public function before()
    {
        // ..
    }

    /**
     * This method will be called after every request to this controller is executed.
     *
     * @param Response $response
     */
    public function after($response)
    {
        // ..
    }

    /**
     * Handle invalid method calls.
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
     * Resolve objects from the container dynamically.
     *
     * @param string $method
     *
     * @return mixed
     *
     * <code>
     *
     *      // Resolve registered object 'mailer' from container
     *      $mailer = $this->mailer; // equivalent to:
     *      $mailer = Container::resolve('mailer');
     *
     * </code>
     */
    public function __get($key)
    {
        return Container::registered($key) ? Container::resolve($key) : null;
    }
}
