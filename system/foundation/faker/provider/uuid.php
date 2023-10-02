<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Uuid extends Base
{
    public static function uuid()
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(openssl_random_pseudo_bytes(16)), 4));
    }
}
