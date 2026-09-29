<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Exceptions\DecryptException;

class Cookie
{
    /**
     * Contains list of registered cookies.
     *
     * @var array
     */
    public static $jar = [];

    /**
     * Cache for decrypted cookie values.
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Check if a cookie exists.
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
     * Get the value of a cookie.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return string
     */
    public static function get($name, $default = null)
    {
        static::guard_name($name);

        if (isset(static::$cache[$name])) {
            return static::$cache[$name];
        }

        if (isset(static::$jar[$name]) && isset(static::$jar[$name]['value'])) {
            $value = static::unseal($name, static::$jar[$name]['value']);
        } else {
            $value = static::unseal($name, Request::foundation()->cookies->get($name));
        }

        if (is_null($value)) {
            return value($default);
        }

        static::$cache[$name] = $value;
        return $value;
    }

    /**
     * Encrypt a cookie value bound to its name, so the ciphertext of one
     * cookie (or any other Crypter output) is not accepted as another cookie.
     *
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public static function seal($name, $value)
    {
        return Crypter::encrypt(hash_hmac('sha256', 'cookie|'.$name, RAKIT_KEY).$value);
    }

    /**
     * Decrypt a sealed cookie value, or NULL when it is invalid or belongs to another name.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return string|null
     */
    protected static function unseal($name, $value)
    {
        if (! is_string($value) || '' === $value) {
            return null;
        }

        try {
            $value = Crypter::decrypt($value);
        } catch (DecryptException $e) {
            return null;
        }

        $prefix = hash_hmac('sha256', 'cookie|'.$name, RAKIT_KEY);

        if (! Crypter::equals($prefix, (string) substr($value, 0, 64))) {
            return null;
        }

        return (string) substr($value, 64);
    }

    /**
     * Set a cookie.
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
        static::guard_name($name);

        if (! is_string($value)) {
            throw new \Exception('Cookie value must be a string.');
        }

        $path = (! is_string($path) || empty($path)) ? '/' : $path;

        if (preg_match('/[,; \t\r\n\013\014]/', $path)) {
            throw new \Exception('Cookie path must not contain a separator, a space or a line break.');
        }

        if (! is_null($domain)) {
            if (preg_match('/[,; \t\r\n\013\014]/', (string) $domain)) {
                throw new \Exception('Cookie domain must not contain a separator, a space or a line break.');
            }

            if (PHP_VERSION_ID >= 70000) {
                $check = (strpos($domain, '.') === 0) ? substr($domain, 1) : $domain;

                if (! filter_var($check, FILTER_VALIDATE_DOMAIN)) {
                    throw new \Exception('Cookie domain must be a valid domain.');
                }
            } else {
                $trimmed = trim($domain);
                $target = (strpos($trimmed, '.') === 0) ? substr($trimmed, 1) : $trimmed;

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

                    if (! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/i', $label)) {
                        throw new \Exception('Cookie domain must be a valid domain.');
                    }
                }
            }
        }

        if ($secure && ! Request::secure() && ! defined('RAKIT_PHPUNIT_RUNNING')) {
            throw new \Exception('Attempting to set secure cookie over HTTP.');
        }

        $expiration = (0 === (int) $expiration) ? 0 : (time() + ($expiration * 60));
        $samesite = is_null($samesite) ? Config::get('session.samesite', 'lax') : $samesite;
        $samesite = strtolower((string) $samesite);

        if (! in_array($samesite, ['lax', 'strict', 'none'])) {
            throw new \Exception(sprintf('The "samesite" parameter value is not valid: %s (%s)', $samesite, gettype($samesite)));
        }

        try {
            $encrypted = static::seal($name, $value);
        } catch (\Throwable $e) {
            throw new \Exception('Failed to encrypt cookie value: '.$e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed to encrypt cookie value: '.$e->getMessage());
        }

        static::$jar[$name] = compact('name', 'value', 'expiration', 'path', 'domain', 'secure', 'samesite');
        static::$jar[$name]['value'] = $encrypted;

        unset(static::$cache[$name]);
    }

    /**
     * Make sure a cookie name is one HTTP allows.
     *
     * @param string $name
     */
    protected static function guard_name($name)
    {
        if (! is_string($name) || '' === $name || ! preg_match('/^[a-zA-Z0-9_.-]+$/', $name)) {
            throw new \Exception(
                'Cookie name must be a non-empty string containing '
                . 'only alphanumeric characters, underscores, dots, and hyphens.'
            );
        }
    }

    /**
     * Set a permanent cookie (Active for 5 years).
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
    public static function forever($name, $value, $path = '/', $domain = null, $secure = false, $samesite = 'lax')
    {
        return static::put($name, $value, 2628000, $path, $domain, $secure, $samesite);
    }

    /**
     * Forget every queued cookie, and the decrypted values cached for them.
     */
    public static function flush()
    {
        static::$jar = [];
        static::$cache = [];
    }

    /**
     * Delete a cookie.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param string $samesite
     *
     * @return bool
     */
    public static function forget($name, $path = '/', $domain = null, $secure = false, $samesite = 'lax')
    {
        return static::put($name, '', -2628000, $path, $domain, $secure, $samesite);
    }
}
