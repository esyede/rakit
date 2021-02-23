<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct script access.');

class Tcno
{
    public static function checksum($identityPrefix)
    {
        if (9 !== strlen((string) $identityPrefix)) {
            throw new \InvalidArgumentException(
                'Argument should be an integer and should be 9 digits.'
            );
        }

        $identity = array_map('intval', str_split($identityPrefix));

        $oddSum = 0;
        $evenSum = 0;

        foreach ($identity as $index => $digit) {
            if (0 === $index % 2) {
                $evenSum += $digit;
            } else {
                $oddSum += $digit;
            }
        }

        $tenthDigit = (7 * $evenSum - $oddSum) % 10;
        $eleventhDigit = ($evenSum + $oddSum + $tenthDigit) % 10;

        return $tenthDigit.$eleventhDigit;
    }

    public static function isValid($tcNo)
    {
        return self::checksum(substr($tcNo, 0, -2)) === substr($tcNo, -2, 2);
    }
}
