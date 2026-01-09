<?php

namespace System;

defined('DS') or exit('No direct access.');

class Cookie
{
    /**
     * Berisi list cookie terdaftar.
     *
     * @var array
     */
    public static $jar = [];

    /**
     * Cache untuk decrypted cookie values.
     *
     * @var array
     */
    private static $cache = [];

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
        if (!is_string($name) || empty($name) || !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new \Exception('Cookie name must be a non-empty string containing only alphanumeric characters, underscores, and hyphens.');
        }

        if (isset(static::$cache[$name])) {
            return static::$cache[$name];
        }

        if (isset(static::$jar[$name]) && isset(static::$jar[$name]['value'])) {
            try {
                $value = Crypter::decrypt(static::$jar[$name]['value']);
                static::$cache[$name] = $value;
                return $value;
            } catch (\Exception $e) {
                throw new \Exception('Failed to decrypt cookie value: ' . $e->getMessage());
            }
        }

        $value = Request::foundation()->cookies->get($name);

        if (is_null($value)) {
            return value($default);
        }

        try {
            $decrypted = Crypter::decrypt($value);
            static::$cache[$name] = $decrypted;
            return $decrypted;
        } catch (\Exception $e) {
            throw new \Exception('Failed to decrypt cookie value: ' . $e->getMessage());
        }
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
        if (!is_string($name) || empty($name) || !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            throw new \Exception('Cookie name must be a non-empty string containing only alphanumeric characters, underscores, and hyphens.');
        }

        if (!is_string($value)) {
            throw new \Exception('Cookie value must be a string.');
        }

        $path = (!is_string($path) || empty($path)) ? '/' : $path;

        if (!is_null($domain)) {
            if (PHP_VERSION_ID >= 70000) {
                $check = (strpos($domain, '.') === 0) ? substr($domain, 1) : $domain;
                if (!filter_var($check, FILTER_VALIDATE_DOMAIN)) {
                    throw new \Exception('Cookie domain must be a valid domain.');
                }
            } else {
                $trimmed = trim($domain);
                $predot = (strpos($trimmed, '.') === 0);
                $target = $predot ? substr($trimmed, 1) : $trimmed;

                if (strlen($target) > 253 || strlen($target) === 0) {
                    throw new \Exception('Cookie domain must be a valid domain.');
                }

                $labels = explode('.', $target);

                if (count($labels) < 1) {
                    throw new \Exception('Cookie domain must be a valid domain.');
                }

                foreach ($labels as $label) {
                    if (strlen($label) === 0 || strlen($label) > 63) {
                        throw new \Exception('Cookie domain must be a valid domain.');
                    }

                    if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i', $label)) {
                        throw new \Exception('Cookie domain must be a valid domain.');
                    }
                }
            }
        }

        if ($secure && !Request::secure() && !defined('RAKIT_PHPUNIT_RUNNING')) {
            throw new \Exception('Attempting to set secure cookie over HTTP.');
        }

        $expiration = (0 === (int) $expiration) ? 0 : (time() + ($expiration * 60));
        $samesite = strtolower((string) is_null($samesite) ? Config::get('session.samesite', 'lax') : $samesite);

        if (!in_array($samesite, ['lax', 'strict', 'none'])) {
            throw new \Exception(sprintf('The "samesite" parameter value is not valid: %s (%s)', $samesite, gettype($samesite)));
        }

        try {
            $encrypted = Crypter::encrypt($value);
        } catch (\Throwable $e) {
            throw new \Exception('Failed to encrypt cookie value: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed to encrypt cookie value: ' . $e->getMessage());
        }

        static::$jar[$name] = compact('name', 'value', 'expiration', 'path', 'domain', 'secure', 'samesite');
        static::$jar[$name]['value'] = $encrypted;

        unset(static::$cache[$name]);
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
        return static::put($name, '', -2628000, $path, $domain, $secure, $samesite);
    }
}
