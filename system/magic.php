<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Magic
{
    /**
     * Berisi atribut-atribut yang di-set ke container.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Buat instance magic container baru.
     *
     * <code>
     *
     *      // Buat instance magic container baru denngan atribut tambahan.
     *      $magic = new Magic(['name' => 'Budi']);
     *
     * </code>
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Ambil value atribut dari magic container.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($attribute, $default = null)
    {
        return Arr::get($this->attributes, $attribute, $default);
    }

    /**
     * Tangani pemanggilan set atribut secara dinamis.
     *
     * <code>
     *
     *      // Set value beberapa atribut sekaligus
     *      $magic->name('Budi')->age(25);
     *
     *      // Set value sebuah atribut ke true (boolean)
     *      $magic->nullable()->name('Budi');
     *
     * </code>
     */
    public function __call($method, array $parameters)
    {
        $this->{$method} = (count($parameters) > 0) ? $parameters[0] : true;
        return $this;
    }

    /**
     * Ambil value atribut secara dinamis.
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * Set value atribut secara dinamis.
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Cek secara dinamis value atribut sudah di-set atau belum.
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Unset value atribut secara dinamis.
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
