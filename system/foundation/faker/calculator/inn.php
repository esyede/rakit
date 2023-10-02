<?php

namespace System\Foundation\Faker\Calculator;

defined('DS') or exit('No direct access.');

class Inn
{
    public static function checksum($inn)
    {
        $multipliers = [1 => 2, 2 => 4, 3 => 10, 4 => 3, 5 => 5, 6 => 9, 7 => 4, 8 => 6, 9 => 8];
        $sum = 0;

        for ($i = 1; $i <= 9; ++$i) {
            $sum += (int) (substr((string) $inn, $i - 1, 1)) * $multipliers[$i];
        }

        return (string) (($sum % 11) % 10);
    }

    public static function isValid($inn)
    {
        return self::checksum(substr((string) $inn, 0, -1)) === substr((string) $inn, -1, 1);
    }
}
