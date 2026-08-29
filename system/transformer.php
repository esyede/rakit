<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Transformer\Merged;
use System\Transformer\Missing;

class Transformer implements \ArrayAccess, \JsonSerializable
{
    /**
     * The key the transformed data is put under. Set it to NULL to hand the
     * data over without a wrapper.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * Contains the thing being transformed.
     *
     * @var mixed
     */
    public $resource;

    /**
     * Contains the data added at the call site.
     *
     * @var array
     */
    protected $additional = [];

    /**
     * Constructor.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Make a transformer for the given resource.
     *
     * @param mixed $resource
     *
     * @return static
     */
    public static function make($resource)
    {
        return new static($resource);
    }

    /**
     * Make a transformer for a list of resources.
     *
     * @param mixed $resources
     *
     * @return \System\Transformer\Collection
     */
    public static function collection($resources)
    {
        return new Transformer\Collection($resources, get_called_class());
    }

    /**
     * Hand the data over without a wrapper, everywhere.
     *
     * @return void
     */
    public static function without_wrapping()
    {
        static::$wrap = null;
    }

    /**
     * Shape the resource into the array that goes out. Override it to say
     * exactly which keys the response carries.
     *
     * @return array
     */
    public function to_array()
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        if (is_object($this->resource) && method_exists($this->resource, 'to_array')) {
            return $this->resource->to_array();
        }

        return (array) $this->resource;
    }

    /**
     * Get the data that sits beside the wrapped data, at the top level.
     *
     * @return array
     */
    public function with()
    {
        return [];
    }

    /**
     * Add data at the top level, from the call site.
     *
     * @param array $data
     *
     * @return $this
     */
    public function additional(array $data)
    {
        $this->additional = array_merge($this->additional, $data);

        return $this;
    }

    /**
     * Keep the value only when the condition holds. A key whose value is left
     * out does not appear in the response at all.
     *
     * @param mixed $condition
     * @param mixed $value
     * @param mixed $default
     *
     * @return mixed
     */
    public function when($condition, $value, $default = null)
    {
        if ($condition) {
            return ($value instanceof \Closure) ? $value() : $value;
        }

        if (func_num_args() < 3) {
            return new Missing();
        }

        return ($default instanceof \Closure) ? $default() : $default;
    }

    /**
     * Fold the values into the array around them, but only when the condition
     * holds.
     *
     * @param mixed $condition
     * @param array $values
     *
     * @return mixed
     */
    public function merge_when($condition, array $values)
    {
        return $condition ? new Merged($values) : new Missing();
    }

    /**
     * Fold the values into the array around them.
     *
     * @param array $values
     *
     * @return \System\Transformer\Merged
     */
    public function merge(array $values)
    {
        return new Merged($values);
    }

    /**
     * Drop the values that were left out and fold in the merged ones.
     *
     * @param array $data
     *
     * @return array
     */
    protected function filter(array $data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value instanceof Missing) {
                continue;
            }

            // A transformer nested inside another one contributes its data,
            // not a wrapper of its own.
            if ($value instanceof self) {
                $result[$key] = $value->filtered();
                continue;
            }

            if ($value instanceof Merged) {
                $merged = $this->filter($value->values);

                foreach ($merged as $index => $item) {
                    if (is_int($index)) {
                        $result[] = $item;
                    } else {
                        $result[$index] = $item;
                    }
                }

                continue;
            }

            $result[$key] = is_array($value) ? $this->filter($value) : $value;
        }

        return $result;
    }

    /**
     * Get the transformed data, with the values that were left out dropped.
     *
     * @return array
     */
    public function filtered()
    {
        return $this->filter($this->to_array());
    }

    /**
     * Get everything that goes out, wrapper and all.
     *
     * @return array
     */
    public function resolve()
    {
        $data = $this->filtered();
        $extras = array_merge($this->with(), $this->additional);
        $wrap = static::$wrap;

        if (is_null($wrap) || '' === $wrap) {
            return empty($extras) ? $data : array_merge($data, $extras);
        }

        return array_merge([$wrap => $data], $extras);
    }

    /**
     * Turn the transformer into a response.
     *
     * @param int   $status
     * @param array $headers
     *
     * @return \System\Response
     */
    public function to_response($status = 200, array $headers = [])
    {
        return Response::json($this->resolve(), $status, $headers);
    }

    /**
     * Convert the transformer into JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function to_json($options = 0)
    {
        return json_encode($this->resolve(), $options);
    }

    /**
     * Convert the transformer into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->resolve();
    }

    /**
     * Read an attribute of the resource.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (is_array($this->resource)) {
            return array_key_exists($key, $this->resource) ? $this->resource[$key] : null;
        }

        if (! is_object($this->resource)) {
            return null;
        }

        if (isset($this->resource->{$key}) || property_exists($this->resource, $key)) {
            return $this->resource->{$key};
        }

        return null;
    }

    /**
     * Determine if the resource has the attribute.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return is_array($this->resource)
            ? isset($this->resource[$key])
            : (is_object($this->resource) && isset($this->resource->{$key}));
    }

    /**
     * Call a method of the resource.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if (! is_object($this->resource) || ! method_exists($this->resource, $method)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        return call_user_func_array([$this->resource, $method], $parameters);
    }

    /**
     * Determine if the offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Get the value of the given offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * Set the value of the given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_array($this->resource)) {
            $this->resource[$offset] = $value;
        } elseif (is_object($this->resource)) {
            $this->resource->{$offset} = $value;
        }
    }

    /**
     * Unset the given offset.
     *
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if (is_array($this->resource)) {
            unset($this->resource[$offset]);
        } elseif (is_object($this->resource)) {
            unset($this->resource->{$offset});
        }
    }

    /**
     * Convert the transformer into JSON.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->to_json();
    }
}
