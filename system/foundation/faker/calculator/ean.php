<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct script access.');

class Ean
{
    public static function checksum($digits)
    {
        $length = strlen($digits);
        $even = 0;

        for ($i = $length - 1; $i >= 0; $i -= 2) {
            $even += $digits[$i];
        }

        $odd = 0;

        for ($i = $length - 2; $i >= 0; $i -= 2) {
            $odd += $digits[$i];
        }

        return (10 - ((3 * $even + $odd) % 10)) % 10;
    }

    public static function isValid($ean)
    {
        if (! preg_match('/^(?:\d{8}|\d{13})$/', $ean)) {
            return false;
        }

        return self::checksum(substr($ean, 0, -1)) === (int) substr($ean, -1);
    }
}
