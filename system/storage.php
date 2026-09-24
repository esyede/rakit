<?php

namespace System;

defined('DS') or exit('No direct access.');

class Storage
{
    /**
     * Whether containment is enforced. Set to false to disable for testing.
     *
     * @var bool
     */
    public static $enforce_containment = true;

    /**
     * Additional allowed roots beyond base path.
     *
     * @var array
     */
    public static $allowed_roots = [];

    /**
     * Validate path is inside allowed roots and free of traversal/wrappers.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function validate_path($path)
    {
        if (!static::$enforce_containment) {
            return $path;
        }

        if (!is_string($path) || '' === trim($path)) {
            throw new \Exception(sprintf('Invalid path: %s', is_string($path) ? $path : gettype($path)));
        }

        if (false !== strpos($path, "\0")) {
            throw new \Exception('Invalid path with null bytes.');
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]+://#', $path)) {
            throw new \Exception(sprintf('Stream wrapper not allowed: %s', $path));
        }

        $segments = preg_split('#[\\\\/]#', $path);

        foreach ($segments as $segment) {
            if ('..' === $segment) {
                throw new \Exception(sprintf('Path traversal not allowed: %s', $path));
            }
        }

        $normalized = str_replace(['\\', '/'], DS, $path);
        $absolute = false;

        if ('' !== $normalized) {
            if ($normalized[0] === DS) {
                $absolute = true;
            } elseif (preg_match('#^[A-Za-z]:\\\\#', $normalized) || preg_match('#^[A-Za-z]:/#', $path)) {
                $absolute = true;
            } elseif (0 === strpos($path, path('base')) || 0 === strpos($normalized, path('base'))) {
                $absolute = true;
            }
        }

        $check = $normalized;

        if (!$absolute) {
            $check = rtrim(path('base'), DS) . DS . ltrim($normalized, DS);
        }

        $real = null;

        if (is_file($check) || is_dir($check) || is_link($check)) {
            $real = realpath($check);
        } else {
            $parent = dirname($check);
            $real_parent = realpath($parent);

            if ($real_parent !== false) {
                $real = $real_parent . DS . basename($check);
            } else {
                $current = $parent;
                $suffix = basename($check);

                while ($current !== '' && $current !== DS && $current !== '.' && !is_dir($current)) {
                    $suffix = basename($current) . DS . $suffix;
                    $current = dirname($current);

                    if ($current === $parent) {
                        break;
                    }
                }

                $real_parent = realpath($current);

                if ($real_parent !== false) {
                    $real = rtrim($real_parent, DS) . DS . ltrim($suffix, DS);
                } else {
                    $real = $check;
                }
            }
        }

        if (null === $real) {
            $real = $check;
        }

        $real = str_replace(['\\', '/'], DS, $real);
        $roots = [];
        $base_real = realpath(rtrim(path('base'), DS));

        if ($base_real) {
            $roots[] = rtrim($base_real, DS);
        }

        try {
            $storage_real = realpath(rtrim(path('storage'), DS));

            if ($storage_real && !in_array($storage_real, $roots, true)) {
                $roots[] = $storage_real;
            }
        } catch (\Throwable $e) {
            // Storage path may not exist yet, ignore
        } catch (\Exception $e) {
            // Storage path may not exist yet, ignore
        }

        foreach (static::$allowed_roots as $extra) {
            $er = realpath($extra);
            if ($er) {
                $roots[] = rtrim($er, DS);
            }
        }

        $inside = false;

        foreach ($roots as $root) {
            $root = rtrim($root, DS);

            if ($real === $root || 0 === strpos($real, $root . DS)) {
                $inside = true;
                break;
            }
        }

        if (!$inside) {
            throw new \Exception(sprintf('Path outside allowed directory: %s', $path));
        }

        return $check;
    }

    /**
     * Check if a file or directory exists. For files only, use Storage::isfile().
     *
     * @param string $path
     *
     * @return bool
     */
    public static function exists($path)
    {
        $path = static::validate_path($path);
        return file_exists($path);
    }

    /**
     * Check if the given path is a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isfile($path)
    {
        $path = static::validate_path($path);
        return is_file($path);
    }

    /**
     * Check if the given path is a directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function isdir($path)
    {
        $path = static::validate_path($path);
        return is_dir($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string
     */
    public static function get($path, $default = null)
    {
        $path = static::validate_path($path);
        return is_file($path) ? file_get_contents($path) : value($default);
    }

    /**
     * Write data to a file.
     *
     * @param string $path
     * @param string $data
     * @param int    $options
     */
    public static function put($path, $data, $options = LOCK_EX)
    {
        $path = static::validate_path($path);
        file_put_contents($path, $data, $options);
        static::protect($path);
    }

    /**
     * Prepend data to a file.
     *
     * @param string $path
     * @param string $data
     */
    public static function prepend($path, $data)
    {
        $path = static::validate_path($path);
        static::put($path, $data.@file_get_contents($path));
    }

    /**
     * Append data to a file.
     *
     * @param string $path
     * @param string $data
     */
    public static function append($path, $data)
    {
        $path = static::validate_path($path);
        static::put($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     */
    public static function delete($path)
    {
        $path = static::validate_path($path);

        if (! is_file($path) && ! is_link($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        unlink($path);
    }

    /**
     * Empty a directory from files and folders.
     *
     * @param string $path
     */
    public static function cleandir($path)
    {
        static::rmdir($path, true);
    }

    /**
     * Move a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @param bool   $overwrite
     */
    public static function move($from, $to, $overwrite = false)
    {
        $from = static::validate_path($from);
        $to = static::validate_path($to);

        if (! is_file($from)) {
            throw new \Exception(sprintf('Source file does not exists: %s', $from));
        }

        if (is_file($to) && ! $overwrite) {
            throw new \Exception(sprintf('Destination file already exists: %s', $to));
        }

        rename($from, $to);
        static::protect($to);
    }

    /**
     * Move a directory.
     *
     * @param string $from
     * @param string $to
     * @param bool   $overwrite
     */
    public static function mvdir($from, $to, $overwrite = false)
    {
        $from = static::validate_path($from);
        $to = static::validate_path($to);

        if (! is_dir($from)) {
            throw new \Exception(sprintf('Source folder does not exists: %s', $from));
        }

        if (is_dir($to)) {
            if (! $overwrite) {
                throw new \Exception(sprintf('Destination folder already exists: %s', $to));
            }

            static::rmdir($to);
        }

        static::cpdir($from, $to);
        static::protect($to);
        static::rmdir($from);
    }

    /**
     * Copy file to a new location.
     *
     * @param string $path
     * @param string $target
     */
    public static function copy($path, $target)
    {
        $path = static::validate_path($path);
        $target = static::validate_path($target);

        if (function_exists('copy')) {
            copy($path, $target);
        } else {
            $fh = fopen($target, 'w');
            fwrite($fh, file_get_contents($path));
            fclose($fh);
        }

        static::protect($target);
    }

    /**
     * Copy directory to a new location.
     *
     * @param string $directory
     * @param string $destination
     * @param int    $options
     */
    public static function cpdir($directory, $destination, $options = \FilesystemIterator::SKIP_DOTS)
    {
        $directory = static::validate_path($directory);
        $destination = static::validate_path($destination);

        if (! is_dir($directory)) {
            throw new \Exception(sprintf('Source folder does not exists: %s', $directory));
        }

        if (! is_dir($destination)) {
            static::mkdir($destination, 0755);
        }

        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            $target = $destination.DS.$item->getBasename();

            if ($item->isDir()) {
                static::cpdir($item->getPathname(), $target, $options);
            } else {
                static::copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Delete a directory.
     *
     * @param string $path
     * @param bool   $preserve
     */
    public static function rmdir($path, $preserve = false)
    {
        $path = static::validate_path($path);

        if (! is_dir($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        $real = realpath($path);

        foreach (array_merge([path('base'), path('storage')], (array) static::$allowed_roots) as $guard) {
            $guard = realpath($guard);

            if ($guard && $real === rtrim($guard, DS)) {
                throw new \Exception(sprintf('Refusing to remove root: %s', $path));
            }
        }

        if (static::isdir($path)) {
            $items = new \FilesystemIterator($path);

            foreach ($items as $item) {
                if ($item->isDir() && ! $item->isLink()) {
                    static::rmdir($item->getPathname());
                } else {
                    static::delete($item->getPathname());
                }
            }

            if (! $preserve) {
                $removed = false;

                for ($attempt = 0; $attempt < 3; $attempt++) {
                    try {
                        clearstatcache(true, $path);
                        $removed = @rmdir($path);
                    } catch (\Throwable $e) {
                        $removed = false;
                    } catch (\Exception $e) {
                        $removed = false;
                    }

                    if ($removed) {
                        break;
                    }

                    if ($attempt < 2) {
                        usleep(100000);
                    }
                }

                if (! $removed) {
                    throw new \Exception(sprintf('Unable to remove path: %s', $path));
                }
            }
        }
    }

    /**
     * Get file extension.
     *
     * @param string $path
     *
     * @return string
     */
    public static function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get file type.
     *
     * @param string $path
     *
     * @return string
     */
    public static function type($path)
    {
        $path = static::validate_path($path);
        return filetype($path);
    }

    /**
     * Get file size.
     *
     * @param string $path
     *
     * @return int
     */
    public static function size($path)
    {
        $path = static::validate_path($path);
        return filesize($path);
    }

    /**
     * Get file modification time.
     *
     * @param string $path
     *
     * @return int
     */
    public static function modified($path)
    {
        $path = static::validate_path($path);
        return filemtime($path);
    }

    /**
     * Get or set file/folder permissions.
     *
     * @param string   $path
     * @param int|null $mode
     *
     * @return bool|int|string
     */
    public static function chmod($path, $mode = null)
    {
        $path = static::validate_path($path);

        if (!$mode) {
            return substr(sprintf('%o', fileperms($path)), -4);
        }

        $result = chmod($path, $mode);
        clearstatcache(true, $path);
        return $result;
    }

    /**
     * Get file name from a path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Get base file name from a path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Get directory name from a path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Guess file mime type from a path.
     *
     * @param string $path
     *
     * @return string|false
     */
    public static function mime($path)
    {
        $path = static::validate_path($path);

        if (! is_file($path) || false === ($finfo = @finfo_open(FILEINFO_MIME_TYPE))) {
            return false;
        }

        $mime = @finfo_file($finfo, $path);
        /** @disregard */
        @finfo_close($finfo);

        return $mime;
    }

    /**
     * Check an uploaded file against its mime type.
     *
     * @param array|string $extensions
     * @param string       $path
     *
     * @return bool
     */
    public static function is($extensions, $path)
    {
        $extensions = array_map('strtolower', is_array($extensions) ? array_values($extensions) : [$extensions]);
        $pool = Foundation\Http\Upload::$extensions;
        $mime = static::mime($path);

        if (isset($pool[$mime])) {
            foreach ($pool[$mime] as $extension) {
                if (in_array($extension, $extensions)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Create a directory recursively, with an index.html at each level.
     *
     * @param string $path
     * @param int    $chmod
     */
    public static function mkdir($path, $chmod = 0755)
    {
        $path = static::validate_path($path);

        if (is_dir($path)) {
            throw new \Exception(sprintf('Target folder already exists: %s', $path));
        }

        mkdir($path, $chmod, true);
        static::protect($path);
    }

    /**
     * Get the latest file in a directory.
     *
     * @param string $directory
     * @param int    $options
     *
     * @return \SplFileInfo
     */
    public static function latest($directory, $options = null)
    {
        $directory = static::validate_path($directory);
        $time = 0;
        $latest = null;
        $items = new \FilesystemIterator($directory, is_null($options) ? \FilesystemIterator::SKIP_DOTS : $options);

        foreach ($items as $item) {
            if ($item->getMTime() > $time) {
                $latest = $item;
                $time = $item->getMTime();
            }
        }

        return $latest;
    }

    /**
     * Get the MD5 hash of a file.
     *
     * @param string $path
     *
     * @return string|false
     */
    public static function hash($path)
    {
        $path = static::validate_path($path);
        return md5_file($path);
    }

    /**
     * Find path based on pattern matching.
     *
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public static function glob($pattern, $flags = 0)
    {
        // Validate glob pattern base directory (strip wildcards)
        $base = $pattern;
        $wildPos = strcspn($pattern, '*?[');

        if ($wildPos < strlen($pattern)) {
            $base = substr($pattern, 0, $wildPos);
            $base = dirname($base);
        } else {
            $base = dirname($pattern);
        }

        if ('' !== $base && '.' !== $base && false === strpos($base, '*') && false === strpos($base, '?')) {
            try {
                static::validate_path($base);
            } catch (\Throwable $e) {
                // Missing base: skip matches without a valid directory.
            } catch (\Exception $e) {
                // Missing base: skip matches without a valid directory.
            }
        }

        $files = [];

        foreach ((array) glob($pattern, $flags) as $file) {
            try {
                $files[] = static::validate_path($file);
            } catch (\Throwable $e) {
                // Match outside allowed roots: drop it.
            } catch (\Exception $e) {
                // Match outside allowed roots: drop it.
            }
        }

        return $files;
    }

    /**
     * Protect path from malicious access via browser by adding an index.html file.
     *
     * @param string $path
     */
    public static function protect($path)
    {
        $path = static::validate_path($path);

        if (! is_file($path) && ! is_dir($path)) {
            return;
        }

        $path = is_file($path) ? rtrim(dirname($path), DS) : $path;

        if (! is_file($file = $path.DS.'index.html')) {
            static::put($file, 'No direct access.'.PHP_EOL);
        }
    }
}
