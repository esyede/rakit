<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

use System\Str;

class Uuid extends Base
{
    public static function uuid()
    {
        $bytes = bin2hex(Str::bytes(16));
        return sprintf(
            '%08s-%04s-4%03s-%04x-%012s',
            substr($bytes, 0, 8),
            substr($bytes, 8, 4),
            substr($bytes, 13, 3),
            hexdec(substr($bytes, 16, 4)) & 0x3fff | 0x8000,
            substr($bytes, 20, 12)
        );
    }
}
