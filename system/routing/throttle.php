<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Cache;
use System\Hook;
use System\Request;
use System\Response;
use System\URI;

class Throttle
{
    /**
     * Prefix for the rate-limiter cache key.
     */
    const PREFIX = 'throttle';

    /**
     * Contains the named limiters, name => callback.
     *
     * @var array
     */
    protected static $limiters = [];

    /**
     * Define a named limiter, used as 'throttle:<name>'. The callback returns
     * ['max' => 60, 'minutes' => 1, 'by' => <bucket>], or NULL for no limit. Without
     * 'by' every client IP gets its own bucket.
     *
     * @param string   $name
     * @param \Closure $callback
     */
    public static function define($name, \Closure $callback)
    {
        static::$limiters[$name] = $callback;
    }

    /**
     * Run the 'throttle' middleware: 'throttle:60,1' limits per route and client,
     * 'throttle:api' runs the named limiter. NULL when allowed, else the 429 response.
     *
     * @param int|string $limit
     * @param int        $minutes
     *
     * @return \System\Response|null
     */
    public static function attempt($limit, $minutes = 1)
    {
        if (is_numeric($limit)) {
            return static::exceeded($limit, $minutes) ? static::error() : null;
        }

        if (! isset(static::$limiters[$limit])) {
            throw new \Exception(sprintf('Undefined rate limiter: %s', $limit));
        }

        $config = call_user_func(static::$limiters[$limit]);

        if (is_null($config)) {
            return null;
        }

        if (! is_array($config) || ! isset($config['max'])) {
            throw new \Exception(sprintf("Rate limiter '%s' must return NULL or an array with a 'max' key.", $limit));
        }

        $by = (isset($config['by']) && '' !== (string) $config['by']) ? (string) $config['by'] : static::client();
        $key = 'limiter|'.$limit.'|'.$by;
        $minutes = isset($config['minutes']) ? $config['minutes'] : 1;

        return static::exceeded($config['max'], $minutes, $key) ? static::error($key) : null;
    }

    /**
     * Run the throttling process and return whether the request is allowed. With a
     * key ('login|'.$email), that key is counted instead of the route and client.
     *
     * @param int         $max_attempts
     * @param int         $decay_minutes
     * @param string|null $key
     *
     * @return bool
     */
    public static function check($max_attempts, $decay_minutes = 1, $key = null)
    {
        $max_attempts = max(1, (int) $max_attempts);
        $decay_minutes = max(1, (int) $decay_minutes);

        $key = static::bucket($key);
        $meta_key = $key.':meta';
        $meta = Cache::get($meta_key);

        if (! is_array($meta) || ! isset($meta['reset']) || $meta['reset'] <= time()) {
            $hits = 1;

            Cache::put($key, $hits, $decay_minutes);
            Cache::put($meta_key, [
                'limit' => $max_attempts,
                'reset' => time() + ($decay_minutes * 60),
                'ip' => static::client(),
            ], $decay_minutes);
        } else {
            $hits = Cache::increment($key, $decay_minutes);
        }

        return $hits <= $max_attempts;
    }

    /**
     * Get the client IP. The Cloudflare header only counts when the request
     * actually arrives from one of the trusted proxies.
     *
     * @return string
     */
    protected static function client()
    {
        if (Request::foundation()->isFromTrustedProxy()) {
            $forwarded = Request::server('HTTP_CF_CONNECTING_IP');

            if ($forwarded && filter_var($forwarded, FILTER_VALIDATE_IP)) {
                return $forwarded;
            }
        }

        return Request::ip();
    }

    /**
     * Check if the rate limit has been exceeded.
     *
     * @param int         $max_attempts
     * @param int         $decay_minutes
     * @param string|null $key
     *
     * @return bool
     */
    public static function exceeded($max_attempts, $decay_minutes, $key = null)
    {
        return ! static::check($max_attempts, $decay_minutes, $key);
    }

    /**
     * Reset a bucket, e.g. after a successful login.
     *
     * @param string|null $key
     */
    public static function clear($key = null)
    {
        $key = static::bucket($key);

        Cache::forget($key);
        Cache::forget($key.':meta');
    }

    /**
     * Get the cache key of a bucket: the route and client one, or a given key.
     *
     * @param string|null $key
     *
     * @return string
     */
    protected static function bucket($key)
    {
        return is_null($key) ? static::key() : static::PREFIX.'.'.md5(RAKIT_KEY.'|key|'.$key);
    }

    /**
     * Get the cache key for the rate limiter.
     *
     * @return string
     */
    public static function key()
    {
        $path = Request::$route ? Request::$route->uri : trim(URI::current(), '/');
        return static::PREFIX.'.'.md5(RAKIT_KEY.'|'.$path.'|'.static::client());
    }

    /**
     * Return a 429 Too Many Requests response with standard rate-limit headers.
     *
     * @param string|null $key
     *
     * @return \System\Response
     */
    public static function error($key = null)
    {
        $key = static::bucket($key);
        $meta = Cache::get($key.':meta') ?: [];
        $limit = isset($meta['limit']) ? (int) $meta['limit'] : 0;
        $reset = isset($meta['reset']) ? (int) $meta['reset'] : time();

        Hook::fire('rakit.auth: lockout', [$key, $limit, $reset]);

        return Response::error(429, [
            'X-Rate-Limit-Limit' => $limit,
            'X-Rate-Limit-Remaining' => 0,
            'X-Rate-Limit-Reset' => $reset,
            'Retry-After' => max(0, $reset - time()),
        ]);
    }
}
