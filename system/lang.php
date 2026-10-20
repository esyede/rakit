<?php

namespace System;

defined('DS') or exit('No direct access.');

class Lang
{
    /**
     * The name of the language loader event.
     *
     * @var string
     */
    const LOADER = 'rakit.language.loader';

    /**
     * Contains the key of the language line being fetched.
     *
     * @var string
     */
    protected $key;

    /**
     * Contains the replacements for the current language line.
     *
     * @var array
     */
    protected $replacements;

    /**
     * From which language the line should be fetched?
     *
     * @var string
     */
    protected $language;

    /**
     * All loaded language lines, keyed [$package][$language][$file].
     *
     * @var array
     */
    protected static $lines = [];

    /**
     * Contains cached language files.
     *
     * @var array
     */
    protected static $files = [];

    /**
     * Constructor.
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     */
    protected function __construct($key, array $replacements = [], $language = null)
    {
        $this->key = $key;
        $this->language = $language;
        $this->replacements = $replacements;
    }

    /**
     * Create a new language line instance.
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     *
     * @return Lang
     */
    public static function line($key, array $replacements = [], $language = null)
    {
        return new static($key, $replacements, is_null($language) ? Config::get('application.language') : $language);
    }

    /**
     * Check if language line exists.
     *
     * @param string $key
     * @param string $language
     *
     * @return bool
     */
    public static function has($key, $language = null)
    {
        return static::line($key, [], $language)->get() !== $key;
    }

    /**
     * Get language line as string.
     *
     * @param string $language
     * @param string $default
     *
     * @return string
     */
    public function get($language = null, $default = null)
    {
        $default = is_null($default) ? $this->key : $default;
        $language = is_null($language) ? $this->language : $language;

        list($package, $file, $line) = $this->parse($this->key);

        if (! static::load($package, $language, $file)) {
            return value($default);
        }

        $line = Arr::get(static::$lines[$package][$language][$file], $line, $default);
        return is_string($line) ? $this->replace($line, $this->replacements) : $line;
    }

    /**
     * Get the line for a count: 'apples' => '{0} No apples|{1} One apple|[2,*] :count apples'.
     * A segment may start with an exact count {n} or a range [a,b] ('*' for no bound);
     * otherwise the first segment is the singular and the second the plural, except
     * for languages without plural forms, which take the first. :count is replaced.
     *
     * @param string      $key
     * @param int|float   $count
     * @param array       $replacements
     * @param string|null $language
     *
     * @return string
     */
    public static function choice($key, $count, array $replacements = [], $language = null)
    {
        $language = is_null($language) ? Config::get('application.language') : $language;
        $line = static::line($key, [], $language)->get($language);

        if (! is_string($line)) {
            return $line;
        }

        $line = static::select($line, $count, $language);
        return static::line($key, [], $language)->replace($line, array_merge(['count' => $count], $replacements));
    }

    /**
     * Pick the segment of a pluralized line that fits a count.
     *
     * @param string    $line
     * @param int|float $count
     * @param string    $language
     *
     * @return string
     */
    protected static function select($line, $count, $language)
    {
        $segments = explode('|', $line);
        $plain = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if (preg_match('/^\{(-?\d+(?:\.\d+)?)\}\s*(.*)$/s', $segment, $match)) {
                if ((float) $match[1] == $count) {
                    return $match[2];
                }

                continue;
            }

            if (preg_match('/^\[(\*|-?\d+(?:\.\d+)?)\s*,\s*(\*|-?\d+(?:\.\d+)?)\]\s*(.*)$/s', $segment, $match)) {
                $low = ('*' === $match[1]) ? -INF : (float) $match[1];
                $high = ('*' === $match[2]) ? INF : (float) $match[2];

                if ($count >= $low && $count <= $high) {
                    return $match[3];
                }

                continue;
            }

            $plain[] = $segment;
        }

        if (0 === count($plain)) {
            return end($segments);
        }

        $base = strtolower((string) strtok((string) $language, '-_'));
        $singular = in_array($base, ['id', 'ms', 'ja', 'ko', 'zh', 'th', 'vi', 'km', 'lo', 'my', 'tr', 'fa'], true);
        $index = ($singular || 1 == abs($count)) ? 0 : 1;

        return isset($plain[$index]) ? $plain[$index] : end($plain);
    }

    /**
     * Replace the :placeholders of a line, the longest names first.
     *
     * @param string $line
     * @param array  $replacements
     *
     * @return string
     */
    public function replace($line, array $replacements)
    {
        if (count($replacements) > 1) {
            uksort($replacements, function ($left, $right) {
                return mb_strlen((string) $right, '8bit') - mb_strlen((string) $left, '8bit');
            });
        }

        foreach ($replacements as $key => $value) {
            $line = str_replace(':'.$key, $value, $line);
        }

        return $line;
    }

    /**
     * Parse '[package]::[file].[line]' into its package, file and line segments.
     *
     * @param string $key
     *
     * @return array
     */
    protected function parse($key)
    {
        $package = Package::name($key);
        $segments = explode('.', Package::element($key));
        return [$package, $segments[0], (count($segments) >= 2) ? implode('.', array_slice($segments, 1)) : null];
    }

    /**
     * Load language lines from a file.
     *
     * @param string $package
     * @param string $language
     * @param string $file
     *
     * @return bool
     */
    public static function load($package, $language, $file)
    {
        if (isset(static::$lines[$package][$language][$file])) {
            return true;
        }

        $lines = Hook::first(static::LOADER, [$package, $language, $file]);
        static::$lines[$package][$language][$file] = is_array($lines) ? $lines : [];
        return count(static::$lines[$package][$language][$file]) > 0;
    }

    /**
     * Load language array from a file.
     *
     * @param string $package
     * @param string $language
     * @param string $file
     *
     * @return array
     */
    public static function file($package, $language, $file)
    {
        if (
            strpos($package, '..') !== false
            || strpos($package, '/') !== false
            || strpos($package, '\\') !== false
            || strpos($language, '..') !== false
            || strpos($language, '/') !== false
            || strpos($language, '\\') !== false
            || strpos($file, '..') !== false
            || strpos($file, '/') !== false
            || strpos($file, '\\') !== false
        ) {
            return [];
        }

        $key = $package.'::'.$language.'::'.$file;
        $path = static::path($package, $language, $file);

        if (isset(static::$files[$key]) && is_file($path) && static::$files[$key]['mtime'] === filemtime($path)) {
            return static::$files[$key]['data'];
        }

        if (! is_file($path)) {
            static::$files[$key] = ['data' => [], 'mtime' => 0];
            return [];
        }

        try {
            $loaded = require $path;
            static::$files[$key] = ['data' => (array) $loaded, 'mtime' => filemtime($path)];
            return static::$files[$key]['data'];
        } catch (\Throwable $e) {
            static::$files[$key] = ['data' => [], 'mtime' => 0];
            return [];
        } catch (\Exception $e) {
            static::$files[$key] = ['data' => [], 'mtime' => 0];
            return [];
        }
    }

    /**
     * Get the path to a package's language file.
     *
     * @param string $package
     * @param string $language
     * @param string $file
     *
     * @return string
     */
    protected static function path($package, $language, $file)
    {
        return Package::path($package).'language'.DS.$language.DS.$file.'.php';
    }

    /**
     * String representation of the language line.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get();
    }
}
