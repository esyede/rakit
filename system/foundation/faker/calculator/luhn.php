<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct script access.');

class Luhn
{
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

    public static function computeCheckDigit($partialNumber)
    {
        $digit = self::checksum($partialNumber . '0');
        return (0 === $digit) ? 0 : ((string) (10 - $digit));
    }

    public static function isValid($number)
    {
        return 0 === self::checksum($number);
    }

    public static function generateLuhnNumber($partialValue)
    {
        if (!preg_match('/^\d+$/', $partialValue)) {
            throw new \InvalidArgumentException('Argument should be an integer.');
        }

        return $partialValue . Luhn::computeCheckDigit($partialValue);
    }
}
