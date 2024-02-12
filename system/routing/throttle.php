<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

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
     * @param int $max_attempts
     * @param int $decay_minutes
     *
     * @return \System\Response
     */
    public static function check($max_attempts, $decay_minutes = 1)
    {
        $max_attempts = (int) $max_attempts;
        $max_attempts = ($max_attempts < 1) ? 1 : $max_attempts;
        $decay_minutes = (int) $decay_minutes;
        $decay_minutes = ($decay_minutes < 1) ? 1 : $decay_minutes;
        $key = static::key();
        $data = Cache::get($key);

        if (!$data) {
            $data = [
                'limit' => $max_attempts,
                'remaining' => $max_attempts,
                'reset' => time() + ($decay_minutes * 3600),
                'retry' => $decay_minutes * 3600,
                'key' => $key,
                'ip' => Request::ip(),
            ];
            Cache::put($key, $data, $decay_minutes);
        }

        if ($data['remaining'] > 0) {
            $data['remaining'] = $data['remaining'] - 1;
            Cache::put($key, $data, $decay_minutes);
            return true;
        }

        if ($data['reset'] > time()) {
            $data['reset'] = time() + ($decay_minutes * 3600);
            return false;
        }

        Cache::forget($key);
        return false;
    }

    /**
     * Cek apakah request telah melebihi batas yang ditentukan.
     *
     * @param int $max_attempts
     * @param int $decay_minutes
     *
     * @return bool
     */
    public static function exceeded($max_attempts, $decay_minutes)
    {
        return !static::check($max_attempts, $decay_minutes);
    }

    /**
     * Ambil cache key untuk throtler.
     *
     * @return string
     */
    public static function key()
    {
        return static::PREFIX . '.' . md5(Request::ip());
    }

    /**
     * Kirim response error ke klien.
     *
     * @return \System\Response
     */
    public static function error()
    {
        $data = Cache::get(static::key());
        return Response::error(429, [
            'X-Rate-Limit-Limit' => $data['limit'],
            'X-Rate-Limit-Remaining' => $data['remaining'],
            'X-Rate-Limit-Reset' => $data['reset'],
            'Retry-After' => $data['retry'],
        ]);
    }
}
