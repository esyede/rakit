<?php

namespace System\Console;

defined('DS') or exit('No direct access.');

class Color
{
    /**
     * Output text in black color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function black($text, $newline = true)
    {
        return static::colorize($text, 30, $newline);
    }

    /**
     * Output text in red color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function red($text, $newline = true)
    {
        return static::colorize($text, 31, $newline);
    }

    /**
     * Output text in green color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function green($text, $newline = true)
    {
        return static::colorize($text, 32, $newline);
    }

    /**
     * Output text in yellow color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function yellow($text, $newline = true)
    {
        return static::colorize($text, 33, $newline);
    }

    /**
     * Output text in blue color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function blue($text, $newline = true)
    {
        return static::colorize($text, 34, $newline);
    }

    /**
     * Output text in purple color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function purple($text, $newline = true)
    {
        return static::colorize($text, 35, $newline);
    }

    /**
     * Output text in cyan color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function cyan($text, $newline = true)
    {
        return static::colorize($text, 36, $newline);
    }

    /**
     * Output text in white color.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    public static function white($text, $newline = true)
    {
        return static::colorize($text, 37, $newline);
    }

    /**
     * Check if the terminal supports colors.
     *
     * @return bool
     */
    public static function supported()
    {
        if (! defined('STDOUT')) {
            return false;
        }

        if (DS === '\\') {
            return (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
                || (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
        }

        return function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

    /**
     * Colorize the given text.
     *
     * @param string $text
     * @param int    $color
     * @param bool   $newline
     *
     * @return string
     */
    private static function colorize($text, $color, $newline = true)
    {
        return (static::supported() ? "\033[{$color}m{$text}\033[m" : $text).($newline ? PHP_EOL : '');
    }
}
