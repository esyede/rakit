<?php

namespace System;

defined('DS') or die('No direct access.');

class Optional implements \ArrayAccess
{
    /**
     * Container for the value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Access object properties dynamically.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return (is_object($this->value) && isset($this->value->{$key})) ? $this->value->{$key} : null;
    }

    /**
     * Check if property exists on object dynamically.
     *
     * @param mixed $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        if (is_object($this->value)) {
            return isset($this->value->{$name});
        }

        if (is_array($this->value) || ($this->value instanceof \ArrayObject)) {
            return isset($this->value[$name]);
        }

        return false;
    }

    /**
     * Call object methods dynamically.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        return is_object($this->value) ? call_user_func_array([$this->value, $method], $parameters) : null;
    }

    /**
     * Check if item exists in offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return Arr::accessible($this->value) && Arr::exists($this->value, $key);
    }

    /**
     * Get item based on offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return Arr::get($this->value, $key);
    }

    /**
     * Set item based on offset.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        if (Arr::accessible($this->value)) {
            $this->value[$key] = $value;
        }
    }

    /**
     * Unset item based on offset.
     *
     * @param string $key
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        if (Arr::accessible($this->value)) {
            unset($this->value[$key]);
        }
    }
}
