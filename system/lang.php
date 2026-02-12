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
     * Contains all loaded language lines.
     * The array key follows this pattern: [$package][$language][$file].
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
     * <code>
     *
     *      // Create a new language line instance for the given line
     *      $line = Lang::line('validation.required');
     *
     *      // Create a new language line instance for the given line (package)
     *      $line = Lang::line('admin::messages.welcome');
     *
     *      // Create a new language line instance for the given line (package, language)
     *      $line = Lang::line('admin::messages.welcome', [], 'en');
     *
     *      // Create a new language line instance for the given line (package, language, replacements)
     *      $line = Lang::line('admin::messages.welcome', ['name' => 'John'], 'en');
     *
     * </code>
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
     * <code>
     *
     *      // Get a language line
     *      $line = Lang::line('validation.required')->get();
     *
     *      // Get a language line in a specific language
     *      $line = Lang::line('validation.required')->get('en'); // en = english
     *
     *      // Fallback to default value if language line not found
     *      $line = Lang::line('validation.required')->get(null, 'Default');
     *
     * </code>
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

        if (!static::load($package, $language, $file)) {
            return value($default);
        }

        $line = Arr::get(static::$lines[$package][$language][$file], $line, $default);

        if (is_string($line)) {
            foreach ($this->replacements as $key => $value) {
                $line = str_replace(':' . $key, $value, $line);
            }
        }

        return $line;
    }

    /**
     * Parse a language key into package, file, and line segments.
     * Language line calls follow this convention: [package_name]::[file_name].[language_line].
     *
     * @param string $key
     *
     * @return array
     */
    protected function parse($key)
    {
        $package = Package::name($key);
        $segments = explode('.', Package::element($key));
        $line = (count($segments) >= 2) ? implode('.', array_slice($segments, 1)) : null;

        return [$package, $segments[0], $line];
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

        $lines = Event::first(static::LOADER, [$package, $language, $file]);
        static::$lines[$package][$language][$file] = $lines;

        return count($lines) > 0;
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
            strpos($package, '..') !== false || strpos($package, '/') !== false || strpos($package, '\\') !== false ||
            strpos($language, '..') !== false || strpos($language, '/') !== false || strpos($language, '\\') !== false ||
            strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false
        ) {
            return [];
        }

        $key = $package . '::' . $language . '::' . $file;
        $path = static::path($package, $language, $file);

        if (isset(static::$files[$key]) && is_file($path) && static::$files[$key]['mtime'] === filemtime($path)) {
            return static::$files[$key]['data'];
        }

        if (!is_file($path)) {
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
        return Package::path($package) . 'language' . DS . $language . DS . $file . '.php';
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
