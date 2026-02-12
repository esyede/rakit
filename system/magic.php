<?php

namespace System;

defined('DS') or exit('No direct access.');

class Magic
{
    /**
     * Contains attributes set to container.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Create a new magic container instance.
     *
     * <code>
     *
     *      // Create a new magic container instance with additional attributes.
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
     * Get value attribute from magic container.
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
     * Handle dynamic attribute setting.
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
     * Get value attribute dynamically.
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * Set value attribute dynamically.
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check dynamically if attribute value is set.
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Unset value attribute dynamically.
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
