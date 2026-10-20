<?php

namespace System\Storage;

defined('DS') or exit('No direct access.');

class Local
{
    /**
     * The disk configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config root, and optionally url (the public URL of the root)
     */
    public function __construct(array $config)
    {
        if (empty($config['root'])) {
            throw new \Exception('The local disk needs a "root" in its configuration.');
        }

        $this->config = array_merge(['url' => null], $config);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists($path)
    {
        return is_file($this->path($path));
    }

    /**
     * Get a file's contents, or $default when it does not exist.
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string|mixed
     */
    public function get($path, $default = null)
    {
        return $this->exists($path) ? file_get_contents($this->path($path)) : $default;
    }

    /**
     * Store a file, making its directory when needed.
     *
     * @param string $path
     * @param string $contents
     * @param array  $options unused, for the same calls as the S3 disk
     *
     * @return bool
     */
    public function put($path, $contents, array $options = [])
    {
        $target = $this->path($path);

        if (! is_dir(dirname($target)) && ! @mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            throw new \Exception(sprintf('Unable to create directory: %s', dirname($target)));
        }

        if (false === file_put_contents($target, (string) $contents, LOCK_EX)) {
            throw new \Exception(sprintf('Unable to write: %s', $target));
        }

        return true;
    }

    /**
     * Store an uploaded or local file.
     *
     * @param string $path
     * @param string $file
     * @param array  $options
     *
     * @return bool
     */
    public function put_file($path, $file, array $options = [])
    {
        if (! is_file($file) || false === ($contents = file_get_contents($file))) {
            throw new \Exception(sprintf('Unable to read: %s', $file));
        }

        return $this->put($path, $contents, $options);
    }

    /**
     * Delete one or more files. Deleting a missing file is not an error.
     *
     * @param string|array $paths
     *
     * @return bool
     */
    public function delete($paths)
    {
        foreach ((array) $paths as $path) {
            $this->exists($path) && unlink($this->path($path));
        }

        return true;
    }

    /**
     * Copy a file.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copy($from, $to)
    {
        if (! $this->exists($from)) {
            throw new \Exception(sprintf('File not found: %s', $from));
        }

        return $this->put($to, file_get_contents($this->path($from)));
    }

    /**
     * Move a file.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function move($from, $to)
    {
        return $this->copy($from, $to) && $this->delete($from);
    }

    /**
     * Get a file's size in bytes.
     *
     * @param string $path
     *
     * @return int
     */
    public function size($path)
    {
        return (int) filesize($this->path($path));
    }

    /**
     * Get when a file was last changed, as a UNIX timestamp.
     *
     * @param string $path
     *
     * @return int
     */
    public function modified($path)
    {
        return (int) filemtime($this->path($path));
    }

    /**
     * List the files under a directory, every level deep.
     *
     * @param string $directory
     *
     * @return array paths relative to the disk root
     */
    public function files($directory = '')
    {
        $root = rtrim($this->config['root'], '/\\').DS;
        $start = $this->path($directory);
        $files = [];

        if (! is_dir($start)) {
            return $files;
        }

        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($start, \FilesystemIterator::SKIP_DOTS));

        foreach ($items as $item) {
            if ($item->isFile()) {
                $files[] = str_replace(DS, '/', substr($item->getPathname(), strlen($root)));
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Get a file's public URL, from the disk's 'url'.
     *
     * @param string $path
     *
     * @return string
     */
    public function url($path)
    {
        if (empty($this->config['url'])) {
            throw new \Exception('The local disk has no "url" in its configuration.');
        }

        return rtrim($this->config['url'], '/').'/'.str_replace('%2F', '/', rawurlencode(ltrim($this->clean($path), '/')));
    }

    /**
     * Get the absolute path of a file.
     *
     * @param string $path
     *
     * @return string
     */
    public function path($path)
    {
        return rtrim($this->config['root'], '/\\').DS.str_replace('/', DS, ltrim($this->clean($path), '/'));
    }

    /**
     * Refuse paths climbing out of the root.
     *
     * @param string $path
     *
     * @return string
     */
    protected function clean($path)
    {
        $path = str_replace('\\', '/', (string) $path);

        if (in_array('..', explode('/', $path), true)) {
            throw new \Exception(sprintf('Path traversal not allowed: %s', $path));
        }

        return $path;
    }
}
