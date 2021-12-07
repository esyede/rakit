<?php

namespace System\Routing;

defined('DS') or exit('No direct script access.');

use System\Cache;
use System\Request;
use System\Response;

class Throttle
{
    /**
     * Prefix untuk cache milik throttle.
     */
    const PREFIX = 'rakit.throttle';

    /**
     * Jalankan proses throttling.
     *
     * @param int $limit
     * @param int $minutes
     *
     * @return \System\Response
     */
    public static function check($limit, $minutes = 1)
    {
        $limit = (int) $limit;
        $limit = ($limit < 1) ? 1 : $limit;

        $minutes = (int) $minutes;
        $minutes = ($minutes < 1) ? 1 : $minutes;

        $key = static::key();
        $ip = static::ip();

        if (! Cache::has($key)) {
            $payloads = [
                'limit' => $limit,
                'remaining' => $limit,
                'reset' => time() + ($minutes * 3600),
                'retry' => $minutes * 3600,
                'key' => $key,
                'ip' => $ip,
            ];

            Cache::put($key, $payloads, $minutes);
        }

        $payloads = Cache::get($key);

        if ($payloads['remaining'] > 0) {
            $payloads['remaining'] = $payloads['remaining'] - 1;
            Cache::put($key, $payloads, $minutes);
            return true;
        }

        if ($payloads['reset'] > time()) {
            $payloads['reset'] = time() + ($minutes * 3600);
            return false;
        }

        Cache::forget($key);
        return false;
    }

    /**
     * Cek apakah request telah melebihi batas yang ditentukan.
     *
     * @param int $limit
     * @param int $minutes
     *
     * @return bool
     */
    public static function exceeded($limit, $minutes)
    {
        return ! static::check($limit, $minutes);
    }

    /**
     * Kirim response error ke klien.
     *
     * @return \System\Response
     */
    public static function error()
    {
        $headers = Cache::get(static::key());
        $headers = [
            'X-Rate-Limit-Limit' => $headers['limit'],
            'X-Rate-Limit-Remaining' => $headers['remaining'],
            'X-Rate-Limit-Reset' => $headers['reset'],
            'Retry-After' => $headers['retry'],
        ];

        return Response::error($code, $headers);
    }

    /**
     * Ambil cache key untuk request saat ini.
     *
     * @return string
     */
    public static function key()
    {
        return static::PREFIX.'.'.str_replace('.', '-', static::ip());
    }

    /**
     * Ambil ip klien.
     *
     * @return string
     */
    public static function ip()
    {
        $ip = Request::ip();
        return ($ip === '[::1]' || $ip === '127.0.0.1') ? '127.0.0.1' : $ip;
    }
}
