<?php

namespace System\Foundation\Faker\Provider\en;

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Provider\Payment as BasePayment;

class Payment extends BasePayment
{
    public function bankAccountNumber()
    {
        return static::numerify(str_repeat('#', static::numberBetween(0, 3) + static::numberBetween(0, 3) + static::numberBetween(0, 3) + static::numberBetween(0, 3) + 5));
    }

    public function bankRoutingNumber()
    {
        $result = sprintf(
            '%02d%01d%01d%04d',
            static::numberBetween(1, 12) + static::randomElement([0, 0, 0, 0, 20, 20, 60]),
            static::randomDigitNotNull(),
            static::randomDigit(),
            static::randomNumber(4, true)
        );
        return $result . static::calculateRoutingNumberChecksum($result);
    }

    public static function calculateRoutingNumberChecksum($routing)
    {
        return (7 * ($routing[0] + $routing[3] + $routing[6]) + 3 * ($routing[1] + $routing[4] + $routing[7]) + 9 * ($routing[2] + $routing[5])) % 10;
    }
}
