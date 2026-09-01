<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator;
use System\Foundation\Faker\Common;
use System\Foundation\Faker\Unique;

class Base
{
    protected $generator;

    protected $unique;

    /**
     * Create a new Base provider instance.
     *
     * @param \System\Foundation\Faker\Generator $generator
     */
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Generate a random digit (0-9).
     *
     * @return int
     */
    public static function randomDigit()
    {
        return mt_rand(0, 9);
    }

    /**
     * Generate a random non-null digit (1-9).
     *
     * @return int
     */
    public static function randomDigitNotNull()
    {
        return mt_rand(1, 9);
    }

    /**
     * Generate a random number with a given number of digits.
     *
     * @param int|null $nbDigits
     * @param bool     $strict
     *
     * @return int
     */
    public static function randomNumber($nbDigits = null, $strict = false)
    {
        if (! is_bool($strict)) {
            throw new \InvalidArgumentException('randomNumber() generates numbers of fixed width. To generate numbers between two boundaries, use numberBetween() instead.');
        }

        if (null === $nbDigits) {
            $nbDigits = static::randomDigitNotNull();
        }

        $max = pow(10, $nbDigits) - 1;

        if ($max > mt_getrandmax()) {
            throw new \InvalidArgumentException('randomNumber() can only generate numbers up to mt_getrandmax()');
        }

        return $strict ? mt_rand(pow(10, $nbDigits - 1), $max) : mt_rand(0, $max);
    }

    /**
     * Generate a random float number.
     *
     * @param int|null $nbMaxDecimals
     * @param float    $min
     * @param float    $max
     *
     * @return float
     */
    public static function randomFloat($nbMaxDecimals = null, $min = 0, $max = null)
    {
        if (null === $nbMaxDecimals) {
            $nbMaxDecimals = static::randomDigit();
        }

        if (null === $max) {
            $max = static::randomNumber();
        }

        if ($min > $max) {
            $tmp = $min;
            $min = $max;
            $max = $tmp;
        }

        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $nbMaxDecimals);
    }

    /**
     * Generate a random number between min and max.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public static function numberBetween($min = 0, $max = 2147483647)
    {
        return mt_rand($min, $max);
    }

    /**
     * Generate a random lowercase letter.
     *
     * @return string
     */
    public static function randomLetter()
    {
        return chr(mt_rand(97, 122));
    }

    /**
     * Generate a random ASCII character.
     *
     * @return string
     */
    public static function randomAscii()
    {
        return chr(mt_rand(33, 126));
    }

    /**
     * Generate random elements from an array.
     *
     * @param array $array
     * @param int   $count
     *
     * @return array
     */
    public static function randomElements(array $array = ['a', 'b', 'c'], $count = 1)
    {
        $allKeys = array_keys($array);
        $numKeys = count($allKeys);

        if ($numKeys < $count) {
            throw new \LengthException(sprintf('Cannot get %d elements, only %d in array', $count, $numKeys));
        }

        $highKey = $numKeys - 1;
        $keys = $elements = [];
        $numElements = 0;

        while ($numElements < $count) {
            $num = mt_rand(0, $highKey);
            if (isset($keys[$num])) {
                continue;
            }

            $keys[$num] = true;
            $elements[] = $array[$allKeys[$num]];
            $numElements++;
        }

        return $elements;
    }

    /**
     * Generate a random element from an array.
     *
     * @param array $array
     *
     * @return mixed
     */
    public static function randomElement($array = ['a', 'b', 'c'])
    {
        if (! $array) {
            return;
        }

        $elements = static::randomElements($array, 1);
        return $elements[0];
    }

    /**
     * Generate a random key from an array.
     *
     * @param array $array
     *
     * @return mixed
     */
    public static function randomKey(array $array = [])
    {
        if (empty($array)) {
            return;
        }

        $keys = array_keys($array);
        return $keys[mt_rand(0, count($keys) - 1)];
    }

    /**
     * Shuffle an array or string.
     *
     * @param array|string $arg
     *
     * @return array|string
     */
    public static function shuffle($arg = '')
    {
        if (is_array($arg)) {
            return static::shuffleArray($arg);
        }

        if (is_string($arg)) {
            return static::shuffleString($arg);
        }

        throw new \InvalidArgumentException('shuffle() only supports strings or arrays');
    }

    /**
     * Shuffle an array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function shuffleArray($array = [])
    {
        $shuffled = [];
        $i = 0;
        reset($array);

        foreach ($array as $key => $value) {
            if ($i === 0) {
                $j = 0;
            } else {
                $j = mt_rand(0, $i);
            }

            if ($j === $i) {
                $shuffled[] = $value;
            } else {
                $shuffled[] = $shuffled[$j];
                $shuffled[$j] = $value;
            }

            $i++;
        }

        return $shuffled;
    }

    /**
     * Shuffle a string.
     *
     * @param string $string
     * @param string $encoding
     *
     * @return string
     */
    public static function shuffleString($string = '', $encoding = 'UTF-8')
    {
        $array = [];
        $strlen = mb_strlen((string) $string, $encoding);

        for ($i = 0; $i < $strlen; ++$i) {
            $array[] = mb_substr((string) $string, $i, 1, $encoding);
        }

        return implode('', static::shuffleArray($array));
    }

    /**
     * Replace hash signs with random digits.
     *
     * @param string $string
     *
     * @return string
     */
    public static function numerify($string = '###')
    {
        $string = (string) $string;
        $replacing = [];

        for ($i = 0, $count = mb_strlen($string, '8bit'); $i < $count; ++$i) {
            if ('#' === $string[$i]) {
                $replacing[] = $i;
            }
        }

        if ($total = count($replacing)) {
            $step = mb_strlen((string) mt_getrandmax(), '8bit') - 1;
            $numbers = '';
            $i = 0;

            while ($i < $total) {
                $size = min($total - $i, $step);
                $numbers .= str_pad(static::randomNumber($size), $size, '0', STR_PAD_LEFT);
                $i += $size;
            }

            for ($i = 0; $i < $total; ++$i) {
                $string[$replacing[$i]] = $numbers[$i];
            }
        }

        return preg_replace_callback('/\%/u', [get_called_class(), 'randomDigitNotNull'], $string);
    }

    /**
     * Replace question marks with random letters.
     *
     * @param string $string
     *
     * @return string
     */
    public static function lexify($string = '????')
    {
        return preg_replace_callback('/\?/u', [get_called_class(), 'randomLetter'], $string);
    }

    /**
     * Replace hash signs with random digits and question marks with random letters.
     *
     * @param string $string
     *
     * @return string
     */
    public static function bothify($string = '## ??')
    {
        return static::lexify(static::numerify($string));
    }

    /**
     * Replace asterisks with random ASCII characters.
     *
     * @param string $string
     *
     * @return string
     */
    public static function asciify($string = '****')
    {
        return preg_replace_callback('/\*/u', [get_called_class(), 'randomAscii'], $string);
    }

    /**
     * Generate a string that matches a regular expression.
     *
     * @param string $regex
     *
     * @return string
     */
    public static function regexify($regex = '')
    {
        $regex = preg_replace('/^\/?\^?/', '', $regex);
        $regex = preg_replace('/\$?\/?$/', '', $regex);
        $regex = preg_replace('/\{(\d+)\}/', '{\1,\1}', $regex);
        $regex = preg_replace('/(?<!\\\)\?/', '{0,1}', $regex);
        $regex = preg_replace('/(?<!\\\)\*/', '{0,'.static::randomDigitNotNull().'}', $regex);
        $regex = preg_replace('/(?<!\\\)\+/', '{1,'.static::randomDigitNotNull().'}', $regex);
        $regex = preg_replace_callback('/(\[[^\]]+\])\{(\d+),(\d+)\}/', function ($matches) {
            return str_repeat($matches[1], Base::randomElement(range($matches[2], $matches[3])));
        }, $regex);
        $regex = preg_replace_callback('/(\([^\)]+\))\{(\d+),(\d+)\}/', function ($matches) {
            return str_repeat($matches[1], Base::randomElement(range($matches[2], $matches[3])));
        }, $regex);
        $regex = preg_replace_callback('/(\\\?.)\{(\d+),(\d+)\}/', function ($matches) {
            return str_repeat($matches[1], Base::randomElement(range($matches[2], $matches[3])));
        }, $regex);
        $regex = preg_replace_callback('/\((.*?)\)/', function ($matches) {
            return Base::randomElement(explode('|', str_replace(['(', ')'], '', $matches[1])));
        }, $regex);
        $regex = preg_replace_callback('/\[([^\]]+)\]/', function ($matches) {
            return '['.preg_replace_callback('/(\w|\d)\-(\w|\d)/', function ($range) {
                return implode('', range($range[1], $range[2]));
            }, $matches[1]).']';
        }, $regex);
        $regex = preg_replace_callback('/\[([^\]]+)\]/', function ($matches) {
            return Base::randomElement(str_split($matches[1]));
        }, $regex);
        $regex = preg_replace_callback('/\\\w/', [get_called_class(), 'randomLetter'], $regex);
        $regex = preg_replace_callback('/\\\d/', [get_called_class(), 'randomDigit'], $regex);
        $regex = preg_replace_callback('/(?<!\\\)\./', [get_called_class(), 'randomAscii'], $regex);

        return str_replace('\\', '', $regex);
    }

    /**
     * Convert a string to lowercase.
     *
     * @param string $string
     *
     * @return string
     */
    public static function toLower($string = '')
    {
        return mb_strtolower((string) $string, 'UTF-8');
    }

    /**
     * Convert a string to uppercase.
     *
     * @param string $string
     *
     * @return string
     */
    public static function toUpper($string = '')
    {
        return mb_strtoupper((string) $string, 'UTF-8');
    }

    /**
     * Get an optional proxy generator.
     *
     * @param float $weight
     * @param mixed $default
     *
     * @return \System\Foundation\Faker\Generator|\System\Foundation\Faker\Common
     */
    public function optional($weight = 0.5, $default = null)
    {
        return (mt_rand() / mt_getrandmax() <= $weight) ? $this->generator : new Common($default);
    }

    /**
     * Get a unique proxy generator.
     *
     * @param bool $reset
     * @param int  $max_retries
     *
     * @return \System\Foundation\Faker\Unique
     */
    public function unique($reset = false, $max_retries = 10000)
    {
        if (! $this->unique) {
            $this->unique = new Unique($this->generator, $max_retries);
            return $this->unique;
        }

        if ($reset) {
            $this->unique->reset();
        }

        if ($max_retries !== null) {
            $this->unique->setMaxRetries($max_retries);
        }

        return $this->unique;
    }

    /**
     * Get the next key-value pair from an array.
     *
     * @param array $array
     *
     * @return array|false
     */
    protected static function eachEvery($array)
    {
        $key = key($array);
        $value = current($array);
        $each = is_null($key) ? false : [1 => $value, 'value' => $value, 0 => $key, 'key' => $key];
        next($array);
        return $each;
    }
}
