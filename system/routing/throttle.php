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
     * Run the throttling process and return whether the request is allowed.
     *
     * @param int $max_attempts
     * @param int $decay_minutes
     *
     * @return bool
     */
    public static function check($max_attempts, $decay_minutes = 1)
    {
        $max_attempts = max(1, (int) $max_attempts);
        $decay_minutes = max(1, (int) $decay_minutes);
        $key = static::key();
        $meta = $key . ':meta';

        $hits = Cache::increment($key, $decay_minutes);

        if ($hits === 1) {
            Cache::put($meta, [
                'limit' => $max_attempts,
                'reset' => time() + ($decay_minutes * 60),
                'ip' => Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip(),
            ], $decay_minutes);
        }

        return $hits <= $max_attempts;
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
        $path = trim(Request::foundation()->getPathInfo(), '/');
        $ip = Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip();
        return static::PREFIX . '.' . RAKIT_KEY . '.' . md5($path . '|' . $ip);
    }

    /**
     * Return a 429 Too Many Requests response with standard rate-limit headers.
     *
     * @return \System\Response
     */
    public static function error()
    {
        $meta = Cache::get(static::key() . ':meta') ?: [];
        $limit = isset($meta['limit']) ? (int) $meta['limit'] : 0;
        $reset = isset($meta['reset']) ? (int) $meta['reset'] : time();

        return Response::error(429, [
            'X-Rate-Limit-Limit' => $limit,
            'X-Rate-Limit-Remaining' => 0,
            'X-Rate-Limit-Reset' => $reset,
            'Retry-After' => max(0, $reset - time()),
        ]);
    }
}
