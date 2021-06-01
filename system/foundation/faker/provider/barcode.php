<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Barcode extends Base
{
    private function ean($length = 13)
    {
        $code = $this->numerify(str_repeat('#', $length - 1));
        return $code.static::eanChecksum($code);
    }

    protected static function eanChecksum($input)
    {
        $sequence = (8 === (strlen($input) - 1)) ? [3, 1] : [1, 3];
        $sums = 0;

        foreach (str_split($input) as $n => $digit) {
            $sums += $digit * $sequence[$n % 2];
        }

        return (10 - $sums % 10) % 10;
    }

    protected static function isbnChecksum($input)
    {
        $length = 9;

        if ($length !== strlen($input)) {
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
        return $code.static::isbnChecksum($code);
    }

    public function isbn13()
    {
        $code = '97'.static::numberBetween(8, 9).$this->numerify(str_repeat('#', 9));
        return $code.static::eanChecksum($code);
    }
}
