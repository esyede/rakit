<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Barcode extends Base
{
    private function ean($length = 13)
    {
        $code = $this->numerify(str_repeat('#', $length - 1));
        return $code . static::eanChecksum($code);
    }

    protected static function eanChecksum($input)
    {
        $input = (string) $input;

        // Note: $input is the code *without* its check digit, so the weights are
        // decided by its own length: the digit right before the check digit always
        // weighs 3, which means an odd length starts at 3 and an even length at 1.
        // The old test ('8 === strlen($input) - 1') was never true for EAN-8
        // (7 digits) and produced an invalid check digit for every one of them.
        $sequence = (0 === mb_strlen($input, '8bit') % 2) ? [1, 3] : [3, 1];
        $inputs = str_split($input);
        $sums = 0;

        foreach ($inputs as $n => $digit) {
            $sums += $digit * $sequence[$n % 2];
        }

        return (10 - $sums % 10) % 10;
    }

    protected static function isbnChecksum($input)
    {
        $input = (string) $input;
        $length = 9;

        if ($length !== mb_strlen($input, '8bit')) {
            throw new \LengthException(sprintf('Input length should be equal to %s', $length));
        }

        $digits = str_split($input);
        array_walk($digits, function (&$digit, $position) {
            $digit = (10 - $position) * $digit;
        });

        $result = (11 - array_sum($digits) % 11) % 11;
        return ($result < 10) ? $result : 'X';
    }

    public function ean13()
    {
        return $this->ean(13);
    }

    public function ean8()
    {
        return $this->ean(8);
    }

    public function isbn10()
    {
        $code = $this->numerify(str_repeat('#', 9));
        return $code . static::isbnChecksum($code);
    }

    public function isbn13()
    {
        $code = '97' . static::numberBetween(8, 9) . $this->numerify('#########');
        return $code . static::eanChecksum($code);
    }
}
