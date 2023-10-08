<?php

namespace System;

defined('DS') or exit('No direct script access.');

trait Macroable
{
    /**
     * List macro terdaftar.
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * Tambahkan macro baru.
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
     * Tambahkan mixin baru.
     *
     * @param callable $mixin
     * @param bool     $replace
     *
     * @return void
     */
    public static function mixin($mixin, $replace = true)
    {
        $methods = (new \ReflectionClass($mixin))
            ->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            if ($replace || !static::has_macro($method->name)) {
                $method->setAccessible(true);
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Cek apakah macro sudah terdaftar.
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
     * Invoke static.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        if (!static::has_macro($method)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        $macro = static::$macros[$method];
        $macro = ($macro instanceof \Closure) ? \Closure::bind($macro, null, __CLASS__) : $macro;
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
        if (!static::has_macro($method)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        $macro = static::$macros[$method];
        $macro = ($macro instanceof \Closure) ? $macro->bindTo($this, __CLASS__) : $macro;
        return call_user_func_array($macro, $parameters);
    }
}
