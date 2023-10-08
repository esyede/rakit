<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Common
{
    protected $default;

    public function __construct($default = null)
    {
        $this->default = $default;
    }

    public function __get($attribute)
    {
        return $this->default;
    }

    public function __call($method, array $attributes)
    {
        return $this->default;
    }
}
