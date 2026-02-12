<?php

namespace System;

defined('DS') or exit('No direct access.');

class Storage
{
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
        return is_dir($path);
    }

    /**
     * Get the contents of a file.
     *
     * <code>
     *
     *      // Get the contents of a file
     *      $contents = Storage::get(path('app').'routes.php');
     *
     *      // Get the contents of a file or return default value if file not found
     *      $contents = Storage::get(path('app').'routes.php', 'File not found!');
     *
     * </code>
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string
     */
    public static function get($path, $default = null)
    {
        return static::isfile($path) ? file_get_contents($path) : value($default);
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
        static::put($path, $data . static::get($path));
    }

    /**
     * Append data to a file.
     *
     * @param string $path
     * @param string $data
     */
    public static function append($path, $data)
    {
        static::put($path, $data, LOCK_EX | FILE_APPEND);
    }

    /**
     * Delete a file.
     *
     * @param string $path
     */
    public static function delete($path)
    {
        if (!static::isfile($path) && !is_link($path)) {
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
        if (!static::isfile($from)) {
            throw new \Exception(sprintf('Source file does not exists: %s', $from));
        }

        if (static::isfile($to) && !$overwrite) {
            throw new \Exception(sprintf('Destination file does not exists: %s', $to));
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
        if (!static::isdir($from)) {
            throw new \Exception(sprintf('Source folder does not exists: %s', $from));
        }

        if (static::isdir($to)) {
            if (!$overwrite) {
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
        if (!static::isdir($directory)) {
            throw new \Exception(sprintf('Source folder does not exists: %s', $directory));
        }

        if (!static::isdir($destination)) {
            static::mkdir($destination, 0755);
        }

        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            $target = $destination . DS . $item->getBasename();

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
        if (!static::isdir($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        if (static::isdir($path)) {
            $items = new \FilesystemIterator($path);

            foreach ($items as $item) {
                if ($item->isDir() && !$item->isLink()) {
                    static::rmdir($item->getPathname());
                } else {
                    static::delete($item->getPathname());
                }
            }

            if (!$preserve) {
                try {
                    rmdir($path);
                } catch (\Throwable $e) {
                    throw new \Exception(sprintf('Unable to remove path: %s', $path));
                } catch (\Exception $e) {
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
        return $mode ? chmod($path, $mode) : substr(sprintf('%o', fileperms($path)), -4);
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
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Check if file is valid based on its mime type.
     * Use this method to validate uploaded files.
     *
     * <code>
     *
     *      // Check if file is a JPG image
     *      $jpg = Storage::is('jpg', 'path/to/file.jpg');
     *
     *      // Check if file is a JPG or PNG image
     *      $image = Storage::is(['jpg', 'png'], 'path/to/file');
     *
     * </code>
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
        if (static::isdir($path)) {
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
        return glob($pattern, $flags);
    }

    /**
     * Protect path from malicious access via browser by adding an index.html file.
     *
     * @param string $path
     */
    public static function protect($path)
    {
        if (!is_file($path) && !is_dir($path)) {
            return;
        }

        $path = is_file($path) ? rtrim(dirname($path), DS) : $path;

        if (!is_file($file = $path . DS . 'index.html')) {
            static::put($file, 'No direct access.' . PHP_EOL);
        }
    }
}
