<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Biased extends Base
{
    /**
     * Generate a biased random number between min and max.
     *
     * @param int $min
     * @param int $max
     * @param callable|null $callback
     *
     * @return int
     */
    public function biasedNumberBetween($min = 0, $max = 100, $callback = null)
    {
        $callback = is_null($callback) ? 'sqrt' : $callback;

        do {
            $x = mt_rand() / mt_getrandmax();
            $y = mt_rand() / (mt_getrandmax() + 1);
        } while (call_user_func($callback, $x) < $y);
        return floor($x * ($max - $min + 1) + $min);
    }

    /**
     * Unbiased callback that always returns 1.
     *
     * @param float $x
     *
     * @return int
     */
    protected static function unbiased($x)
    {
        return 1;
    }

    /**
     * Linear bias callback favoring lower values.
     *
     * @param float $x
     *
     * @return float
     */
    protected static function linearLow($x)
    {
        return 1 - $x;
    }

    /**
     * Linear bias callback favoring higher values.
     *
     * @param float $x
     *
     * @return float
     */
    protected static function linearHigh($x)
    {
        return $x;
    }
}
