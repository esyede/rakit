<?php

namespace System;

defined('DS') or exit('No direct access.');

class URL
{
    /**
     * Contains Base URL (from cache).
     *
     * @var string|null
     */
    public static $base;

    /**
     * Get full URI (including query string).
     *
     * @return string
     */
    public static function full()
    {
        return static::to(URI::full());
    }

    /**
     * Get current request URI.
     *
     * @return string
     */
    public static function current()
    {
        return static::to(URI::current(), false, false);
    }

    /**
     * Get the application home URL.
     *
     * @return string
     */
    public static function home()
    {
        return is_null(Routing\Router::find('home')) ? static::to('/') : static::to_route('home');
    }

    /**
     * Get the application base URL.
     *
     * @return string
     */
    public static function base()
    {
        if (! static::$base) {
            $base = Config::get('application.url');
            static::$base = ('' === $base) ? Request::foundation()->getRootUrl() : $base;
        }

        return static::$base;
    }

    /**
     * Create an URL to a location within application scope.
     *
     * @param string $url
     * @param bool   $asset
     * @param bool   $locale
     *
     * @return string
     */
    public static function to($url = '', $asset = false, $locale = true)
    {
        if (is_string($url) && preg_match('/[\r\n]/', $url)) {
            $url = '/';
        }

        if (is_string($url) && preg_match('#^(//|\\\\\\\\)#', trim($url))) {
            $url = '/';
        }

        if (is_string($url) && preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', trim($url))) {
            if (!preg_match('#^https?://#i', trim($url))) {
                if (static::valid($url)) {
                    $url = '/';
                }
            }
        }

        if (static::valid($url) || Str::starts_with($url, '#')) {
            return $url;
        }

        $config = Config::get('application');
        $base = static::base().($asset ? '' : '/'.$config['index']);

        if (! $asset && $locale && count($config['languages']) > 0) {
            if (in_array($config['language'], $config['languages'])) {
                $base = rtrim($base, '/').'/'.$config['language'];
            }
        }

        $base = (Request::secure() || Str::starts_with(Config::get('application.url'), 'https://'))
            ? Str::replace_first('http://', 'https://', $base)
            : Str::replace_first('https://', 'http://', $base);

        return rtrim($base, '/').'/'.ltrim($url, '/');
    }

    /**
     * Create URL to the controller action.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    public static function to_action($action, array $parameters = [])
    {
        $route = Routing\Router::uses($action);
        return is_null($route)
            ? static::convention($action, $parameters)
            : static::explicit($route, $action, $parameters);
    }

    /**
     * Create action URL from a route definition.
     *
     * @param array  $route
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    protected static function explicit($route, $action, array $parameters)
    {
        return static::to(static::transpose(Routing\Router::uri(key($route)), $parameters));
    }

    /**
     * Create action URL based on convention.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    protected static function convention($action, array $parameters)
    {
        list($package, $action) = Package::parse($action);

        $package = Package::get($package);
        $root = isset($package['handles']) ? $package['handles'] : '';
        $parameters = implode('/', $parameters);

        $uri = $root.'/'.str_replace(['.', '@'], '/', $action);
        $uri = static::to(Str::finish($uri, '/').$parameters);

        return trim($uri, '/');
    }

    /**
     * Create URL to the assets folder.
     *
     * @param string $url
     *
     * @return string
     */
    public static function to_asset($url)
    {
        return (static::valid($url) || static::valid('http:'.$url))
            ? $url
            : static::to('assets/'.ltrim($url, '/'), true);
    }

    /**
     * Create URL from named route.
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    public static function to_route($name, array $parameters = [])
    {
        if (is_null($route = Routing\Router::find($name))) {
            throw new \Exception(sprintf('Error creating URL for undefined route: %s', $name));
        }

        return static::to(trim(static::transpose(Routing\Router::uri(key($route)), $parameters), '/'));
    }

    /**
     * Sign a URL, so a link handed out (an email confirmation, an unsubscribe link)
     * can be trusted without a login. With $minutes it expires. Absolute signs the
     * scheme and host too; relative only the path and query.
     *
     * @param string   $url
     * @param int|null $minutes
     * @param bool     $absolute
     *
     * @return string
     */
    public static function signed($url, $minutes = null, $absolute = true)
    {
        $url = static::to($url);
        $base = preg_replace('/[?#].*$/s', '', $url);
        $query = [];

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        unset($query['signature']);

        if (! is_null($minutes)) {
            $query['expires'] = time() + ((int) $minutes * 60);
        }

        $signed = $absolute ? $base : (string) parse_url($base, PHP_URL_PATH);
        $query['signature'] = static::signature(static::canonical($signed, $query));

        return static::canonical($base, $query);
    }

    /**
     * Create a signed URL to a named route.
     *
     * @param string   $name
     * @param array    $parameters
     * @param int|null $minutes
     * @param bool     $absolute
     *
     * @return string
     */
    public static function to_signed_route($name, array $parameters = [], $minutes = null, $absolute = true)
    {
        return static::signed(static::to_route($name, $parameters), $minutes, $absolute);
    }

    /**
     * Check whether the current request carries a valid, unexpired signature.
     *
     * @param bool $absolute
     *
     * @return bool
     */
    public static function has_valid_signature($absolute = true)
    {
        $request = Request::foundation();
        $query = $request->query->all();
        $signature = isset($query['signature']) ? $query['signature'] : null;

        unset($query['signature']);

        if (! is_string($signature) || '' === $signature) {
            return false;
        }

        if (
            isset($query['expires'])
            && (! is_scalar($query['expires']) || ! ctype_digit((string) $query['expires']) || time() > (int) $query['expires'])
        ) {
            return false;
        }

        $base = $request->getBaseUrl().$request->getPathInfo();
        $base = $absolute ? $request->getSchemeAndHttpHost().$base : $base;

        return Crypter::equals(static::signature(static::canonical($base, $query)), $signature);
    }

    /**
     * Build the form of a URL that is signed: the same on both sides, whatever
     * encoding the client used for the query.
     *
     * @param string $base
     * @param array  $query
     *
     * @return string
     */
    protected static function canonical($base, array $query)
    {
        $query = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return ('' === $query) ? $base : $base.'?'.$query;
    }

    /**
     * Compute a URL signature, with a key of its own derived from the application key.
     *
     * @param string $url
     *
     * @return string
     */
    protected static function signature($url)
    {
        return hash_hmac('sha256', $url, hash_hmac('sha256', 'rakit-url-signature-v1', RAKIT_KEY, true));
    }

    /**
     * Create URL for switching language, keeping current page or not.
     *
     * @param string $language
     * @param bool   $reset
     *
     * @return string
     */
    public static function to_language($language, $reset = false)
    {
        $url = $reset ? URL::home() : URL::to(URI::current());
        return in_array($language, Config::get('application.languages'))
            ? str_replace('/'.Config::get('application.language').'/', '/'.$language.'/', $url)
            : $url;
    }

    /**
     * Replace parameters in the given URI.
     *
     * @param string $uri
     * @param array  $parameters
     *
     * @return string
     */
    public static function transpose($uri, array $parameters)
    {
        foreach ($parameters as $parameter) {
            if (! is_null($parameter)) {
                $uri = preg_replace_callback('/\(.+?\)/', function ($match) use ($parameter) {
                    if ($parameter instanceof Database\Facile\Model) {
                        $name = preg_match('/^\(:([a-zA-Z0-9_]+)\??\)$/', ltrim($match[0], '/'), $token) ? $token[1] : null;
                        return (string) Routing\Route::route_key($parameter, $name);
                    }

                    return (string) $parameter;
                }, $uri, 1);
            }
        }

        return trim(preg_replace('/\(.+?\)/', '', $uri), '/');
    }

    /**
     * Check if the given URL is valid.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function valid($url)
    {
        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }
}
