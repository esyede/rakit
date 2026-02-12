<?php

namespace System;

defined('DS') or exit('No direct access.');

class URI
{
    /**
     * Contains the current request URI.
     *
     * @var string
     */
    public static $uri;

    /**
     * Contains the segments of the current request URI.
     *
     * @var array
     */
    public static $segments = [];

    /**
     * Get the full URI (including query string).
     *
     * @return string
     */
    public static function full()
    {
        return Request::getUri();
    }

    /**
     * Get the current request URI.
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
     * Format the given URI.
     *
     * @param string $uri
     *
     * @return string
     */
    protected static function format($uri)
    {
        $url = trim($uri, '/');
        return $url ?: '/';
    }

    /**
     * Check if the current URI matches the given URI pattern.
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
     * Get the segment of the current URI based on its index (index starts from 1).
     *
     * <code>
     *
     *      // Get URI segment based on index
     *      $segment = URI::segment(1);
     *
     *      // Get URI segment based on index, return default value if not found
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
     * Set the segments of the current URI for the current request.
     *
     * @param string $uri
     */
    protected static function segments($uri)
    {
        static::$segments = array_diff(explode('/', trim($uri, '/')), ['']);
    }
}
