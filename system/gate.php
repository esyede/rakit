<?php

namespace System;

defined('DS') or exit('No direct access.');

class Gate
{
    /**
     * Contains the abilities, ability name => callback.
     *
     * @var array
     */
    protected static $abilities = [];

    /**
     * Contains the policies, model class => policy class.
     *
     * @var array
     */
    protected static $policies = [];

    /**
     * Contains the callbacks run before every check.
     *
     * @var array
     */
    protected static $befores = [];

    /**
     * Contains the callbacks run after every check.
     *
     * @var array
     */
    protected static $afters = [];

    /**
     * The user checked, when set through for_user().
     *
     * @var mixed
     */
    protected $user;

    /**
     * Whether $user was given, so a NULL user stays a guest instead of Auth::user().
     *
     * @var bool
     */
    protected $for_user = false;

    /**
     * Define an ability. The callback gets the user, then the check's arguments.
     * A 'Class@method' string is accepted too.
     *
     * @param string          $ability
     * @param \Closure|string $callback
     */
    public static function define($ability, $callback)
    {
        if (! ($callback instanceof \Closure) && ! (is_string($callback) && false !== strpos($callback, '@'))) {
            throw new \InvalidArgumentException(sprintf('Gate callback must be a Closure or "Class@method": %s', $ability));
        }

        static::$abilities[$ability] = $callback;
    }

    /**
     * Register a policy class for a model class.
     *
     * @param string $model
     * @param string $policy
     */
    public static function policy($model, $policy)
    {
        static::$policies[ltrim($model, '\\')] = ltrim($policy, '\\');
    }

    /**
     * Register a callback run before every check. A non-NULL return decides the check.
     *
     * @param \Closure $callback
     */
    public static function before(\Closure $callback)
    {
        static::$befores[] = $callback;
    }

    /**
     * Register a callback run after every check. A non-NULL return decides a check
     * nothing else decided.
     *
     * @param \Closure $callback
     */
    public static function after(\Closure $callback)
    {
        static::$afters[] = $callback;
    }

    /**
     * Check whether an ability has been defined.
     *
     * @param string|array $ability
     *
     * @return bool
     */
    public static function has($ability)
    {
        foreach ((array) $ability as $name) {
            if (! isset(static::$abilities[$name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the policy instance for a model, a model class name, or NULL.
     *
     * @param object|string $model
     *
     * @return object|null
     */
    public static function get_policy_for($model)
    {
        $class = is_object($model) ? get_class($model) : (is_string($model) ? ltrim($model, '\\') : null);

        if (is_null($class)) {
            return null;
        }

        if (isset(static::$policies[$class])) {
            $policy = static::$policies[$class];
            return new $policy();
        }

        foreach (static::$policies as $parent => $policy) {
            if (is_subclass_of($class, $parent)) {
                return new $policy();
            }
        }

        return null;
    }

    /**
     * Get a gate checking the given user instead of the logged in one.
     *
     * @param mixed $user
     *
     * @return Gate
     */
    public static function for_user($user)
    {
        $gate = new static();
        $gate->user = $user;
        $gate->for_user = true;

        return $gate;
    }

    /**
     * Forget every ability, policy and callback.
     */
    public static function flush()
    {
        static::$abilities = [];
        static::$policies = [];
        static::$befores = [];
        static::$afters = [];
    }

    /**
     * Check whether the user has the ability.
     *
     * @param string $ability
     * @param mixed  $arguments
     *
     * @return bool
     */
    protected function allows($ability, $arguments = [])
    {
        return $this->raw($ability, $arguments);
    }

    /**
     * Check whether the user lacks the ability.
     *
     * @param string $ability
     * @param mixed  $arguments
     *
     * @return bool
     */
    protected function denies($ability, $arguments = [])
    {
        return ! $this->raw($ability, $arguments);
    }

    /**
     * Check whether the user has every given ability.
     *
     * @param string|array $abilities
     * @param mixed        $arguments
     *
     * @return bool
     */
    protected function check($abilities, $arguments = [])
    {
        foreach ((array) $abilities as $ability) {
            if (! $this->raw($ability, $arguments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether the user has any of the given abilities.
     *
     * @param string|array $abilities
     * @param mixed        $arguments
     *
     * @return bool
     */
    protected function any($abilities, $arguments = [])
    {
        foreach ((array) $abilities as $ability) {
            if ($this->raw($ability, $arguments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the user has none of the given abilities.
     *
     * @param string|array $abilities
     * @param mixed        $arguments
     *
     * @return bool
     */
    protected function none($abilities, $arguments = [])
    {
        return ! $this->any($abilities, $arguments);
    }

    /**
     * Answer 403 unless the user has the ability.
     *
     * @param string $ability
     * @param mixed  $arguments
     *
     * @return bool
     */
    protected function authorize($ability, $arguments = [])
    {
        if (! $this->raw($ability, $arguments)) {
            abort(403);
        }

        return true;
    }

    /**
     * Run the check: before callbacks, then the policy or the ability, then after
     * callbacks. Anything undefined is denied.
     *
     * @param string $ability
     * @param mixed  $arguments
     *
     * @return bool
     */
    protected function raw($ability, $arguments)
    {
        $arguments = is_array($arguments) ? array_values($arguments) : [$arguments];
        $user = $this->for_user ? $this->user : Auth::user();
        $result = null;

        foreach (static::$befores as $before) {
            if ($this->callable_by($user, $before)) {
                $result = call_user_func($before, $user, $ability, $arguments);

                if (! is_null($result)) {
                    break;
                }
            }
        }

        if (is_null($result)) {
            $result = $this->resolve($user, $ability, $arguments);
        }

        foreach (static::$afters as $after) {
            if ($this->callable_by($user, $after)) {
                $answer = call_user_func($after, $user, $ability, $result, $arguments);
                $result = is_null($result) ? $answer : $result;
            }
        }

        return (bool) $result;
    }

    /**
     * Ask the policy of the first argument, or else the defined ability.
     *
     * @param mixed  $user
     * @param string $ability
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function resolve($user, $ability, array $arguments)
    {
        $policy = isset($arguments[0]) ? static::get_policy_for($arguments[0]) : null;

        if ($policy) {
            if (is_string($arguments[0])) {
                array_shift($arguments);
            }

            if (method_exists($policy, 'before') && $this->callable_by($user, [$policy, 'before'])) {
                $result = $policy->before($user, $ability);

                if (! is_null($result)) {
                    return $result;
                }
            }

            if (method_exists($policy, $ability)) {
                return $this->callable_by($user, [$policy, $ability])
                    ? call_user_func_array([$policy, $ability], array_merge([$user], $arguments))
                    : false;
            }
        }

        if (! isset(static::$abilities[$ability])) {
            return null;
        }

        $callback = static::$abilities[$ability];

        if (is_string($callback)) {
            list($class, $method) = explode('@', $callback, 2);
            $callback = [new $class(), $method];
        }

        return $this->callable_by($user, $callback)
            ? call_user_func_array($callback, array_merge([$user], $arguments))
            : false;
    }

    /**
     * Check whether the callback may run for this user: a guest only reaches
     * callbacks whose user parameter is optional, as in function ($user = null).
     *
     * @param mixed          $user
     * @param callable|array $callback
     *
     * @return bool
     */
    protected function callable_by($user, $callback)
    {
        if (! is_null($user)) {
            return true;
        }

        $reflection = is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);
        $parameters = $reflection->getParameters();

        return isset($parameters[0]) && $parameters[0]->isOptional();
    }

    /**
     * Run a check on the logged in user: Gate::allows('edit', $post).
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        $gate = new static();
        return $gate->__call($method, $parameters);
    }

    /**
     * Run a check on the user given to for_user().
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if (! in_array($method, ['allows', 'denies', 'check', 'any', 'none', 'authorize'], true)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        return call_user_func_array([$this, $method], $parameters);
    }
}
