<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Package;
use System\Request;

class Middlewares
{
    /**
     * Contains the list of middlewares.
     *
     * @var string|array
     */
    public $middlewares = [];

    /**
     * Contains the middleware parameters.
     *
     * @var mixed
     */
    public $parameters;

    /**
     * Contains the list of controller method names for middleware only().
     *
     * @var array
     */
    public $only = [];

    /**
     * Contains the list of controller method names for middleware except().
     *
     * @var array
     */
    public $except = [];

    /**
     * Contains the list of controller method names for middleware on().
     *
     * @var array
     */
    public $methods = [];

    /**
     * Constructor.
     *
     * @param string|array $middlewares
     * @param mixed        $parameters
     */
    public function __construct($middlewares, $parameters = null)
    {
        $this->parameters = $parameters;
        $this->middlewares = Middleware::parse($middlewares);
    }

    /**
     * Parse middleware string and return the middleware name and it's parameters.
     *
     * @param string $middleware
     *
     * @return array
     */
    public function get($middleware)
    {
        if (!is_null($this->parameters)) {
            return [$middleware, $this->parameters()];
        }

        $element = (string) Package::element($middleware);

        if (false !== ($colon = strpos($element, ':'))) {
            $parameters = explode(',', substr($element, $colon + 1));

            if (DEFAULT_PACKAGE !== ($package = Package::name($middleware))) {
                $colon = mb_strlen($package . '::', '8bit') + $colon;
            }

            return [substr((string) $middleware, 0, $colon), $parameters];
        }

        return [$middleware, []];
    }

    /**
     * Evaluate the parameters if it's a Closure.
     *
     * @return array
     */
    protected function parameters()
    {
        if ($this->parameters instanceof \Closure) {
            $this->parameters = call_user_func($this->parameters);
        }

        return $this->parameters;
    }

    /**
     * Check if the middleware applies to the given method.
     *
     * @param string $method
     *
     * @return bool
     */
    public function applies($method)
    {
        if (count($this->only) > 0 && !in_array($method, $this->only)) {
            return false;
        }

        if (count($this->except) > 0 && in_array($method, $this->except)) {
            return false;
        }

        $method = strtolower((string) Request::method());
        return count($this->methods) < 1 || in_array($method, $this->methods);
    }

    /**
     * Set the controller method names to be excluded.
     * These method names will not be attached with middleware.
     *
     * <code>
     *
     *      // Attach middleware to all methods except 'index'
     *      $this->middleware('before', 'auth')->except('index');
     *
     *      // Attach middleware to all methods except 'index' and 'home'
     *      $this->middleware('before', 'auth')->except(['index', 'home']);
     *
     * </code>
     *
     * @param array $methods
     *
     * @return Middlewares
     */
    public function except($methods)
    {
        $this->except = is_array($methods) ? $methods : func_get_args();
        return $this;
    }

    /**
     * Kebalikan dari method except().
     * Hanya nama - nama method ini yang akan dilampiri middleware.
     *
     * <code>
     *
     *      // Set middleware hanya untuk method "index" saja
     *      $this->middleware('before', 'auth')->only('index');
     *
     *      // Set middleware hanya untuk method "index" dan "home" saja
     *      $this->middleware('before', 'auth')->only(['index', 'home']);
     *
     *      $this->middleware('before', 'auth')->only('index', 'home');
     *
     * </code>
     *
     * @param array $methods
     *
     * @return Middlewares
     */
    public function only($methods)
    {
        $this->only = is_array($methods) ? $methods : func_get_args();
        return $this;
    }

    /**
     * Set the HTTP methods for which the middleware will run.
     *
     * <code>
     *
     *      // Set middleware to run only on POST requests
     *      $this->middleware('before', 'csrf')->on('post');
     *
     *      // Set middleware to run only on POST and PUT requests
     *      $this->middleware('before', 'csrf')->on(['post', 'put']);
     *
     * </code>
     *
     * @param array $methods
     *
     * @return Middlewares
     */
    public function on($methods)
    {
        $methods = is_array($methods) ? $methods : func_get_args();
        $this->methods = array_map('strtolower', $methods);
        return $this;
    }
}
