<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Barcode extends Base
{
    /**
     * Generate an EAN barcode of the given length.
     *
     * @param int $length
     *
     * @return string
     */
    private function ean($length = 13)
    {
        $code = $this->numerify(str_repeat('#', $length - 1));
        return $code.static::eanChecksum($code);
    }

    /**
     * Compute the EAN checksum digit.
     *
     * @param string $input
     *
     * @return int
     */
    protected static function eanChecksum($input)
    {
        $input = (string) $input;
        $sequence = (0 === mb_strlen($input, '8bit') % 2) ? [1, 3] : [3, 1];
        $inputs = str_split($input);
        $sums = 0;

        foreach ($inputs as $n => $digit) {
            $sums += $digit * $sequence[$n % 2];
        }

        return (10 - $sums % 10) % 10;
    }

    /**
     * Compute the ISBN checksum character.
     *
     * @param string $input
     *
     * @return int|string
     */
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

    /**
     * Generate a random EAN-13 barcode.
     *
     * @return string
     */
    public function ean13()
    {
        return $this->ean(13);
    }

    /**
     * Generate a random EAN-8 barcode.
     *
     * @return string
     */
    public function ean8()
    {
        return $this->ean(8);
    }

    /**
     * Generate a random ISBN-10 barcode.
     *
     * @return string
     */
    public function isbn10()
    {
        $code = $this->numerify(str_repeat('#', 9));
        return $code.static::isbnChecksum($code);
    }

    /**
     * Generate a random ISBN-13 barcode.
     *
     * @return string
     */
    public function isbn13()
    {
        $code = '97'.static::numberBetween(8, 9).$this->numerify('#########');
        return $code.static::eanChecksum($code);
    }
}
