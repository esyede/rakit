<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct script access.');

class Iban
{
    public static function checksum($iban)
    {
        $string = substr((string) $iban, 4) . substr((string) $iban, 0, 2) . '00';
        $string = preg_replace_callback('/[A-Z]/', ['self', 'alphaToNumberCallback'], $string);
        return str_pad(98 - self::mod97($string), 2, '0', STR_PAD_LEFT);
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

        for ($i = 1, $size = mb_strlen((string) $number, '8bit'); $i < $size; ++$i) {
            $checksum = (10 * $checksum + (int) $number[$i]) % 97;
        }

        return $checksum;
    }

    public static function isValid($iban)
    {
        return self::checksum($iban) === substr((string) $iban, 2, 2);
    }
}
