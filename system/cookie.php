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
        return !is_null(static::get($name));
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
        if (isset(static::$jar[$name]) && isset(static::$jar[$name]['value'])) {
            return Crypter::decrypt(static::$jar[$name]['value']);
        }

        $value = Request::foundation()->cookies->get($name);
        return is_null($value) ? value($default) : Crypter::decrypt($value);
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
     * @param string $samesite
     */
    public static function put(
        $name,
        $value,
        $expiration = 0,
        $path = '/',
        $domain = null,
        $secure = false,
        $samesite = 'lax'
    ) {
        // Jika $secure nilainya TRUE, cookie hanya bisa diakses via HTTPS.
        if ($secure && !Request::secure()) {
            throw new \Exception('Attempting to set secure cookie over HTTP.');
        }

        $expiration = (0 === (int) $expiration) ? 0 : (time() + ($expiration * 60));
        $samesite = is_null($samesite) ? Config::get('session.samesite', 'lax') : $samesite;
        $samesite = strtolower((string) $samesite);

        if (!in_array($samesite, ['lax', 'strict', 'none'])) {
            throw new \Exception(sprintf(
                'The "samesite" parameter value is not valid: %s (%s)',
                $samesite,
                gettype($samesite)
            ));
        }

        $value = Crypter::encrypt($value);
        static::$jar[$name] = [
            'name' => $name,
            'value' => $value,
            'expiration' => $expiration,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'samesite' => $samesite,
        ];
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
     * @param string $samesite
     *
     * @return bool
     */
    public static function forever(
        $name,
        $value,
        $path = '/',
        $domain = null,
        $secure = false,
        $samesite = 'lax'
    ) {
        return static::put($name, $value, 2628000, $path, $domain, $secure, $samesite);
    }

    /**
     * Hapus cookie.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param string $samesite
     *
     * @return bool
     */
    public static function forget(
        $name,
        $path = '/',
        $domain = null,
        $secure = false,
        $samesite = 'lax'
    ) {
        return static::put($name, null, -2628000, $path, $domain, $secure, $samesite);
    }
}
