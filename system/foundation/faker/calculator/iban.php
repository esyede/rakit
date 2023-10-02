<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct access.');

class Iban
{
    public static function checksum($iban)
    {
        $iban = (string) $iban;
        $iban = substr($iban, 4) . substr($iban, 0, 2) . '00';
        $iban = preg_replace_callback('/[A-Z]/', ['self', 'alphaToNumberCallback'], $iban);
        return str_pad(98 - self::mod97($iban), 2, '0', STR_PAD_LEFT);
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
        $number = (string) $number;
        $checksum = (int) $number[0];

        for ($i = 1, $size = mb_strlen($number, '8bit'); $i < $size; ++$i) {
            $checksum = (10 * $checksum + (int) $number[$i]) % 97;
        }

        return $checksum;
    }

    public static function isValid($iban)
    {
        return self::checksum($iban) === substr((string) $iban, 2, 2);
    }
}
