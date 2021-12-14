<?php

namespace Docs\Libraries;

defined('DS') or exit('No direct script access.');

use System\Markdown;

class Docs
{
    /**
     * Cek apakah file markdown ada atau tidak.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function exists($name)
    {
        return is_file(static::path($name));
    }

    /**
     * Ambil full path ke file markdown.
     *
     * @param string $name
     *
     * @return string
     */
    public static function path($name)
    {
        return dirname(__DIR__).DS.'data'.DS.str_replace(['/', '\\'], DS, $name).'.md';
    }

    /**
     * Ubah sintaks markdown ke html.
     *
     * @param string $name
     *
     * @return string
     */
    public static function render($name)
    {
        return Markdown::render(static::path($name));
    }

    /**
     * Dekorasi tampilan nama halaman.
     *
     * @param string $title
     *
     * @return string
     */
    public static function title($title)
    {
        $title = (false !== strpos($title, '/')) ? explode('/', $title) : [$title];
        $title = str_replace('/', ' ~ ', implode('/', array_map('ucwords', $title)));

        return $title;
    }

    /**
     * Dekorasi konten.
     *
     * @param string $content
     *
     * @return string
     */
    public static function content($content)
    {
        $replacers = [
            '<blockquote>' => '<article class="message is-small is-primary"><div class="message-header">Note</div><div class="message-body">',
            '</blockquote>' => '</div></article>',
            '<table>' => '<div class="table-container"><table class="table is-narrow is-bordered is-striped is-fullwidth">',
            '</table>' => '</table></div>',
        ];

        return str_replace(array_keys($replacers), array_values($replacers), $content);
    }

    /**
     * Dekorasi tampilan sidebar.
     *
     * @param string $sidebar
     *
     * @return string
     */
    public static function sidebar($sidebar)
    {
        $sidebar = preg_replace(
            '/<a(.*)href="([^"]*)"(.*)>(.*)<\/a>\s?<!-- has-submenu -->(\s|\r|\n)?<ul>/',
            '<a$1href="#" class="navbar-link has-submenu"$3>$4</a>$5<ul class="submenu">',
            $sidebar
        );

        $replacers = [
            '<h3>' => '<p class="menu-label">',
            '</h3>' => '</p>',
            '<ul>' => '<ul class="menu-list">',
        ];

        return str_replace(array_keys($replacers), array_values($replacers), $sidebar);
    }
}
