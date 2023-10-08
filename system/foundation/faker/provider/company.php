<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Company extends Base
{
    protected static $formats = ['{{lastName}} {{companySuffix}}'];
    protected static $companySuffix = ['Ltd', 'Pvt. Ltd', 'Co.'];

    public function company()
    {
        return $this->generator->parse(static::randomElement(static::$formats));
    }

    public static function companySuffix()
    {
        return static::randomElement(static::$companySuffix);
    }
}
