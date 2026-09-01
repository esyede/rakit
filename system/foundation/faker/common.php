<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Common
{
    /** @var mixed */
    protected $default;

    /**
     * Create a new Common instance.
     *
     * @param mixed $default
     */
    public function __construct($default = null)
    {
        $this->default = $default;
    }

    /**
     * Get the default value.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        return $this->default;
    }

    /**
     * Call a method and return the default value.
     *
     * @param string $method
     * @param array  $attributes
     *
     * @return mixed
     */
    public function __call($method, array $attributes)
    {
        return $this->default;
    }
}
