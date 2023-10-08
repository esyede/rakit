<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Phone extends Base
{
    protected static $formats = ['###-###-###'];

    public static function phoneNumber()
    {
        return static::numerify(static::randomElement(static::$formats));
    }
}
