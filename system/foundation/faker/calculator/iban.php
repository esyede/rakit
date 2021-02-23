<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct script access.');

class Iban
{
    public static function checksum($iban)
    {
        $string = substr($iban, 4).substr($iban, 0, 2).'00';
        $string = preg_replace_callback('/[A-Z]/', ['self', 'alphaToNumberCallback'], $string);
        $checksum = (98 - self::mod97($string));

        return str_pad($checksum, 2, '0', STR_PAD_LEFT);
    }

    private static function alphaToNumberCallback($match)
    {
        return self::alphaToNumber($match[0]);
    }

    public static function alphaToNumber($char)
    {
        return ord($char) - 55;
    }

    public static function mod97($number)
    {
        $checksum = (int) $number[0];

        for ($i = 1, $size = strlen($number); $i < $size; ++$i) {
            $checksum = (10 * $checksum + (int) $number[$i]) % 97;
        }

        return $checksum;
    }

    public static function isValid($iban)
    {
        return self::checksum($iban) === substr($iban, 2, 2);
    }
}
