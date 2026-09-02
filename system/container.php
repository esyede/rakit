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
     * Names currently being resolved, used to notice a cycle before it turns
     * into an exhausted stack.
     *
     * @var array
     */
    protected static $building = [];

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
        return array_key_exists($name, static::$registry) || array_key_exists($name, static::$singletons);
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
     * @param string $name
     * @param mixed  $instance
     */
    public static function instance($name, $instance)
    {
        static::$singletons[$name] = $instance;
    }

    /**
     * Flush per-request singletons and building tracker (worker bridges).
     *
     * @return void
     */
    public static function flush()
    {
        static::$singletons = [];
        static::$building = [];
    }

    /**
     * Resolve a name into an object instance.
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

        $tracked = is_string($type);

        if ($tracked && isset(static::$building[$type])) {
            $chain = implode(' -> ', array_keys(static::$building));
            static::$building = [];

            throw new \Exception(sprintf('Circular dependency while resolving: %s (%s)', $type, $chain));
        }

        if ($tracked) {
            static::$building[$type] = true;
        }

        try {
            $object = static::make($type, $parameters);
        } catch (\Throwable $e) {
            if ($tracked) {
                unset(static::$building[$type]);
            }

            throw $e;
        } catch (\Exception $e) {
            if ($tracked) {
                unset(static::$building[$type]);
            }

            throw $e;
        }

        if ($tracked) {
            unset(static::$building[$type]);
        }

        if (isset(static::$registry[$type]['singleton']) && static::$registry[$type]['singleton']) {
            static::$singletons[$type] = $object;
        }

        Hook::fire('rakit.resolving', [$type, $object]);

        return $object;
    }

    /**
     * Turn a name into an object, following an alias when one is registered.
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return mixed
     */
    protected static function make($type, array $parameters)
    {
        $resolver = isset(static::$registry[$type])
            ? Arr::get(static::$registry[$type], 'resolver', $type)
            : $type;

        return ($resolver === $type || ($resolver instanceof \Closure))
            ? static::build($resolver, $parameters)
            : static::resolve($resolver);
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

        if (! $reflector->isInstantiable()) {
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
            if (count($arguments) > 0) {
                $dependencies[] = array_shift($arguments);
                continue;
            }

            $dependency = static::class_name($parameter);
            $dependencies[] = is_null($dependency)
                ? static::resolve_non_class($parameter)
                : static::resolve($dependency);
        }

        return $dependencies;
    }

    /**
     * Get the class name a parameter is type-hinted with, if any.
     * Built-in types (int, string, array, ...), union types and intersection
     * types have no class to resolve, so they yield NULL.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return string|null
     */
    protected static function class_name(\ReflectionParameter $parameter)
    {
        if (PHP_VERSION_ID < 70100) {
            $class = $parameter->getClass();
            return is_null($class) ? null : $class->getName();
        }

        $type = $parameter->getType();

        if (! ($type instanceof \ReflectionNamedType) || $type->isBuiltin()) {
            return;
        }

        $name = $type->getName();

        if ('self' === $name || 'static' === $name) {
            $class = $parameter->getDeclaringClass();
            return is_null($class) ? null : $class->getName();
        }

        return $name;
    }

    /**
     * Resolve optional parameter for dependency injection.
     *
     * @param \ReflectionParameter $parameter
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
