<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct access.');

class Tcno
{
    /**
     * Calculate the Turkish identity number checksum.
     *
     * @param string $identityPrefix
     *
     * @return string
     */
    public static function checksum($identityPrefix)
    {
        $identityPrefix = (string) $identityPrefix;

        if (9 !== mb_strlen($identityPrefix, '8bit')) {
            throw new \Exception('Argument should be an integer and should be 9 digits.');
        }

        $identity = array_map('intval', str_split($identityPrefix));
        $odd = 0;
        $even = 0;

        foreach ($identity as $index => $digit) {
            if (0 === $index % 2) {
                $even += $digit;
            } else {
                $odd += $digit;
            }
        }

        return ((7 * $even - $odd) % 10).(($even + $odd + ((7 * $even - $odd) % 10)) % 10);
    }

    /**
     * Validate a Turkish identity number.
     *
     * @param string $tcNo
     *
     * @return bool
     */
    public static function isValid($tcNo)
    {
        return static::checksum(substr((string) $tcNo, 0, -2)) === substr((string) $tcNo, -2, 2);
    }
}
