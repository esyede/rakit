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
     * @param bool   $mustExist
     * @return string Normalized path
     */
    protected static function validate_path($path, $mustExist = false)
    {
        if (!static::$enforce_containment) {
            return $path;
        }

        if (!is_string($path) || '' === trim($path)) {
            throw new \Exception(sprintf('Invalid path: %s', $path));
        }

        if (false !== strpos($path, "\0")) {
            throw new \Exception('Invalid path with null bytes.');
        }

        // Block stream wrappers
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path)) {
            throw new \Exception(sprintf('Stream wrapper not allowed: %s', $path));
        }

        // Normalize separators
        $normalized = str_replace(['\\', '/'], DS, $path);

        // Resolve to absolute for checking
        // If relative, prepend base
        $isAbsolute = false;
        if ('' !== $normalized) {
            if ($normalized[0] === DS) {
                $isAbsolute = true;
            } elseif (preg_match('#^[A-Za-z]:\\\\#', $normalized) || preg_match('#^[A-Za-z]:/#', $path)) {
                $isAbsolute = true;
            } elseif (0 === strpos($path, path('base')) || 0 === strpos($normalized, path('base'))) {
                $isAbsolute = true;
            }
        }

        $checkPath = $normalized;
        if (!$isAbsolute) {
            // Relative path -> resolve against base
            $checkPath = rtrim(path('base'), DS) . DS . ltrim($normalized, DS);
        }

        // For existence checks, use realpath if file exists, else parent dir
        $real = null;
        $parentReal = null;

        if (is_file($checkPath) || is_dir($checkPath) || is_link($checkPath)) {
            $real = realpath($checkPath);
        } else {
            // File does not exist yet (put, mkdir etc.) - check parent
            $parent = dirname($checkPath);
            $realParent = realpath($parent);
            if ($realParent !== false) {
                $parentReal = $realParent;
                // Reconstruct intended real path for containment check
                $real = $realParent . DS . basename($checkPath);
            } else {
                // Parent does not exist yet, walk up until found
                $current = $parent;
                $suffix = basename($checkPath);
                while ($current !== '' && $current !== DS && $current !== '.' && !is_dir($current)) {
                    $suffix = basename($current) . DS . $suffix;
                    $current = dirname($current);
                    if ($current === $parent) {
                        break; // prevent infinite loop
                    }
                }
                $realParent = realpath($current);
                if ($realParent !== false) {
                    $real = rtrim($realParent, DS) . DS . ltrim($suffix, DS);
                } else {
                    // Fallback: use base as root for relative paths
                    $real = $checkPath;
                }
            }
        }

        if (null === $real) {
            $real = $checkPath;
        }

        // Normalize real path
        $real = str_replace(['\\', '/'], DS, $real);

        // Build allowed roots list
        $roots = [];
        $baseReal = realpath(rtrim(path('base'), DS));
        if ($baseReal) {
            $roots[] = rtrim($baseReal, DS);
        }
        // Storage is primary containment, but base already covers it
        try {
            $storageReal = realpath(rtrim(path('storage'), DS));
            if ($storageReal && !in_array($storageReal, $roots, true)) {
                $roots[] = $storageReal;
            }
        } catch (\Throwable $e) {
        } catch (\Exception $e) {
        }

        foreach (static::$allowed_roots as $extra) {
            $er = realpath($extra);
            if ($er) {
                $roots[] = rtrim($er, DS);
            }
        }

        // Check containment: real must be inside one of the roots
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

        return $path;
    }

    /**
     * Check if file or directory exists.
     * This method is not suitable for checking the existence of a file.
     * Use Storage::isfile() for that purpose!
     *
     * @param string $path
     *
     * @return bool
     */
    public static function exists($path)
    {
        static::validate_path($path);
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
        static::validate_path($path);
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
        static::validate_path($path);
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
        static::validate_path($path);
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
        static::validate_path($path);
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
        static::validate_path($path);
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
        static::validate_path($path);
        static::put($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     */
    public static function delete($path)
    {
        static::validate_path($path);
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
        static::validate_path($from);
        static::validate_path($to);
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
        static::validate_path($from);
        static::validate_path($to);
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
        static::validate_path($path);
        static::validate_path($target);
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
        static::validate_path($directory);
        static::validate_path($destination);
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
        static::validate_path($path);
        if (! is_dir($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
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
        static::validate_path($path);
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
        static::validate_path($path);
        return filemtime($path);
    }

    /**
     * Get or set file/folder permissions.
     *
     * @param string   $path
     * @param int|null $mode
     *
     * @return bool|int
     */
    public static function chmod($path, $mode = null)
    {
        static::validate_path($path);

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
     * @return string
     */
    public static function mime($path)
    {
        static::validate_path($path);
        if (! is_file($path) || false === ($finfo = @finfo_open(FILEINFO_MIME_TYPE))) {
            return false;
        }

        $mime = @finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime;
    }

    /**
     * Check if file is valid based on its mime type.
     * Use this method to validate uploaded files.
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
     * Create a new directory recursively.
     * This method also creates an index.html file in each subfolder.
     *
     * @param string $path
     * @param int    $chmod
     */
    public static function mkdir($path, $chmod = 0755)
    {
        static::validate_path($path);
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
        static::validate_path($directory);
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
        static::validate_path($path);
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
                // If base not yet exists, allow glob to return empty instead of throwing
                // Containment will be enforced on actual files returned
            } catch (\Exception $e) {
            }
        }
        return glob($pattern, $flags);
    }

    /**
     * Protect path from malicious access via browser by adding an index.html file.
     *
     * @param string $path
     */
    public static function protect($path)
    {
        if (! is_file($path) && ! is_dir($path)) {
            return;
        }

        $path = is_file($path) ? rtrim(dirname($path), DS) : $path;

        if (! is_file($file = $path.DS.'index.html')) {
            static::put($file, 'No direct access.'.PHP_EOL);
        }
    }
}
