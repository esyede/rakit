<?php

namespace System;

defined('DS') or exit('No direct script access.');

class URI
{
    /**
     * Berisi URI request saat ini.
     *
     * @var string
     */
    public static $uri;

    /**
     * Berisi segmen-segmen URI request saat ini.
     *
     * @var array
     */
    public static $segments = [];

    /**
     * Ambil full URI (termasuk query string).
     *
     * @return string
     */
    public static function full()
    {
        return Request::getUri();
    }

    /**
     * Ambil URI request saat ini.
     *
     * @return string
     */
    public static function current()
    {
        if (is_null(static::$uri)) {
            $uri = static::format(Request::getPathInfo());
            static::segments($uri);
            static::$uri = $uri;
        }

        return static::$uri;
    }

    /**
     * Memformat URI yang diberikan.
     *
     * @param string $uri
     *
     * @return string
     */
    protected static function format($uri)
    {
        $url = trim($uri, '/');
        return $url ? $url : '/';
    }

    /**
     * Cek apakah URI saat ini cocok dengan pola URI yang diberikan.
     *
     * @param string $pattern
     *
     * @return bool
     */
    public static function is($pattern)
    {
        return Str::is($pattern, static::current());
    }

    /**
     * Ambil segmen URI berdasarkan indexnya (index dimulai dari 1, bukan nol).
     *
     * <code>
     *
     *      // Ambil segmen URI berdasarkan index
     *      $segment = URI::segment(1);
     *
     *      // Ambil segmen URI berdasarkan index
     *      // return default value jika tidak ketemu
     *      $segment = URI::segment(2, 'Budi');
     *
     * </code>
     *
     * @param int   $index
     * @param mixed $default
     *
     * @return string
     */
    public static function segment($index, $default = null)
    {
        static::current();
        return Arr::get(static::$segments, $index - 1, $default);
    }

    /**
     * Set segmen-segmen URI untuk request saat ini.
     *
     * @param string $uri
     */
    protected static function segments($uri)
    {
        static::$segments = array_diff(explode('/', trim($uri, '/')), ['']);
    }
}
