<?php

namespace System\Console;

defined('DS') or exit('No direct access.');

class Color
{
    public static function supported()
    {
        if (DS === '\\') {
            return (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
                || (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
        }
        return function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

    public static function green($text, $newline = true)
    {
        $text = static::supported() ? "\033[32m{$text}\033[m" : $text;
        return $text . ($newline ? PHP_EOL : '');
    }

    public static function yellow($text, $newline = true)
    {
        $text = static::supported() ? "\033[33m{$text}\033[m" : $text;
        return $text . ($newline ? PHP_EOL : '');
    }

    public static function red($text, $newline = true)
    {
        $text = static::supported() ? "\033[31m{$text}\033[m" : $text;
        return $text . ($newline ? PHP_EOL : '');
    }
}
