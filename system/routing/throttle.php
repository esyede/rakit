<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Cache;
use System\Request;
use System\Response;

class Throttle
{
    /**
     * Prefix for the rate-limiter cache key.
     */
    const PREFIX = 'throttle';

    /**
     * Run the throttling process.
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
                'reset' => time() + ($decay_minutes * 60),
                'retry' => $decay_minutes * 60,
                'key' => $key,
                'ip' => Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip(),
            ];
            Cache::put($key, $data, $decay_minutes);
        }

        if ($data['remaining'] > 0) {
            $data['remaining'] = $data['remaining'] - 1;
            Cache::put($key, $data, $decay_minutes);
            return true;
        }

        if ($data['reset'] > time()) {
            return false;
        }

        // Reset rate limit after decay period
        $data = [
            'limit' => $max_attempts,
            'remaining' => $max_attempts - 1,
            'reset' => time() + ($decay_minutes * 60),
            'retry' => $decay_minutes * 60,
            'key' => $key,
            'ip' => Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip(),
        ];
        Cache::put($key, $data, $decay_minutes);
        return true;
    }

    /**
     * Check if the rate limit has been exceeded.
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
     * Get the cache key for the rate limiter.
     *
     * @return string
     */
    public static function key()
    {
        return static::PREFIX . '.' . RAKIT_KEY . '.' . md5(Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip());
    }

    /**
     * Send the rate limit exceeded response.
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
