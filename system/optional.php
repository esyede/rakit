<?php

namespace System;

defined('DS') or die('No direct script access.');

class Optional implements \ArrayAccess
{
    /**
     * Penampung data.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Buat instance baru.
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
     *  Akses properti objek secara dinamis.
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
     * Cek apakah properti ada pada objek secara dinamis.
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

        if (is_array($this->value) || $this->value instanceof \ArrayObject) {
            return isset($this->value[$name]);
        }

        return false;
    }

    /**
     * Panggil method objek secara dinamis.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        return is_object($this->value)
            ? call_user_func_array([$this->value, $method], $parameters)
            : null;
    }

    /**
     * Cek apakah item ada dalam offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return Arr::accessible($this->value) && Arr::exists($this->value, $key);
    }

    /**
     * Ambil item berdasarkan offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return Arr::get($this->value, $key);
    }

    /**
     * Set item berdasarkan offset.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (Arr::accessible($this->value)) {
            $this->value[$key] = $value;
        }
    }

    /**
     * Unset item berdasarkan offset.
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        if (Arr::accessible($this->value)) {
            unset($this->value[$key]);
        }
    }
}
