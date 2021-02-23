<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Cookie
{
    /**
     * Berisi list cookie terdaftar.
     *
     * @var array
     */
    public static $jar = [];

    /**
     * Cek cookie ada atau tidak.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has($name)
    {
        return ! is_null(static::get($name));
    }

    /**
     * Ambil value cookie.
     *
     * <code>
     *
     *      // Ambil value cookie 'makanan'
     *      $makanan = Cookie::get('makanan');
     *
     *      // Return default value jika cookie tidak ketemu
     *      $makanan = Cookie::get('makanan', 'Mie Ayam');
     *
     * </code>
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return string
     */
    public static function get($name, $default = null)
    {
        if (isset(static::$jar[$name])) {
            return Crypter::decrypt(static::$jar[$name]['value']);
        }

        if (! is_null($value = Request::foundation()->cookies->get($name))) {
            return Crypter::decrypt($value);
        }

        return value($default);
    }

    /**
     * Set value cookie.
     *
     * <code>
     *
     *      // Set value cookie 'makanan'
     *      Cookie::put('makanan', 'Mie Ayam');
     *
     *      // Set waktu kadaluwarsa cookie 20 menit
     *      Cookie::put('makanan', 'Mie Ayam', 20);
     *
     * </code>
     *
     * @param string $name
     * @param string $value
     * @param int    $expiration
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     */
    public static function put(
        $name,
        $value,
        $expiration = 0,
        $path = '/',
        $domain = null,
        $secure = false,
        $samesite = null
    ) {
        if (0 !== (int) $expiration) {
            $expiration = time() + ($expiration * 60);
        }

        if (null === $samesite || '' === $samesite) {
            $samesite = Config::get('session.samesite', 'Lax');
        }

        $samesite = is_string($samesite) ? strtolower($samesite) : $samesite;

        if (! in_array($samesite, ['lax', 'strict', 'none', null], true)) {
            throw new \InvalidArgumentException('The "samesite" parameter value is not valid.');
        }

        $value = Crypter::encrypt($value);

        // Jika $secure nilainya TRUE, cookie hanya bisa diakses via HTTPS.
        if ($secure && ! Request::secure()) {
            throw new \Exception('Attempting to set secure cookie over HTTP.');
        }

        static::$jar[$name] = compact('name', 'value', 'expiration', 'path', 'domain', 'secure', 'samesite');
    }

    /**
     * Set cookie permanen (Aktif selama 5 tahun).
     *
     * <code>
     *
     *      // Set cookie 'makanan' secara permanen
     *      Cookie::forever('makanan', 'Bakso');
     *
     * </code>
     *
     * @param string $name
     * @param string $value
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return bool
     */
    public static function forever($name, $value, $path = '/', $domain = null, $secure = false, $samesite = null)
    {
        return static::put($name, $value, 2628000, $path, $domain, $secure, $samesite);
    }

    /**
     * Hapus cookie.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return bool
     */
    public static function forget($name, $path = '/', $domain = null, $secure = false, $samesite = null)
    {
        return static::put($name, null, -2628000, $path, $domain, $secure, $samesite);
    }
}
