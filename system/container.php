<?php

namespace System;

defined('DS') or exit('No direct access.');

class Container
{
    /**
     * Contains registered dependencies.
     *
     * @var array
     */
    public static $registry = [];

    /**
     * Contains resolved singletons.
     *
     * @var array
     */
    public static $singletons = [];

    /**
     * Register an object with its resolver.
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
     * Check if an object is registered in the container.
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
     * Register a singleton object.
     * Singleton will only be instantiated once, when the object is resolved.
     *
     * @param string   $name
     * @param \Closure $resolver
     */
    public static function singleton($name, $resolver = null)
    {
        static::register($name, $resolver, true);
    }

    /**
     * Register an instance as a singleton.
     *
     * <code>
     *
     *      // Register instance mailer as a singleton.
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
     * Resolve a name into an object instance.
     *
     * <code>
     *
     *      // Get the 'mailer' instance
     *      $mailer = Container::resolve('mailer');
     *
     *      // Get the 'mailer' instance and pass a parameter
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

        $resolver = isset(static::$registry[$type])
            ? Arr::get(static::$registry[$type], 'resolver', $type)
            : $type;

        $object = ($resolver === $type || ($resolver instanceof \Closure))
            ? static::build($resolver, $parameters)
            : static::resolve($resolver);

        if (isset(static::$registry[$type]['singleton']) && static::$registry[$type]['singleton']) {
            static::$singletons[$type] = $object;
        }

        Event::fire('rakit.resolving', [$type, $object]);

        return $object;
    }

    /**
     * Instantiate a type.
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

        if (!$reflector->isInstantiable()) {
            throw new \Exception(sprintf('Resolution target is not instantiable: %s', $type));
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $type();
        }

        return $reflector->newInstanceArgs(static::dependencies($constructor->getParameters(), $parameters));
    }

    /**
     * Resolve dependencies for a \ReflectionParameter.
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
            $dependency = (PHP_VERSION_ID >= 80000) ? $parameter->getType() : $parameter->getClass();
            $dependencies[] = (count($arguments) > 0)
                ? array_shift($arguments)
                : (is_null($dependency)
                    ? static::resolve_non_class($parameter)
                    : static::resolve($dependency->getName())
                );
        }

        return $dependencies;
    }

    /**
     * Resolve optional parameter for dependency injection.
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
