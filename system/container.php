<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Container
{
    /**
     * Berisi list dependensi terdaftar.
     *
     * @var array
     */
    public static $registry = [];

    /**
     * Berisi instance singleton yang telah diresolve.
     *
     * @var array
     */
    public static $singletons = [];

    /**
     * Daftarkan objek berikut resolvernya.
     *
     * @param string $name
     * @param mixed  $resolver
     * @param bool   $singleton
     */
    public static function register($name, $resolver = null, $singleton = false)
    {
        $resolver = is_null($resolver) ? $name : $resolver;
        static::$registry[$name] = compact('resolver', 'singleton');
    }

    /**
     * Periksa apakah objek sudah terdaftar di container atau belum.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function registered($name)
    {
        return array_key_exists($name, static::$registry);
    }

    /**
     * Daftarkan sebuah objek (singleton).
     * Singleton hanya akan diinstansiasi sekali saja, saat objek itu diresolve.
     *
     * @param string   $name
     * @param \Closure $resolver
     */
    public static function singleton($name, \Closure $resolver = null)
    {
        static::register($name, $resolver, true);
    }

    /**
     * Daftarkan instance yang sudah ada sebagai singleton.
     *
     * <code>
     *
     *      // Daftarkan instance mailer sebagai singleton.
     *      Container::instance('mailer', new Mailer());
     *
     * </code>
     *
     * @param string $name
     * @param mixed  $instance
     */
    public static function instance($name, $instance)
    {
        static::$singletons[$name] = $instance;
    }

    /**
     * Resolve nama yang diberikan menjadi sebuah instance objek.
     *
     * <code>
     *
     *      // Ambil instance objek 'mailer'
     *      $mailer = Container::resolve('mailer');
     *
     *      // Ambil instance objek 'mailer' dan oper sebuah parameter
     *      $mailer = Container::resolve('mailer', ['test']);
     *
     * </code>
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function resolve($type, array $parameters = [])
    {
        if (isset(static::$singletons[$type])) {
            return static::$singletons[$type];
        }

        if (isset(static::$registry[$type])) {
            $concrete = Arr::get(static::$registry[$type], 'resolver', $type);
        } else {
            $concrete = $type;
        }

        if ($concrete === $type || $concrete instanceof \Closure) {
            $object = static::build($concrete, $parameters);
        } else {
            $object = static::resolve($concrete);
        }

        if (isset(static::$registry[$type]['singleton'])
        && true === static::$registry[$type]['singleton']) {
            static::$singletons[$type] = $object;
        }

        Event::fire('rakit.resolving', [$type, $object]);

        return $object;
    }

    /**
     * Instansiasi tipe objek yang diberikan.
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return mixed
     */
    protected static function build($type, array $parameters = [])
    {
        if ($type instanceof \Closure) {
            return call_user_func_array($type, $parameters);
        }

        $reflector = new \ReflectionClass($type);

        if (! $reflector->isInstantiable()) {
            throw new \Exception(sprintf('Resolution target is not instantiable: %s', $type));
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $type();
        }

        $dependencies = static::dependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve seluruh dependensi dari ReflectionParameters.
     *
     * @param array $parameters
     * @param array $arguments
     *
     * @return array
     */
    protected static function dependencies(array $parameters, array $arguments)
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            if (PHP_VERSION_ID >= 80000) {
                $dependency = $parameter->getType();
            } else {
                $dependency = $parameter->getClass();
            }

            if (count($arguments) > 0) {
                $dependencies[] = array_shift($arguments);
            } elseif (is_null($dependency)) {
                $dependencies[] = static::resolve_non_class($parameter);
            } else {
                $dependencies[] = static::resolve($dependency->getName());
            }
        }

        return $dependencies;
    }

    /**
     * Resolve parameter opsional untuk dependency injection kita.
     *
     * @param \ReflectionParameter $paameter
     *
     * @return mixed
     */
    protected static function resolve_non_class(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception(sprintf('Unresolvable dependency resolving: %s', $parameter));
    }
}
