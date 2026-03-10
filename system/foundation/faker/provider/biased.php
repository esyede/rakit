<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Biased extends Base
{
    public function biasedNumberBetween($min = 0, $max = 100, $callback = null)
    {
        $callback = is_null($callback) ? 'sqrt' : $callback;

        do {
            $x = mt_rand() / mt_getrandmax();
            $y = mt_rand() / (mt_getrandmax() + 1);
        } while (call_user_func($callback, $x) < $y);
        return floor($x * ($max - $min + 1) + $min);
    }

    protected static function unbiased($x)
    {
        return 1;
    }

    protected static function linearLow($x)
    {
        return 1 - $x;
    }

    protected static function linearHigh($x)
    {
        return $x;
    }
}
