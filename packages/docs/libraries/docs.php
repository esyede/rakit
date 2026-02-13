<?php

namespace Docs\Libraries;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Cache;
use System\Markdown;

class Docs
{
    /**
     * Check if a markdown file exists.
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
     * Get the path to a markdown file.
     *
     * @param string $name
     *
     * @return string
     */
    public static function path($name)
    {
        return dirname(__DIR__) . DS . 'data' . DS . str_replace(['/', '\\'], DS, $name) . '.md';
    }

    /**
     * Render markdown to HTML (with caching).
     *
     * @param string $name
     *
     * @return string
     */
    public static function render($name)
    {
        $name = static::path($name);
        $mtime = filemtime($name);
        $cache = Cache::get('docs.' . md5($name));

        if ($mtime > intval(Arr::get(Arr::wrap($cache), 'mtime'))) {
            Cache::forget('docs.' . md5($name));
            Cache::forever('docs.' . md5($name), ['content' => Markdown::render($name), 'mtime' => $mtime]);
        }

        $cache = Cache::get('docs.' . md5($name));
        return Arr::get(Arr::wrap($cache), 'content');
    }

    /**
     * Decorate page title.
     *
     * @param string $title
     *
     * @return string
     */
    public static function title($title)
    {
        $title = strpos($title, '/') ? explode('/', $title) : [$title];
        return str_replace('/', ' ~ ', implode('/', array_map('ucwords', $title)));
    }

    /**
     * Decorate content.
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
     * Decorate sidebar content.
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

    /**
     * Ensure that search data exists.
     *
     * @return void
     */
    public static function ensure_search_data_exists()
    {
        $srcdir = dirname(__DIR__) . DS . 'data';
        $destfile = path('assets') . 'packages' . DS . 'docs' . DS . 'js' . DS . 'data.json';
        $cache_key = 'docs.search_data_mtime';
        $current_mtime = static::get_directory_mtime($srcdir);

        $cached_mtime = Cache::get($cache_key);
        if ($cached_mtime !== $current_mtime || !is_file($destfile)) {
            $files = static::get_markdown_files($srcdir);
            $documents = [];

            foreach ($files as $file) {
                $content = file_get_contents($file);
                preg_match('/^#\s*(.+)$/m', $content, $matches);
                $title = isset($matches[1]) ? trim($matches[1]) : basename($file, '.md');
                $relpath = str_replace($srcdir . DS, '', $file);
                $url = str_replace(DS, '/', dirname($relpath) . '/' . basename($relpath, '.md'));

                if (strpos($url, '000-sidebar') !== false) {
                    continue;
                }

                $url = trim($url, '/');
                $documents[] = ['id' => $url, 'title' => $title, 'url' => $url, 'content' => $content];
            }

            file_put_contents($destfile, json_encode($documents));
            Cache::forever($cache_key, $current_mtime);
        }
    }

    /**
     * Recursively get all markdown files in a directory.
     *
     * @param string $directory
     *
     * @return array
     */
    protected static function get_markdown_files($directory)
    {
        $files = glob($directory . DS . '*.md');
        $dirs = glob($directory . DS . '*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $files = array_merge($files, static::get_markdown_files($dir));
        }

        return $files;
    }

    /**
     * Get the maximum modification time of all markdown files in a directory.
     *
     * @param string $directory
     *
     * @return int
     */
    protected static function get_directory_mtime($directory)
    {
        $files = static::get_markdown_files($directory);

        if (empty($files)) {
            return 0;
        }

        $mtimes = array_map('filemtime', $files);
        return max($mtimes);
    }
}
