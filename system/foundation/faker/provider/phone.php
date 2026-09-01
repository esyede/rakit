<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Phone extends Base
{
    protected static $formats = ['###-###-###'];

    /**
     * Generate a random phone number.
     *
     * @return string
     */
    public function phoneNumber()
    {
        return static::numerify($this->generator->parse(static::randomElement(static::$formats)));
    }
}
