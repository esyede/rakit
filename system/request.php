<?php

namespace System;

defined('DS') or exit('No direct access.');

class Request
{
    /**
     * The key name for request spoofing.
     *
     * @var string
     */
    const SPOOFER = '_method';

    /**
     * Contains all route instances for handling requests.
     *
     * @var mixed
     */
    public static $route;

    /**
     * Contains instance of http foundation.
     *
     * @var \System\Foundation\Http\Request
     */
    public static $foundation;

    /**
     * Cache foundation instance for performance.
     *
     * @var \System\Foundation\Http\Request|null
     */
    private static $cached_foundation = null;

    /**
     * List format request.
     *
     * @var array
     */
    public static $formats = [
        'html' => ['text/html', 'application/xhtml+xml'],
        'txt' => ['text/plain'],
        'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        'css' => ['text/css'],
        'json' => ['application/json', 'application/x-json'],
        'jsonld' => ['application/ld+json'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'rdf' => ['application/rdf+xml'],
        'atom' => ['application/atom+xml'],
        'rss' => ['application/rss+xml'],
        'form' => ['application/x-www-form-urlencoded'],
    ];

    /**
     * Get current request URI.
     *
     * @return string
     */
    public static function uri()
    {
        return URI::current();
    }

    /**
     * Get current request method.
     *
     * @return string
     */
    public static function method()
    {
        return strtoupper((string) static::foundation()->getMethod());
    }

    /**
     * Check request method type.
     *
     * @param string $method
     *
     * @return bool
     */
    public static function is_method($method)
    {
        return static::method() === strtoupper((string) $method);
    }

    /**
     * Get current request handler.
     *
     * <code>
     *
     *      // Get current request handler
     *      $accept = Request::header('Accept');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function header($key, $default = null)
    {
        return Arr::get(static::headers(), $key, $default);
    }

    /**
     * Get all HTTP request headers.
     *
     * @return array
     */
    public static function headers()
    {
        $headers = static::foundation()->headers->all();
        $all = [];

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $all[$name] = $value;
            }
        }

        return $all;
    }

    /**
     * Get an item from global $_SERVER array.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return string
     */
    public static function server($key, $default = null)
    {
        return Arr::get(static::servers(), strtoupper((string) $key), $default);
    }

    /**
     * Get all items from global $_SERVER array.
     *
     * @return array
     */
    public static function servers()
    {
        return static::foundation()->server->all();
    }

    /**
     * Check if request method is spoofed with hidden form.
     *
     * @return bool
     */
    public static function spoofed()
    {
        return !is_null(static::foundation()->get(static::SPOOFER));
    }

    /**
     * Get IP address of the request sender.
     *
     * @param mixed $default
     *
     * @return string
     */
    public static function ip($default = '0.0.0.0')
    {
        $address = static::foundation()->getClientIp();
        return (is_null($address) || !filter_var($address, FILTER_VALIDATE_IP)) ? $default : $address;
    }

    /**
     * Get list of acceptable content-types from the current request.
     *
     * @return array
     */
    public static function accept()
    {
        return static::foundation()->getAcceptableContentTypes();
    }

    /**
     * Check if the current request can accept the given content-type.
     *
     * @param string|array $types
     *
     * @return bool
     */
    public static function accepts($types)
    {
        $types = is_array($types) ? $types : func_get_args();
        $accepts = static::accept();

        if (count($accepts) === 0) {
            return true;
        }

        foreach ($accepts as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }

            foreach ($types as $type) {
                if (static::matches_type($accept, $type) || $accept === strtok($type, '/') . '/*') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get preferred content-type from the current request.
     *
     * @param string|array $types
     *
     * @return string|null
     */
    public static function prefers($types)
    {
        $types = is_array($types) ? $types : func_get_args();
        $accepts = static::accept();

        foreach ($accepts as $accept) {
            if (in_array($accept, ['*/*', '*'])) {
                return $types[0];
            }

            foreach ($types as $ctype) {
                $type = isset(static::$formats[$ctype]) ? static::$formats[$ctype] : $ctype;

                if (static::matches_type($type, $accept) || $accept === strtok($type, '/') . '/*') {
                    return $ctype;
                }
            }
        }
    }

    /**
     * Check if the current request can accept HTML.
     *
     * @return bool
     */
    public static function accept_html()
    {
        return static::accepts('text/html');
    }

    /**
     * Check if the current request can accept any content-type.
     *
     * @return bool
     */
    public static function accept_any()
    {
        $accept = static::accept();
        return count($accept) === 0 || (isset($accept[0]) && ($accept[0] === '*/*' || $accept[0] === '*'));
    }

    /**
     * Check if the current content-type matches the given type.
     *
     * @param string $actual
     * @param string $type
     *
     * @return bool
     */
    public static function matches_type($actual, $type)
    {
        if ($actual === $type) {
            return true;
        }

        $split = explode('/', $actual);
        return isset($split[1]) && false !== preg_match(
            '#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote($split[1], '#') . '#',
            $type
        );
    }

    /**
     * Check if the current request has the json content-type.
     *
     * @return bool
     */
    public static function is_json()
    {
        $type = static::header('Content-Type');
        return Str::contains($type ?: '', ['/json', '+json']);
    }

    /**
     * Check if the current request has expected json response.
     *
     * @return bool
     */
    public static function expects_json()
    {
        return (static::ajax() && !static::pjax()) || static::wants_json();
    }

    /**
     * Check if the current request wants json.
     *
     * @return bool
     */
    public static function wants_json()
    {
        $accept = static::accept();
        return isset($accept[0]) && Str::contains($accept[0], ['/json', '+json']);
    }

    /**
     * Get the authorization header.
     *
     * @param mixed $default
     *
     * @return string|null
     */
    public static function authorization()
    {
        return static::header('Authorization');
    }

    /**
     * Get the bearer token header.
     *
     * @param mixed $default
     *
     * @return string|null
     */
    public static function bearer()
    {
        $auth = (string) static::authorization();

        if (0 === stripos($auth, 'Bearer ')) {
            $token = mb_substr($auth, 7, null, '8bit');

            // Validasi token: diisi dan hanya karakter aman
            if (!empty($token) && preg_match('/^[A-Za-z0-9\-_\.\+\/=]+$/', $token)) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Get the request body.
     *
     * @param bool $as_resource
     *
     * @return string|resource|null
     */
    public static function content($as_resource = false)
    {
        return static::foundation()->getContent($as_resource);
    }

    /**
     * Get the language list that can be accepted by the browser client.
     *
     * @return array
     */
    public static function languages()
    {
        return static::foundation()->getLanguages();
    }

    /**
     * Check if the request is coming via HTTPS or not.
     *
     * @return bool
     */
    public static function secure()
    {
        return static::foundation()->isSecure();
    }

    /**
     * Get the user-agent of the sender of the current request.
     *
     * @return string|null
     */
    public static function agent()
    {
        return static::header('User-Agent');
    }

    /**
     * Check if the request has been forged.
     * Forged request is indicated by the absence of a valid CSRF token.
     *
     * @return bool
     */
    public static function forged()
    {
        $token = Session::token();

        if (empty($token)) {
            return true;
        }

        $header = static::header('X-Csrf-Token');
        $header = $header ?: static::header('X-Xsrf-Token');

        if (in_array(static::method(), ['GET', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'])) {
            return false;
        }

        if ($header) {
            return false !== stripos((string) $header, 'nocheck') || !Crypter::equals($token, $header);
        }

        return !Crypter::equals(Input::get(Session::TOKEN), $token);
    }

    /**
     * Check if the request is AJAX.
     *
     * @return bool
     */
    public static function ajax()
    {
        return static::foundation()->isXmlHttpRequest();
    }

    /**
     * Check if the request is PJAX.
     *
     * @return bool
     */
    public static function pjax()
    {
        return (bool) static::header('X-Pjax') === true;
    }

    /**
     * Check if the request is prefetch.
     *
     * @return bool
     */
    public static function prefetch()
    {
        return strcasecmp(static::server('HTTP_X_MOZ'), 'prefetch') === 0
            || strcasecmp(static::header('Purpose'), 'prefetch') === 0;
    }

    /**
     * Get the HTTP Referrer.
     *
     * @return string
     */
    public static function referrer()
    {
        return static::header('Referer');
    }

    /**
     * Get the timestamp when the request started.
     *
     * @return int
     */
    public static function time()
    {
        return (int) RAKIT_START;
    }

    /**
     * Check if the request is from the console.
     *
     * @return bool
     */
    public static function cli()
    {
        return defined('STDIN')
            || 'cli' === php_sapi_name()
            || ('cgi' === substr((string) PHP_SAPI, 0, 3) && is_callable('getenv') && getenv('TERM'));
    }

    /**
     * Get the environment of the request.
     *
     * @return string|null
     */
    public static function env()
    {
        return static::foundation()->server->get('RAKIT_ENV');
    }

    /**
     * Set the environment of the request.
     *
     * @param string $env
     *
     * @return void
     */
    public static function set_env($env)
    {
        static::foundation()->server->set('RAKIT_ENV', $env);
    }

    /**
     * Check environment of the request.
     *
     * @param string $env
     *
     * @return bool
     */
    public static function is_env($env)
    {
        return static::env() === $env;
    }

    /**
     * Detect environment of the request based on configuration in paths.php.
     *
     * @param array  $environments
     * @param string $uri
     *
     * @return string|null
     */
    public static function detect_env(array $environments, $uri)
    {
        foreach ($environments as $environment => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::is($pattern, $uri) || $pattern === gethostname()) {
                    return $environment;
                }
            }
        }
    }

    /**
     * Get the route handler of the request.
     *
     * @return Route
     */
    public static function route()
    {
        return static::$route;
    }

    /**
     * Get the instance of http request foundation.
     *
     * @return \System\Foundation\Http\Request
     */
    public static function foundation()
    {
        if (static::$cached_foundation !== null) {
            return static::$cached_foundation;
        }

        return static::$cached_foundation = static::$foundation;
    }

    /**
     * Reset the cached foundation (for testing).
     *
     * @return void
     */
    public static function reset_foundation()
    {
        static::$cached_foundation = null;
    }

    /**
     * Get the subdomain of the request.
     *
     * @return string|null
     */
    public static function subdomain()
    {
        $host = static::foundation()->getHost();
        $parts = explode('.', $host);
        return (count($parts) > 2) ? $parts[0] : null;
    }

    /**
     * Handle static method calls on the request foundation.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::foundation(), $method], $parameters);
    }
}
