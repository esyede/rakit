<?php

namespace System;

defined('DS') or exit('No direct access.');

class Maintenance
{
    /**
     * The cookie that lets a visitor through, once they opened the secret URI.
     */
    const COOKIE = 'rakit_maintenance';

    /**
     * Put the application in maintenance mode.
     *
     * @param string|null $secret  a URI segment that lets its visitor through
     * @param int|null    $retry   seconds for the Retry-After header
     * @param array       $allowed client IPs that are let through
     */
    public static function down($secret = null, $retry = null, array $allowed = [])
    {
        $data = [
            'time' => time(),
            'retry' => is_null($retry) ? null : max(0, (int) $retry),
            'secret' => (is_null($secret) || '' === (string) $secret) ? null : static::sign($secret),
            'allowed' => array_values(array_filter(array_map('trim', $allowed), 'strlen')),
        ];

        Storage::put(static::path(), "<?php defined('DS') or exit('No direct access.');?>".json_encode($data), LOCK_EX);
    }

    /**
     * Bring the application back up.
     */
    public static function up()
    {
        if (is_file(static::path())) {
            Storage::delete(static::path());
        }
    }

    /**
     * Check whether the application is in maintenance mode.
     *
     * @return bool
     */
    public static function active()
    {
        return is_file(static::path());
    }

    /**
     * Get the maintenance data, or NULL when the application is up.
     *
     * @return array|null
     */
    public static function data()
    {
        $content = @file_get_contents(static::path());

        if (false === $content) {
            return null;
        }

        $data = json_decode(substr($content, strpos($content, '?>') + 2), true);

        return is_array($data) ? $data : ['time' => 0, 'retry' => null, 'secret' => null, 'allowed' => []];
    }

    /**
     * Answer the current request while in maintenance mode: a 503, or for the secret
     * URI a redirect home with the bypass cookie. NULL when the request may go on.
     *
     * @return Response|null
     */
    public static function check()
    {
        if (! is_file(static::path()) || is_null($data = static::data())) {
            return null;
        }

        if (in_array(Request::ip(), (array) $data['allowed'], true)) {
            return null;
        }

        if (! is_null($data['secret'])) {
            if (Crypter::equals($data['secret'], (string) Cookie::get(static::COOKIE))) {
                return null;
            }

            if (Crypter::equals($data['secret'], static::sign(trim((string) URI::current(), '/')))) {
                Cookie::put(static::COOKIE, $data['secret'], 60 * 12);
                return Redirect::to('/');
            }
        }

        return Response::error(503, is_null($data['retry']) ? [] : ['Retry-After' => (string) $data['retry']]);
    }

    /**
     * Get the path of the maintenance file.
     *
     * @return string
     */
    public static function path()
    {
        return path('storage').'down.php';
    }

    /**
     * Sign a secret with the application key.
     *
     * @param string $secret
     *
     * @return string
     */
    protected static function sign($secret)
    {
        return hash_hmac('sha256', 'rakit-maintenance|'.$secret, RAKIT_KEY);
    }
}
