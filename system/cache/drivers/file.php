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
        return !is_null($this->get($key));
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
        $key = $this->naming($key);

        if (!is_file($this->path . $key)) {
            return;
        }

        $cache = Storage::get($this->path . $key);
        $cache = (string) $this->unguard($cache);
        return (time() >= substr($cache, 0, 10)) ? $this->forget($key) : unserialize(substr($cache, 10));
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * <code>
     *
     *      // Store an item in the cache for 15 minutes
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
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
        $value = $this->guard($this->expiration($minutes) . serialize($value));
        Storage::put($this->path . $key, $value, LOCK_EX);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $key =  $this->path . $this->naming($key);
        is_file($key) && Storage::delete($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        $files = glob(path('storage') . 'cache' . DS . '*.cache.php');

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
        return sprintf('%u', crc32($key)) . '.cache.php';
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
        return "<?php defined('DS') or exit('No direct access.');?>" . $value;
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
