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
     * Increment a numeric value in the cache.
     * The whole read, add and write happens under one exclusive lock, so two
     * requests arriving together cannot both write the same number.
     *
     * @param string $key
     * @param int    $minutes
     *
     * @return int
     */
    public function increment($key, $minutes = 1)
    {
        $path = $this->path.$this->naming($key);
        $handle = @fopen($path, 'c+');

        if (false === $handle) {
            return parent::increment($key, $minutes);
        }

        if (! flock($handle, LOCK_EX)) {
            fclose($handle);
            return parent::increment($key, $minutes);
        }

        $cache = (string) $this->unguard((string) stream_get_contents($handle));
        $current = 0;

        if ('' !== $cache && time() < (int) substr($cache, 0, 10)) {
            $current = (int) @unserialize(substr($cache, 10));
        }

        $new = $current + 1;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $this->guard($this->expiration($minutes).serialize($new)));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $new;
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
