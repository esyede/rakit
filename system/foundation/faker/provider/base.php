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
        if (! is_bool($strict)) {
            throw new \InvalidArgumentException(
                'randomNumber() generates numbers of fixed width. To generate numbers '.
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

        if ($strict) {
            return mt_rand(pow(10, $nbDigits - 1), $max);
        }

        return mt_rand(0, $max);
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
        $allKeys = array_keys($array);
        $numKeys = count($allKeys);

        if ($numKeys < $count) {
            $exception = 'Cannot get %s elements, only %s elements is available the in array';
            throw new \LengthException(sprintf($exception, $count, $numKeys));
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
            ++$numElements;
        }

        return $elements;
    }

    public static function randomElement($array = ['a', 'b', 'c'])
    {
        if (! $array) {
            return;
        }

        $elements = static::randomElements($array, 1);

        return $elements[0];
    }

    public static function randomKey($array = [])
    {
        if (! $array) {
            return;
        }

        $keys = array_keys($array);
        $key = $keys[mt_rand(0, count($keys) - 1)];

        return $key;
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

        while (list($key, $value) = each($array)) {
            if (0 === $i) {
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

            ++$i;
        }

        return $shuffled;
    }

    public static function shuffleString($string = '', $encoding = 'UTF-8')
    {
        if (function_exists('mb_strlen')) {
            $array = [];
            $strlen = mb_strlen($string, $encoding);

            for ($i = 0; $i < $strlen; ++$i) {
                $array[] = mb_substr($string, $i, 1, $encoding);
            }
        } else {
            $array = str_split($string, 1);
        }

        return implode('', static::shuffleArray($array));
    }

    public static function numerify($string = '###')
    {
        $toReplace = [];

        for ($i = 0, $count = strlen($string); $i < $count; ++$i) {
            if ('#' === $string[$i]) {
                $toReplace[] = $i;
            }
        }

        if ($nbReplacements = count($toReplace)) {
            $maxAtOnce = strlen((string) mt_getrandmax()) - 1;
            $numbers = '';
            $i = 0;

            while ($i < $nbReplacements) {
                $size = min($nbReplacements - $i, $maxAtOnce);
                $numbers .= str_pad(static::randomNumber($size), $size, '0', STR_PAD_LEFT);
                $i += $size;
            }

            for ($i = 0; $i < $nbReplacements; ++$i) {
                $string[$toReplace[$i]] = $numbers[$i];
            }
        }

        $string = preg_replace_callback('/\%/u', 'static::randomDigitNotNull', $string);

        return $string;
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

        $regex = preg_replace_callback('/\\\w/', 'static::randomLetter', $regex);
        $regex = preg_replace_callback('/\\\d/', 'static::randomDigit', $regex);
        $regex = preg_replace_callback('/(?<!\\\)\./', 'static::randomAscii', $regex);
        $regex = str_replace('\\', '', $regex);

        return $regex;
    }

    public static function toLower($string = '')
    {
        return mb_strtolower($string, 'UTF-8');
    }

    public static function toUpper($string = '')
    {
        return mb_strtoupper($string, 'UTF-8');
    }

    public function optional($weight = 0.5, $default = null)
    {
        if (mt_rand() / mt_getrandmax() <= $weight) {
            return $this->generator;
        }

        return new Common($default);
    }

    public function unique($reset = false, $maxRetries = 10000)
    {
        if ($reset || ! $this->unique) {
            $this->unique = new Unique($this->generator, $maxRetries);
        }

        return $this->unique;
    }
}
