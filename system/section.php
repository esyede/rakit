<?php

namespace System;

defined('DS') or exit('No direct access.');

class Section
{
    /**
     * Contains all registered sections.
     *
     * @var array
     */
    public static $sections = [];

    /**
     * Contains the last section where injection started.
     *
     * @var array
     */
    public static $last = [];

    /**
     * Contains all registered stacks.
     *
     * @var array
     */
    public static $stacks = [];

    /**
     * Start injecting content into a section.
     *
     * <code>
     *
     *      // Start injecting content into section named 'header'
     *      Section::start('header');
     *
     *      // Start injecting raw string into section named 'header' without buffering
     *      Section::start('header', '<title>rakit</title>');
     *
     * </code>
     *
     * @param string         $section
     * @param string|Closure $content
     */
    public static function start($section, $content = '')
    {
        if ('' === $content) {
            ob_start();
            static::$last[] = $section;
        } else {
            static::extend($section, $content);
        }
    }

    /**
     * Check if a section exists and is not empty.
     *
     * @param string $section
     *
     * @return bool
     */
    public static function has($section)
    {
        return isset(static::$sections[$section]);
    }

    /**
     * Inject content into a section.
     * This will replace the existing content of the section.
     *
     * <code>
     *
     *      // Inject inline content into section named 'header'
     *      Section::inject('header', '<title>rakit</title>');
     *
     * </code>
     *
     * @param string $section
     * @param string $content
     */
    public static function inject($section, $content)
    {
        static::start($section, $content);
    }

    /**
     * Stop injecting content into a section.
     *
     * @return string
     */
    public static function stop()
    {
        $last = array_pop(static::$last);
        static::extend($last, ob_get_clean());
        return $last;
    }

    /**
     * Extend content into a section.
     *
     * @param string $section
     * @param string $content
     */
    protected static function extend($section, $content)
    {
        static::$sections[$section] = isset(static::$sections[$section])
            ? str_replace('@parent', $content, static::$sections[$section])
            : $content;
    }

    /**
     * Append content into a section.
     *
     * @param string $section
     * @param string $content
     */
    public static function append($section, $content)
    {
        static::$sections[$section] = isset(static::$sections[$section])
            ? static::$sections[$section] . $content
            : $content;
    }

    /**
     * Get content of a section.
     *
     * @param string $section
     *
     * @return string
     */
    public static function yield_content($section)
    {
        return isset(static::$sections[$section]) ? static::$sections[$section] : '';
    }

    /**
     * Stop injecting content into a section and return its content.
     *
     * @param string|null $section
     *
     * @return string
     */
    public static function yield_section($section = null)
    {
        return static::yield_content((null === $section) ?  static::stop() : $section);
    }

    /**
     * Start pushing content to stack.
     *
     * @param string $stack
     */
    public static function push($stack)
    {
        ob_start();
        static::$last[] = $stack;
    }

    /**
     * Stop pushing content to stack.
     */
    public static function endpush()
    {
        $last = array_pop(static::$last);
        $content = ob_get_clean();

        if (!isset(static::$stacks[$last])) {
            static::$stacks[$last] = [];
        }

        static::$stacks[$last][] = $content;
    }

    /**
     * Get content of a stack.
     *
     * @param string $stack
     *
     * @return string
     */
    public static function stack($stack)
    {
        return isset(static::$stacks[$stack]) ? implode('', static::$stacks[$stack]) : '';
    }
}
