<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct access.');

class Iban
{
    /**
     * Calculate the IBAN checksum.
     *
     * @param string $iban
     *
     * @return string
     */
    public static function checksum($iban)
    {
        $iban = (string) $iban;
        $iban = substr($iban, 4).substr($iban, 0, 2).'00';
        $iban = preg_replace_callback('/[A-Z]/', [__CLASS__, 'alphaToNumberCallback'], $iban);
        return str_pad(98 - static::mod97($iban), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Callback to convert alpha characters to numbers.
     *
     * @param array $match
     *
     * @return string
     */
    private static function alphaToNumberCallback($match)
    {
        return static::alphaToNumber($match[0]);
    }

    /**
     * Convert an alpha character to its numeric representation.
     *
     * @param string $char
     *
     * @return int
     */
    public static function alphaToNumber($char)
    {
        return ord($char) - 55;
    }

    /**
     * Calculate mod97 of a number.
     *
     * @param string $number
     *
     * @return int
     */
    public static function mod97($number)
    {
        $number = (string) $number;
        $checksum = (int) $number[0];

        for ($i = 1, $size = mb_strlen($number, '8bit'); $i < $size; ++$i) {
            $checksum = (10 * $checksum + (int) $number[$i]) % 97;
        }

        return $checksum;
    }

    /**
     * Validate an IBAN number.
     *
     * @param string $iban
     *
     * @return bool
     */
    public static function isValid($iban)
    {
        return static::checksum($iban) === substr((string) $iban, 2, 2);
    }
}
