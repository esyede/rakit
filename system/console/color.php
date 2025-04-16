<?php

namespace System\Console;

defined('DS') or exit('No direct access.');

class Color
{
    public static function black($text, $newline = true)
    {
        return static::colorize($text, 30, $newline);
    }

    public static function red($text, $newline = true)
    {
        return static::colorize($text, 31, $newline);
    }

    public static function green($text, $newline = true)
    {
        return static::colorize($text, 32, $newline);
    }

    public static function yellow($text, $newline = true)
    {
        return static::colorize($text, 33, $newline);
    }

    public static function blue($text, $newline = true)
    {
        return static::colorize($text, 34, $newline);
    }

    public static function purple($text, $newline = true)
    {
        return static::colorize($text, 35, $newline);
    }

    public static function cyan($text, $newline = true)
    {
        return static::colorize($text, 36, $newline);
    }

    public static function white($text, $newline = true)
    {
        return static::colorize($text, 37, $newline);
    }

    public static function supported()
    {
        if (DS === '\\') {
            return (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
                || (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
        }

        return function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

    private static function colorize($text, $color, $newline = true)
    {
        return (static::supported() ? "\033[{$color}m{$text}\033[m" : $text) . ($newline ? PHP_EOL : '');
    }
}
