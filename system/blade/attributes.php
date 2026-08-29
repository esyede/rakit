<?php

namespace System\Blade;

defined('DS') or exit('No direct access.');

class Attributes implements \ArrayAccess, \Countable, \IteratorAggregate, \System\Htmlable
{
    /**
     * Contains the attributes the tag was given.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get every attribute.
     *
     * @return array
     */
    public function all()
    {
        return $this->attributes;
    }

    /**
     * Get one attribute.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : $default;
    }

    /**
     * Determine if the attribute is there.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public function has($key)
    {
        foreach ((array) $key as $name) {
            if (! array_key_exists($name, $this->attributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Keep only the given attributes.
     *
     * @param string|array $keys
     *
     * @return static
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : array_slice(func_get_args(), 0);
        $result = [];

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $keys, true)) {
                $result[$key] = $value;
            }
        }

        return new static($result);
    }

    /**
     * Drop the given attributes.
     *
     * @param string|array $keys
     *
     * @return static
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : array_slice(func_get_args(), 0);
        $result = [];

        foreach ($this->attributes as $key => $value) {
            if (! in_array($key, $keys, true)) {
                $result[$key] = $value;
            }
        }

        return new static($result);
    }

    /**
     * Keep the attributes whose name starts with the given string, with that
     * start taken off their names.
     *
     * @param string $prefix
     *
     * @return static
     */
    public function starting_with($prefix)
    {
        $result = [];
        $length = strlen($prefix);

        foreach ($this->attributes as $key => $value) {
            if (0 === strncmp($key, $prefix, $length)) {
                $result[substr($key, $length)] = $value;
            }
        }

        return new static($result);
    }

    /**
     * Put the given attributes underneath the ones the tag was given. The class
     * attribute is joined instead of replaced, the way a class list should be.
     *
     * @param array $defaults
     *
     * @return static
     */
    public function merge(array $defaults = [])
    {
        $result = $defaults;

        foreach ($this->attributes as $key => $value) {
            if ('class' === $key && isset($defaults['class'])) {
                $result[$key] = trim($defaults['class'] . ' ' . $value);
                continue;
            }

            $result[$key] = $value;
        }

        return new static($result);
    }

    /**
     * Build the class attribute out of the given list. A key is kept when its
     * value holds, a plain entry is always kept.
     *
     * @param array|string $classes
     *
     * @return static
     */
    public function class_names($classes)
    {
        $result = [];

        foreach ((array) $classes as $key => $value) {
            if (is_int($key)) {
                $result[] = $value;
            } elseif ($value) {
                $result[] = $key;
            }
        }

        return $this->merge(['class' => implode(' ', $result)]);
    }

    /**
     * Build the html of the attributes.
     *
     * @return string
     */
    public function to_html()
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            if (false === $value || is_null($value)) {
                continue;
            }

            if (true === $value) {
                $result[] = e($key);
                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            $result[] = e($key) . '="' . e((string) $value) . '"';
        }

        return implode(' ', $result);
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
        return array_key_exists($offset, $this->attributes);
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
        return $this->get($offset);
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
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
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
        unset($this->attributes[$offset]);
    }

    /**
     * Count the attributes.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->attributes);
    }

    /**
     * Get an iterator for the attributes.
     *
     * @return \Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * Build the html of the attributes.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->to_html();
    }
}
