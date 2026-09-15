<?php

namespace System;

defined('DS') or exit('No direct access.');

trait Macroable
{
    /**
     * Contains list of registered macros.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Add a new macro.
     *
     * @param string   $name
     * @param callable $handler
     *
     * @return void
     */
    public static function macro($name, $handler)
    {
        static::$macros[$name] = $handler;
    }

    /**
     * Add a new mixin.
     *
     * @param callable $mixin
     * @param bool     $replace
     *
     * @return void
     */
    public static function mixin($mixin, $replace = true)
    {
        $methods = (new \ReflectionClass($mixin))->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            if ($replace || ! static::has_macro($method->name)) {
                if (PHP_VERSION_ID < 80100) {
                    /* @disregard */
                    $method->setAccessible(true);
                }
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Check if macro is registered.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has_macro($name)
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Invoke static method.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        if (! static::has_macro($method)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        $macro = static::$macros[$method];
        // Running __CLASS__ inside a trait corrupts the constant table of PHP 5.4.0, a later
        // get_defined_constants(true) then crashes. get_class() names the same class there,
        // but is deprecated without an argument since PHP 8.3, hence the version check.
        $scope = (PHP_VERSION_ID < 50500) ? get_class() : __CLASS__;
        $macro = ($macro instanceof \Closure) ? \Closure::bind($macro, null, $scope) : $macro;
        return call_user_func_array($macro, $parameters);
    }

    /**
     * Invoke object.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if (! static::has_macro($method)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        $macro = static::$macros[$method];
        // See __callStatic() for why __CLASS__ is not used on PHP 5.4.
        $scope = (PHP_VERSION_ID < 50500) ? get_class() : __CLASS__;
        $macro = ($macro instanceof \Closure) ? $macro->bindTo($this, $scope) : $macro;
        return call_user_func_array($macro, $parameters);
    }
}
