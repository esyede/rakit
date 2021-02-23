<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Company extends Base
{
    protected static $formats = [
        '{{lastName}} {{companySuffix}}',
    ];

    protected static $companySuffix = ['Ltd', 'Pvt. Ltd', 'Co.'];

    public function company()
    {
        $format = static::randomElement(static::$formats);

        return $this->generator->parse($format);
    }

    public static function companySuffix()
    {
        return static::randomElement(static::$companySuffix);
    }
}
