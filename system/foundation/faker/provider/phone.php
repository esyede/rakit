<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Phone extends Base
{
    protected static $formats = ['###-###-###'];

    public function phoneNumber()
    {
        return static::numerify($this->generator->parse(static::randomElement(static::$formats)));
    }
}
