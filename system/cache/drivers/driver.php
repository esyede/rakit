<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

abstract class Driver
{
    /**
     * Periksa apakah item ada di cache.
     *
     * @param string $key
     *
     * @return bool
     */
    abstract public function has($key);

    /**
     * Ambil sebuah item dari cache.
     *
     * <code>
     *
     *      // Ambil sebuah item dari driver cache
     *      $name = Cache::driver('name');
     *
     *      // Return default value jika item tidak ditemukan
     *      $name = Cache::get('name', 'Budi');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $item = $this->retrieve($key);
        return is_null($item) ? value($default) : $item;
    }

    /**
     * Ambil sebuah item dari cache dan hapus item tersebut.
     *
     * <code>
     *
     *      // Ambil dan hapus sebuah item dari cache
     *      $value = Cache::pull('key');
     *
     *      // Return default value jika item tidak ditemukan
     *      $value = Cache::pull('key', 'default');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Ambil item dari driver cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract protected function retrieve($key);

    /**
     * Simpan sebuah item ke cache untuk beberapa menit.
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    abstract public function put($key, $value, $minutes);

    /**
     * Simpan sebuah item ke cache selamanya (Aktif selama 5 tahun).
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit
     *      Cache::forever('name', 'Budi');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 2628000);
    }

    /**
     * Ambil item dari cache, atau taruh item tersebut ke cache dan return default value.
     *
     * <code>
     *
     *      // Ambil sebuah item dari cache, atau taruh item tersebut ke cache selama 15 menit
     *      $name = Cache::remember('name', 15, 'Budi');
     *
     *      // Gunakan closure sebagai value item cache
     *      $count = Cache::remember('count', 15, function() { return User::count(); });
     *
     * </code>
     *
     * @param string $key
     * @param int    $minutes
     * @param mixed  $default
     * @param string $function
     *
     * @return mixed
     */
    public function remember($key, $minutes, $default, $function = 'put')
    {
        if (!is_null($item = $this->get($key, null))) {
            return $item;
        }

        $this->{$function}($key, $default = value($default), $minutes);
        return $default;
    }

    /**
     * Anbil sebuah item dari cache, atau taruh item tersebut ke cache selamanya (atau 5 tahun).
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function sear($key, $default)
    {
        return $this->remember($key, null, $default, 'forever');
    }

    /**
     * Hapus sebuah item dari cache.
     *
     * @param string $key
     */
    abstract public function forget($key);

    /**
     * Hapus seluruh item cache.
     */
    abstract public function flush();

    /**
     * Ambil waktu kedaluwarsa cache (dalam unix timestamp).
     *
     * @param int $minutes
     *
     * @return int
     */
    protected function expiration($minutes)
    {
        return time() + ($minutes * 60);
    }
}
