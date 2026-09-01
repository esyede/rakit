<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct access.');

class Luhn
{
    /**
     * Calculate the Luhn checksum.
     *
     * @param string $number
     *
     * @return int
     */
    private static function checksum($number)
    {
        $number = (string) $number;
        $length = mb_strlen($number, '8bit');
        $sum = 0;

        for ($i = $length - 1; $i >= 0; $i -= 2) {
            $sum += $number[$i];
        }

        for ($i = $length - 2; $i >= 0; $i -= 2) {
            $sum += array_sum(str_split($number[$i] * 2));
        }

        return $sum % 10;
    }

    /**
     * Compute the Luhn check digit for a partial number.
     *
     * @param string $partialNumber
     *
     * @return int
     */
    public static function computeCheckDigit($partialNumber)
    {
        $digit = static::checksum($partialNumber.'0');
        return (0 === $digit) ? 0 : ((string) (10 - $digit));
    }

    /**
     * Validate a number using the Luhn algorithm.
     *
     * @param string $number
     *
     * @return bool
     */
    public static function isValid($number)
    {
        return 0 === static::checksum($number);
    }

    /**
     * Generate a complete Luhn number from a partial value.
     *
     * @param string $partialValue
     *
     * @return string
     */
    public static function generateLuhnNumber($partialValue)
    {
        if (! preg_match('/^\d+$/', $partialValue)) {
            throw new \InvalidArgumentException('Argument should be an integer.');
        }

        return $partialValue.static::computeCheckDigit($partialValue);
    }
}
