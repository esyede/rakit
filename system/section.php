<?php

namespace System;

defined('DS') or exit('No direct access.');

class Section
{
    /**
     * Berisi seluruh section yang terdaftar.
     *
     * @var array
     */
    public static $sections = [];

    /**
     * Berisi section terakhir dimana injeksi dimulai.
     *
     * @var array
     */
    public static $last = [];

    /**
     * Berisi seluruh stacks yang terdaftar.
     *
     * @var array
     */
    public static $stacks = [];

    /**
     * Mulai injeksi konten ke section.
     *
     * <code>
     *
     *      // Mulai menginjeksi section bernama 'header'
     *      Section::start('header');
     *
     *      // Mulai menginjeksi string mentah ke section bernama 'header' tanpa buffering
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
     * Cek apakah sebuah section ada (dan tidak kosong) atau tidak.
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
     * Inject konten inline kedalam section.
     * Ini berguna untuk menginjeksi string sederhana seperti judul halaman.
     *
     * <code>
     *
     *      // Inject konten inline kedalam section bernama 'header'
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
     * Hentikan injeksi konten kedalam section dan return kontennya.
     *
     * @return string
     */
    public static function yield_section()
    {
        return static::yield_content(static::stop());
    }

    /**
     * Hentikan injeksi konten kedalam section.
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
     * Extend konten kedalam section yang diberikan.
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
     * Append konten kedalam section yang diberikan.
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
     * Ambil konten milik sebuah section.
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
     * Mulai push konten ke stack.
     *
     * @param string $stack
     */
    public static function push($stack)
    {
        ob_start();
        static::$last[] = $stack;
    }

    /**
     * Akhiri push konten ke stack.
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
     * Ambil konten milik sebuah stack.
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
