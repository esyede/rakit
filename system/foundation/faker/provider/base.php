<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

use System\Foundation\Faker\Generator;
use System\Foundation\Faker\Common;
use System\Foundation\Faker\Unique;

class Base
{
    protected $generator;
    protected $unique;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public static function randomDigit()
    {
        return mt_rand(0, 9);
    }

    public static function randomDigitNotNull()
    {
        return mt_rand(1, 9);
    }

    public static function randomNumber($nbDigits = null, $strict = false)
    {
        if (!is_bool($strict)) {
            throw new \InvalidArgumentException(
                'randomNumber() generates numbers of fixed width. To generate numbers ' .
                    'between two boundaries, use numberBetween() instead.'
            );
        }

        if (null === $nbDigits) {
            $nbDigits = static::randomDigitNotNull();
        }

        $max = pow(10, $nbDigits) - 1;

        if ($max > mt_getrandmax()) {
            throw new \InvalidArgumentException(
                'randomNumber() can only generate numbers up to mt_getrandmax()'
            );
        }

        return $strict ? mt_rand(pow(10, $nbDigits - 1), $max) : mt_rand(0, $max);
    }

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

    public static function numberBetween($min = 0, $max = 2147483647)
    {
        return mt_rand($min, $max);
    }

    public static function randomLetter()
    {
        return chr(mt_rand(97, 122));
    }

    public static function randomAscii()
    {
        return chr(mt_rand(33, 126));
    }

    public static function randomElements(array $array = ['a', 'b', 'c'], $count = 1)
    {
        $keys = array_keys($array);
        $total = count($keys);

        if ($total < $count) {
            throw new \LengthException(sprintf(
                'Cannot get %s elements, only %s elements is available the in array',
                $count,
                $total
            ));
        }

        $high = $total - 1;
        $keys = $elements = [];
        $index = 0;

        while ($index < $count) {
            $num = mt_rand(0, $high);

            if (isset($keys[$num])) {
                continue;
            }

            $keys[$num] = true;
            $elements[] = $array[$keys[$num]];
            ++$index;
        }

        return $elements;
    }

    public static function randomElement(array $array = ['a', 'b', 'c'])
    {
        return empty($array) ? null : static::randomElements($array, 1)[0];
    }

    public static function randomKey(array $array = [])
    {
        if (empty($array)) {
            return;
        }

        $keys = array_keys($array);
        return $keys[mt_rand(0, count($keys) - 1)];
    }

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

    public static function shuffleArray($array = [])
    {
        $shuffled = [];
        $i = 0;
        reset($array);

        while (list($key, $value) = static::eachEvery($array)) {
            $j = (0 === $i) ? 0 : mt_rand(0, $i);

            if ($j === $i) {
                $shuffled[] = $value;
            } else {
                $shuffled[] = $shuffled[$j];
                $shuffled[$j] = $value;
            }

            ++$i;
        }

        return $shuffled;
    }

    public static function shuffleString($string = '', $encoding = 'UTF-8')
    {
        $array = [];
        $strlen = mb_strlen((string) $string, $encoding);

        for ($i = 0; $i < $strlen; ++$i) {
            $array[] = mb_substr((string) $string, $i, 1, $encoding);
        }

        return implode('', static::shuffleArray($array));
    }

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

        return preg_replace_callback('/\%/u', 'static::randomDigitNotNull', $string);
    }

    public static function lexify($string = '????')
    {
        return preg_replace_callback('/\?/u', 'static::randomLetter', $string);
    }

    public static function bothify($string = '## ??')
    {
        return static::lexify(static::numerify($string));
    }

    public static function asciify($string = '****')
    {
        return preg_replace_callback('/\*/u', 'static::randomAscii', $string);
    }

    public static function regexify($regex = '')
    {
        $regex = preg_replace('/^\/?\^?/', '', $regex);
        $regex = preg_replace('/\$?\/?$/', '', $regex);
        $regex = preg_replace('/\{(\d+)\}/', '{\1,\1}', $regex);
        $regex = preg_replace('/(?<!\\\)\?/', '{0,1}', $regex);
        $regex = preg_replace('/(?<!\\\)\*/', '{0,' . static::randomDigitNotNull() . '}', $regex);
        $regex = preg_replace('/(?<!\\\)\+/', '{1,' . static::randomDigitNotNull() . '}', $regex);
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
            return '[' . preg_replace_callback('/(\w|\d)\-(\w|\d)/', function ($range) {
                return implode('', range($range[1], $range[2]));
            }, $matches[1]) . ']';
        }, $regex);

        $regex = preg_replace_callback('/\[([^\]]+)\]/', function ($matches) {
            return Base::randomElement(str_split($matches[1]));
        }, $regex);

        $regex = preg_replace_callback('/\\\w/', 'static::randomLetter', $regex);
        $regex = preg_replace_callback('/\\\d/', 'static::randomDigit', $regex);
        $regex = preg_replace_callback('/(?<!\\\)\./', 'static::randomAscii', $regex);

        return str_replace('\\', '', $regex);
    }

    public static function toLower($string = '')
    {
        return mb_strtolower((string) $string, 'UTF-8');
    }

    public static function toUpper($string = '')
    {
        return mb_strtoupper((string) $string, 'UTF-8');
    }

    public function optional($weight = 0.5, $default = null)
    {
        return (mt_rand() / mt_getrandmax() <= $weight) ? $this->generator : new Common($default);
    }

    public function unique($reset = false, $max_retries = 10000)
    {
        if ($reset || !$this->unique) {
            $this->unique = new Unique($this->generator, $max_retries);
        }

        return $this->unique;
    }

    protected static function eachEvery($array)
    {
        $key = key($array);
        $value = current($array);
        $each = is_null($key) ? false : [1 => $value, 'value' => $value, 0 => $key, 'key' => $key];
        next($array);
        return $each;
    }
}
