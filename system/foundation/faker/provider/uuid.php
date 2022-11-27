<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

use System\Str;

class Uuid extends Base
{
    public static function uuid()
    {
        $bytes = bin2hex(Str::bytes(16));
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
