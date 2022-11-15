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
        $limit = ($limit < 3) ? 3 : $limit;

        $minutes = (int) $minutes;
        $minutes = ($minutes < 1) ? 1 : $minutes;

        $ip = Request::ip();
        $key = static::PREFIX.'.'.sprintf('%u', ip2long($ip));

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
            'x-rate-limit-limit' => $headers['limit'],
            'x-rate-limit-remaining' => $headers['remaining'],
            'x-rate-limit-reset' => $headers['reset'],
            'retry-after' => $headers['retry'],
        ];

        return Response::error(429, $headers);
    }
}
