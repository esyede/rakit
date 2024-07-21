<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Storage;

class File extends Driver
{
    /**
     * Berisi path file cache.
     *
     * @var string
     */
    protected $path;

    /**
     * Buat instance driver file baru.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Cek apakah item ada di cache.
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
     * Ambil item dari driver cache.
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

        return (time() >= substr($cache, 0, 10))
            ? $this->forget($key)
            : unserialize(substr($cache, 10));
    }

    /**
     * Simpan item ke cache untuk beberapa menit.
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit.
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
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $key = $this->naming($key);

        if (is_file($key = $this->path . $key)) {
            Storage::delete($key);
        }
    }

    /**
     * Hapus seluruhitem cache.
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
     * Helper untuk format nama file cache.
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
     * Helper untuk proteksi akses file via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        return $guard . $value;
    }

    /**
     * Helper untuk buang proteksi akses file via browser.
     * (Kebalikan dari method guard).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        return str_replace($guard, '', $value);
    }
}
