<?php

namespace Docs\Libraries;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Cache;
use System\Markdown;
use System\Str;

class Docs
{
    /**
     * Pattern matching the table of contents MarkdownTOC generates.
     *
     * @var string
     */
    const OUTLINE = '/<!--\s*MarkdownTOC.*?-->(.*?)<!--\s*\/MarkdownTOC\s*-->/s';

    /**
     * Cached reading order taken from the sidebar.
     *
     * @var array|null
     */
    protected static $order;

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
        // The outline is rendered beside the page instead of on top of it.
        $content = preg_replace(static::OUTLINE, '', (string) $content);

        $replacers = [
            '<blockquote>' => '<article class="message is-small is-primary"><div class="message-header">Note</div><div class="message-body">',
            '</blockquote>' => '</div></article>',
            '<table>' => '<div class="table-container"><table class="table is-narrow is-bordered is-striped is-fullwidth">',
            '</table>' => '</table></div>',
        ];

        return str_replace(array_keys($replacers), array_values($replacers), $content);
    }

    /**
     * Get the table of contents of a page, if it has one.
     *
     * @param string $content
     *
     * @return string
     */
    public static function outline($content)
    {
        if (!preg_match(static::OUTLINE, (string) $content, $matches)) {
            return '';
        }

        return trim($matches[1]);
    }

    /**
     * Get the pages before and after the given one.
     *
     * @param string $name
     *
     * @return array
     */
    public static function neighbours($name)
    {
        $order = static::reading_order();
        $current = static::canonical($name);
        $index = null;

        foreach ($order as $position => $page) {
            if ($page['url'] === $current) {
                $index = $position;
                break;
            }
        }

        if (is_null($index)) {
            return ['previous' => null, 'next' => null];
        }

        $section = $order[$index]['section'];

        return [
            'previous' => static::neighbour($order, $index - 1, $section),
            'next' => static::neighbour($order, $index + 1, $section),
        ];
    }

    /**
     * Prepare one neighbour, naming its section only when it differs.
     *
     * @param array  $order
     * @param int    $index
     * @param string $section
     *
     * @return array|null
     */
    protected static function neighbour(array $order, $index, $section)
    {
        if (!isset($order[$index])) {
            return null;
        }

        $page = $order[$index];
        $page['section'] = ($page['section'] === $section) ? null : $page['section'];

        return $page;
    }

    /**
     * Read the page order out of the sidebar, skipping in-page anchors.
     *
     * @return array
     */
    protected static function reading_order()
    {
        if (!is_null(static::$order)) {
            return static::$order;
        }

        $sidebar = static::path('000-sidebar');
        static::$order = [];

        if (!is_file($sidebar)) {
            return static::$order;
        }

        $pattern = '/^#{3}\s*(?P<section>.+)$|\[(?P<name>[^\]]+)\]\(\/docs\/(?P<path>[^)#]+)\)/m';
        preg_match_all($pattern, file_get_contents($sidebar), $matches, PREG_SET_ORDER);
        $section = '';
        $seen = [];

        foreach ($matches as $match) {
            if ('' !== trim($match['section'])) {
                $section = trim($match['section']);
                continue;
            }

            $url = static::canonical(trim($match['path'], '/'));

            if (isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            static::$order[] = ['name' => trim($match['name']), 'url' => $url, 'section' => $section];
        }

        return static::$order;
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
     * Get the canonical URL of a page.
     *
     * @param string $name
     *
     * @return string
     */
    public static function canonical($name)
    {
        $name = trim(str_replace('\\', '/', $name), '/');
        $name = ('home' === $name) ? '' : preg_replace('/\/home$/', '', $name);

        return rtrim(url('docs/' . $name), '/');
    }

    /**
     * Get the last modification time of a page.
     *
     * @param string $name
     *
     * @return int
     */
    public static function modified($name)
    {
        $path = static::path($name);
        return is_file($path) ? filemtime($path) : time();
    }

    /**
     * Build a meta description from rendered content.
     *
     * @param string $content
     *
     * @return string
     */
    public static function description($content)
    {
        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', (string) $content, $matches);

        foreach ($matches[1] as $paragraph) {
            $text = strip_tags(str_replace('<', ' <', $paragraph));
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $text = Str::trim(preg_replace('/\s+/u', ' ', $text));

            if (Str::length($text) >= 40) {
                return Str::limit($text, 157);
            }
        }

        return 'Rakit framework documentation.';
    }

    /**
     * Build breadcrumb trail for a page.
     *
     * @param string $name
     *
     * @return array
     */
    public static function breadcrumbs($name)
    {
        $crumbs = [['name' => 'Home', 'url' => rtrim(url('/'), '/')]];
        $name = trim(str_replace('\\', '/', $name), '/');
        $name = ('home' === $name) ? '' : preg_replace('/\/home$/', '', $name);

        if ('' === $name) {
            $crumbs[] = ['name' => 'Docs', 'url' => static::canonical('home')];
            return $crumbs;
        }

        $crumbs[] = ['name' => 'Docs', 'url' => static::canonical('home')];
        $segments = explode('/', $name);
        $last = array_pop($segments);
        $walked = [];

        foreach ($segments as $segment) {
            $walked[] = $segment;
            $path = implode('/', $walked);

            $crumbs[] = [
                'name' => static::title($segment),
                'url' => static::exists($path . '/home') ? static::canonical($path) : null,
            ];
        }

        $crumbs[] = ['name' => static::title($last), 'url' => static::canonical($name)];

        return $crumbs;
    }

    /**
     * List every indexable page as `url => mtime`.
     *
     * @return array
     */
    public static function pages()
    {
        $srcdir = dirname(__DIR__) . DS . 'data';
        $pages = [];

        foreach (static::get_markdown_files($srcdir) as $file) {
            $name = str_replace($srcdir . DS, '', $file);
            $name = str_replace(DS, '/', substr($name, 0, -3));

            if (false !== strpos($name, '000-sidebar')) {
                continue;
            }

            $url = static::canonical($name);
            $mtime = filemtime($file);

            if (!isset($pages[$url]) || $pages[$url] < $mtime) {
                $pages[$url] = $mtime;
            }
        }

        ksort($pages);

        return $pages;
    }

    /**
     * Ensure that search data exists.
     *
     * @return void
     */
    public static function ensure_search_data_exists()
    {
        $srcdir = dirname(__DIR__) . DS . 'data';
        $destfile = path('storage') . 'docs-search-data.json';
        $mtime = static::get_directory_mtime($srcdir);

        if (Cache::get('docs.search_data_mtime') !== $mtime || !is_file($destfile)) {
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
            Cache::forever('docs.search_data_mtime', $mtime);
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
