<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct script access.');

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
     * Ambil item dari cache, atau taruh item tersebut ke cache dan return default value.
     *
     * <code>
     *
     *      // Ambil sebuah item dari cache, atau taruh item tersebut ke cache selama 15 menit
     *      $name = Cache::remember('name', 'Budi', 15);
     *
     *      // Gunakan closure sebagai value item cache
     *      $count = Cache::remember('count', function() { return User::count(); }, 15);
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     * @param int    $minutes
     * @param string $function
     *
     * @return mixed
     */
    public function remember($key, $default, $minutes, $function = 'put')
    {
        if (! is_null($item = $this->get($key, null))) {
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
        return $this->remember($key, $default, null, 'forever');
    }

    /**
     * Hapus sebuah item dari cache.
     *
     * @param string $key
     */
    abstract public function forget($key);

    /**
     * Ambil waktu kedaluwarsa cache (dalam unix timestamp).
     *
     * @param int $minutes
     *
     * @return int
     */
    protected function expiration($minutes)
    {
        return (time() + ($minutes * 60));
    }
}
