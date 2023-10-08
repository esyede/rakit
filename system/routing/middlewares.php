<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Package;
use System\Request;

class Middlewares
{
    /**
     * Berisi list seluruh middleware yang terdaftar.
     *
     * @var string|array
     */
    public $middlewares = [];

    /**
     * Berisi parameter yng di oper ke middleware.
     *
     * @var mixed
     */
    public $parameters;

    /**
     * Berisi list nama method controller untuk middleware only().
     *
     * @var array
     */
    public $only = [];

    /**
     * Berisi list nama method controller untuk middleware except().
     *
     * @var array
     */
    public $except = [];

    /**
     * Berisi list nama http requset method untuk middleware on().
     *
     * @var array
     */
    public $methods = [];

    /**
     * Buat instance Middlewares baru.
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
     * Parse string middleware menjadi nama middleware dan parameternya.
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
     * Evaluasi parameter middleware.
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
     * Periksa apakah middleware yang diberikan berlaku pada method controller yang diberikan.
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
     * Set method controller yang harus dikeculikan dari middleware.
     * Nama - nama method ini tidak akan dilampiri middleware.
     *
     * <code>
     *
     *      // Lampirkan middleware ke semua method selain 'index'
     *      $this->middleware('before', 'auth')->except('index');
     *
     *      // Lampirkan middleware ke semua method selain 'index' dan 'home'
     *      $this->middleware('before', 'auth')->except(['index', 'home']);
     *
     *      $this->middleware('before', 'auth')->except('index', 'home');
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
     * Set HTTP method yang harus dimiddleware.
     *
     * <code>
     *
     *      // Set agar middleware hanya berjalan di POST saja
     *      $this->middleware('before', 'csrf')->on('post');
     *
     *      // Set agar middleware hanya berjalan di POST dan PUT
     *      $this->middleware('before', 'csrf')->on(['post', 'put']);
     *
     *      $this->middleware('before', 'csrf')->on('post', 'put');
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
