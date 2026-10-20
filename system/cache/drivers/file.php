<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Storage;

class File extends Driver
{
    /**
     * Contains the path to the cache directory.
     *
     * @var string
     */
    protected $path;

    /**
     * Make a new file cache driver instance.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * Retrieve an item from the cache driver.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function retrieve($key)
    {
        $path = $this->path.$this->naming($key);

        if (! is_file($path)) {
            return;
        }

        $cache = (string) $this->unguard(Storage::get($path));

        if (time() >= (int) substr($cache, 0, 10)) {
            $this->forget($key);
            return;
        }

        $value = @unserialize(substr($cache, 10));

        return (false === $value && 'b:0;' !== substr($cache, 10)) ? null : $value;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        if ($minutes <= 0) {
            return;
        }

        $key = $this->naming($key);
        $value = $this->guard($this->expiration($minutes).serialize($value));
        Storage::put($this->path.$key, $value, LOCK_EX);
    }

    /**
     * Increment a numeric value in the cache, under a file lock.
     *
     * @param string   $key
     * @param int|null $minutes
     * @param int      $by
     *
     * @return int
     */
    public function increment($key, $minutes = 1, $by = 1)
    {
        $expiration = $this->expiration($minutes);
        $result = $this->locked($key, function ($current) use ($by, $expiration) {
            $new = ((int) $current) + (int) $by;
            return [$new, [$new, $expiration]];
        });

        return is_null($result) ? parent::increment($key, $minutes, $by) : $result;
    }

    /**
     * Store a value only when the key is not there yet, under a file lock.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $seconds
     *
     * @return bool
     */
    protected function add_seconds($key, $value, $seconds)
    {
        $result = $this->locked($key, function ($current) use ($value, $seconds) {
            return is_null($current) ? [true, [$value, time() + (int) $seconds]] : [false, null];
        });

        return is_null($result) ? parent::add_seconds($key, $value, $seconds) : $result;
    }

    /**
     * Release a lock its owner still holds, under a file lock.
     *
     * @param string $name
     * @param string $owner
     *
     * @return bool
     */
    public function release_lock($name, $owner)
    {
        $result = $this->locked('lock:'.$name, function ($current) use ($owner) {
            return ((string) $owner === $current) ? [true, false] : [false, null];
        });

        return is_null($result) ? parent::release_lock($name, $owner) : $result;
    }

    /**
     * Read and change an entry while holding an exclusive lock on its file. The
     * callback gets the live value (NULL when missing or expired) and returns
     * [result, change], the change being [value, expiration], FALSE to empty the
     * entry, or NULL to leave it. NULL when the file cannot be locked.
     *
     * @param string   $key
     * @param \Closure $callback
     *
     * @return mixed
     */
    protected function locked($key, \Closure $callback)
    {
        $handle = @fopen($this->path.$this->naming($key), 'c+');

        if (false === $handle) {
            return null;
        }

        if (! flock($handle, LOCK_EX)) {
            fclose($handle);
            return null;
        }

        $cache = (string) $this->unguard((string) stream_get_contents($handle));
        $current = null;

        if ('' !== $cache && time() < (int) substr($cache, 0, 10)) {
            $current = @unserialize(substr($cache, 10));
            $current = (false === $current && 'b:0;' !== substr($cache, 10)) ? null : $current;
        }

        list($result, $change) = $callback($current);

        if (! is_null($change)) {
            ftruncate($handle, 0);
            rewind($handle);

            if (false !== $change) {
                fwrite($handle, $this->guard($change[1].serialize($change[0])));
            }

            fflush($handle);
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return $result;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $key = $this->path.$this->naming($key);
        is_file($key) && Storage::delete($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        $files = glob($this->path.'*.cache.php');

        if (is_array($files) && count($files) > 0) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Helper methhod for creating a unique file name for the given cache key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function naming($key)
    {
        return sha1((string) $key).'.cache.php';
    }

    /**
     * Helper method for adding protection to the cache file to prevent direct access via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        return "<?php defined('DS') or exit('No direct access.');?>".$value;
    }

    /**
     * Helper method for removing the protection from the cache file when retrieving the value.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        return str_replace("<?php defined('DS') or exit('No direct access.');?>", '', $value);
    }
}
