<?php

namespace System;

defined('DS') or exit('No direct access.');

class Curl
{
    /** @var string */
    const GET = 'GET';

    /** @var string */
    const HEAD = 'HEAD';

    /** @var string */
    const POST = 'POST';

    /** @var string */
    const PUT = 'PUT';

    /** @var string */
    const DELETE = 'DELETE';

    /** @var string */
    const CONNECT = 'CONNECT';

    /** @var string */
    const OPTIONS = 'OPTIONS';

    /** @var string */
    const TRACE = 'TRACE';

    /** @var string */
    const BASELINE = 'BASELINE';

    /** @var string */
    const LINK = 'LINK';

    /** @var string */
    const UNLINK = 'UNLINK';

    /** @var string */
    const MERGE = 'MERGE';

    /** @var string */
    const BASELINECONTROL = 'BASELINE-CONTROL';

    /** @var string */
    const MKACTIVITY = 'MKACTIVITY';

    /** @var string */
    const VERSIONCONTROL = 'VERSION-CONTROL';

    /** @var string */
    const REPORT = 'REPORT';

    /** @var string */
    const CHECKOUT = 'CHECKOUT';

    /** @var string */
    const CHECKIN = 'CHECKIN';

    /** @var string */
    const UNCHECKOUT = 'UNCHECKOUT';

    /** @var string */
    const MKWORKSPACE = 'MKWORKSPACE';

    /** @var string */
    const UPDATE = 'UPDATE';

    /** @var string */
    const LABEL = 'LABEL';

    /** @var string */
    const ORDERPATCH = 'ORDERPATCH';

    /** @var string */
    const ACL = 'ACL';

    /** @var string */
    const MKREDIRECTREF = 'MKREDIRECTREF';

    /** @var string */
    const UPDATEREDIRECTREF = 'UPDATEREDIRECTREF';

    /** @var string */
    const MKCALENDAR = 'MKCALENDAR';

    /** @var string */
    const PROPFIND = 'PROPFIND';

    /** @var string */
    const LOCK = 'LOCK';

    /** @var string */
    const UNLOCK = 'UNLOCK';

    /** @var string */
    const PROPPATCH = 'PROPPATCH';

    /** @var string */
    const MKCOL = 'MKCOL';

    /** @var string */
    const COPY = 'COPY';

    /** @var string */
    const MOVE = 'MOVE';

    /** @var string */
    const SEARCH = 'SEARCH';

    /** @var string */
    const PATCH = 'PATCH';

    /** @var string */
    const BIND = 'BIND';

    /** @var string */
    const UNBIND = 'UNBIND';

    /** @var string */
    const REBIND = 'REBIND';

    /**
     * Curl handler.
     *
     * @var \CURLHandle|resource
     */
    private static $handler;

    /**
     * Cookie.
     *
     * @var string
     */
    private static $cookie;

    /**
     * Cookie file.
     *
     * @var string
     */
    private static $cookie_file;

    /**
     * Curl options.
     *
     * @var array
     */
    private static $curl_options = [];

    /**
     * Default headers.
     *
     * @var array
     */
    private static $default_headers = [];

    /**
     * Json options.
     *
     * @var array
     */
    private static $json_options = [];

    /**
     * Socket timeout.
     *
     * @var int
     */
    private static $socket_timeout;

    /**
     * Verify peer.
     *
     * @var int
     */
    private static $verify_peer = 1;

    /**
     * Verify host.
     *
     * @var int
     */
    private static $verify_host = 2;

    /**
     * Auth.
     *
     * @var array
     */
    private static $auth = ['user' => '', 'pass' => '', 'method' => CURLAUTH_BASIC];

    /**
     * Proxy.
     *
     * @var array
     */
    /**
     * The fake responses by URL pattern while faking, NULL when sending for real.
     *
     * @var array|null
     */
    private static $fakes;

    /**
     * The requests sent while faking.
     *
     * @var array
     */
    private static $recorded = [];

    private static $proxy = [
        'port' => false,
        'tunnel' => false,
        'address' => false,
        'type' => CURLPROXY_HTTP,
        'auth' => ['user' => '', 'pass' => '', 'method' => CURLAUTH_BASIC],
    ];

    /**
     * Set json options.
     *
     * @param bool $associative
     * @param int  $depth
     * @param int  $options
     *
     * @return array
     */
    public static function json_options($associative = false, $depth = 512, $options = 0)
    {
        return static::$json_options = [$associative, $depth, $options];
    }

    /**
     * Verify ssl peer.
     *
     * @param bool $enabled
     *
     * @return int
     */
    public static function verify_peer($enabled = true)
    {
        return static::$verify_peer = $enabled ? 1 : 0;
    }

    /**
     * Verify ssl host.
     *
     * @param bool $enabled
     *
     * @return int
     */
    public static function verify_host($enabled = true)
    {
        return static::$verify_host = $enabled ? 2 : 0;
    }

    /**
     * Set request timeout (in seconds).
     *
     * @param int $seconds
     *
     * @return int
     */
    public static function timeout($seconds = 0)
    {
        return static::$socket_timeout = ((int) $seconds < 1) ? PHP_INT_MAX : (int) $seconds;
    }

    /**
     * Set default request headers.
     *
     * @param array $headers
     *
     * @return array
     */
    public static function default_headers(array $headers)
    {
        return static::$default_headers = array_merge(static::$default_headers, $headers);
    }

    /**
     * Set default request header.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return array
     */
    public static function default_header($name, $value)
    {
        return static::$default_headers[$name] = $value;
    }

    /**
     * Remove default request headers.
     *
     * @return array
     */
    public static function clear_default_headers()
    {
        return static::$default_headers = [];
    }

    /**
     * Set curl options.
     *
     * @param array $options
     *
     * @return array
     */
    public static function curl_options(array $options)
    {
        return static::$curl_options = static::merge_options(static::$curl_options, $options);
    }

    /**
     * Set curl option.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return array
     */
    public static function curl_option($name, $value)
    {
        return static::$curl_options[$name] = $value;
    }

    /**
     * Remove curl options.
     *
     * @return array
     */
    public static function clear_curl_options()
    {
        return static::$curl_options = [];
    }

    /**
     * Drop the credentials sent with every request.
     */
    public static function clear_auth()
    {
        static::$auth = ['user' => '', 'pass' => '', 'method' => CURLAUTH_BASIC];
    }

    /**
     * Drop the cookie sent with every request.
     */
    public static function clear_cookie()
    {
        static::$cookie = false;
        static::$cookie_file = false;
    }

    /**
     * Stop routing requests through a proxy.
     */
    public static function clear_proxy()
    {
        static::$proxy = [
            'address' => false,
            'port' => 1080,
            'type' => CURLPROXY_HTTP,
            'tunnel' => false,
            'auth' => ['user' => '', 'pass' => '', 'method' => CURLAUTH_BASIC],
        ];
    }

    /**
     * Get every setting, or put back ones taken earlier. The worker bridge restores
     * the boot-time settings with it, so one request's auth, cookie or proxy is never
     * sent on the next request's calls.
     *
     * @param array|null $state
     *
     * @return array|null
     */
    public static function state($state = null)
    {
        $keys = [
            'cookie', 'cookie_file', 'curl_options', 'default_headers', 'json_options',
            'socket_timeout', 'verify_peer', 'verify_host', 'auth', 'proxy',
        ];

        if (! is_array($state)) {
            $state = [];

            foreach ($keys as $key) {
                $state[$key] = static::$$key;
            }

            return $state;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $state)) {
                static::$$key = $state[$key];
            }
        }
    }

    /**
     * Reset every setting. Call it before switching host, so credentials meant for
     * one endpoint are not sent to another.
     */
    public static function reset()
    {
        static::clear_auth();
        static::clear_cookie();
        static::clear_proxy();
        static::clear_default_headers();
        static::clear_curl_options();

        static::$socket_timeout = null;
    }

    /**
     * Set cookie (string).
     *
     * @param string $cookie
     *
     * @return string
     */
    public static function cookie($cookie)
    {
        static::$cookie = $cookie;
    }

    /**
     * Set cookie (file).
     *
     * @param string $path
     *
     * @return string
     */
    public static function cookie_file($path)
    {
        static::$cookie_file = $path;
    }

    /**
     * Set request authentication.
     *
     * @param string $username
     * @param string $password
     * @param int    $method
     *
     * @return void
     */
    public static function auth($username = '', $password = '', $method = CURLAUTH_BASIC)
    {
        static::$auth['user'] = $username;
        static::$auth['pass'] = $password;
        static::$auth['method'] = $method;
    }

    /**
     * Set proxy.
     *
     * @param string $address
     * @param int    $port
     * @param int    $type
     * @param bool   $tunnel
     *
     * @return void
     */
    public static function proxy($address, $port = 1080, $type = CURLPROXY_HTTP, $tunnel = false)
    {
        static::$proxy['type'] = $type;
        static::$proxy['port'] = $port;
        static::$proxy['tunnel'] = $tunnel;
        static::$proxy['address'] = $address;
    }

    /**
     * Set proxy authentication.
     *
     * @param string $username
     * @param string $password
     * @param int    $method
     *
     * @return void
     */
    public static function proxy_auth($username = '', $password = '', $method = CURLAUTH_BASIC)
    {
        static::$proxy['auth']['user'] = $username;
        static::$proxy['auth']['pass'] = $password;
        static::$proxy['auth']['method'] = $method;
    }

    /**
     * Run a GET request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $parameters
     *
     * @return \stdClass
     */
    public static function get($url, array $headers = [], $parameters = null)
    {
        return static::send(static::GET, $url, $parameters, $headers);
    }

    /**
     * Run a HEAD request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $parameters
     *
     * @return \stdClass
     */
    public static function head($url, array $headers = [], $parameters = null)
    {
        return static::send(static::HEAD, $url, $parameters, $headers);
    }

    /**
     * Run an OPTIONS request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $parameters
     *
     * @return \stdClass
     */
    public static function options($url, array $headers = [], $parameters = null)
    {
        return static::send(static::OPTIONS, $url, $parameters, $headers);
    }

    /**
     * Run a CONNECT request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $parameters
     *
     * @return \stdClass
     */
    public static function connect($url, array $headers = [], $parameters = null)
    {
        return static::send(static::CONNECT, $url, $parameters, $headers);
    }

    /**
     * Run a POST request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $body
     *
     * @return \stdClass
     */
    public static function post($url, array $headers = [], $body = null)
    {
        return static::send(static::POST, $url, $body, $headers);
    }

    /**
     * Run a DELETE request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $body
     *
     * @return \stdClass
     */
    public static function delete($url, array $headers = [], $body = null)
    {
        return static::send(static::DELETE, $url, $body, $headers);
    }

    /**
     * Run a PUT request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $body
     *
     * @return \stdClass
     */
    public static function put($url, array $headers = [], $body = null)
    {
        return static::send(static::PUT, $url, $body, $headers);
    }

    /**
     * Run a PATCH request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $body
     *
     * @return \stdClass
     */
    public static function patch($url, array $headers = [], $body = null)
    {
        return static::send(static::PATCH, $url, $body, $headers);
    }

    /**
     * Run a TRACE request.
     *
     * @param string     $url
     * @param array      $headers
     * @param mixed|null $body
     *
     * @return \stdClass
     */
    public static function trace($url, array $headers = [], $body = null)
    {
        return static::send(static::TRACE, $url, $body, $headers);
    }

    /**
     * Run a curl request.
     *
     * @param string     $method
     * @param string     $url
     * @param mixed|null $body
     * @param array      $headers
     *
     * @return \stdClass
     */
    public static function send($method, $url, $body = null, array $headers = [])
    {
        if (is_array(static::$fakes)) {
            return static::faked($method, $url, $body, $headers);
        }

        static::$handler = curl_init();

        if ($method !== static::GET) {
            if ($method === static::POST) {
                curl_setopt(static::$handler, CURLOPT_POST, true);
            } else {
                if ($method === static::HEAD) {
                    curl_setopt(static::$handler, CURLOPT_NOBODY, true);
                }

                curl_setopt(static::$handler, CURLOPT_CUSTOMREQUEST, $method);
            }

            curl_setopt(static::$handler, CURLOPT_POSTFIELDS, $body);
        } elseif (is_array($body)) {
            $url .= (false !== strpos((string) $url, '?')) ? '&' : '?';
            $url .= http_build_query(static::build_curl_query($body));
        }

        $defaults = [
            CURLOPT_URL => static::encode_url($url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => static::format_headers($headers),
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => (bool) static::$verify_peer,
            CURLOPT_SSL_VERIFYHOST => ((int) static::$verify_host > 0) ? 2 : 0,
            CURLOPT_ENCODING => '',
        ];

        if (defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
            $protocols = CURLPROTO_HTTP | CURLPROTO_HTTPS;

            if (defined('CURLOPT_PROTOCOLS')) {
                $defaults[CURLOPT_PROTOCOLS] = $protocols;
            }

            if (defined('CURLOPT_REDIR_PROTOCOLS')) {
                $defaults[CURLOPT_REDIR_PROTOCOLS] = $protocols;
            }
        }

        $timeout = is_int(static::$socket_timeout) ? static::$socket_timeout : 30;
        curl_setopt_array(static::$handler, static::merge_options($defaults, static::$curl_options));
        curl_setopt(static::$handler, CURLOPT_TIMEOUT, $timeout);

        if (static::$cookie) {
            curl_setopt(static::$handler, CURLOPT_COOKIE, static::$cookie);
        }

        if (static::$cookie_file) {
            curl_setopt(static::$handler, CURLOPT_COOKIEFILE, static::$cookie_file);
            curl_setopt(static::$handler, CURLOPT_COOKIEJAR, static::$cookie_file);
        }

        if (! empty(static::$auth['user'])) {
            curl_setopt_array(static::$handler, [
                CURLOPT_HTTPAUTH => static::$auth['method'],
                CURLOPT_USERPWD => static::$auth['user'].':'.static::$auth['pass'],
            ]);
        }

        if (static::$proxy['address'] !== false) {
            curl_setopt_array(static::$handler, [
                CURLOPT_PROXYTYPE => static::$proxy['type'],
                CURLOPT_PROXY => static::$proxy['address'],
                CURLOPT_PROXYPORT => static::$proxy['port'],
                CURLOPT_HTTPPROXYTUNNEL => static::$proxy['tunnel'],
                CURLOPT_PROXYAUTH => static::$proxy['auth']['method'],
                CURLOPT_PROXYUSERPWD => static::$proxy['auth']['user'].':'.static::$proxy['auth']['pass'],
            ]);
        }

        $started = microtime(true);
        $response = curl_exec(static::$handler);
        $error = curl_error(static::$handler);
        $info = static::info();

        if (class_exists('\System\Foundation\Oops\Debugger', false)
            && ! \System\Foundation\Oops\Debugger::$productionMode) {
            \System\Foundation\Oops\Collectors::trackHttp(
                $method,
                $url,
                isset($info['http_code']) ? $info['http_code'] : 0,
                (microtime(true) - $started) * 1000,
                isset($info['size_download']) ? $info['size_download'] : 0,
                $error ? $error : null,
                $headers,
                $body
            );
        }

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
            curl_close(static::$handler);
        }

        if ($error) {
            throw new \Exception(sprintf('Curl error: %s', $error));
        }

        $raw_headers = substr((string) $response, 0, $info['header_size']);
        $body = substr((string) $response, $info['header_size']);
        $json_options = static::$json_options;

        $response = new \stdClass();
        $response->code = $info['http_code'];
        $response->body = $body;
        $response->raw_body = $body;

        array_unshift($json_options, $body);
        $json = call_user_func_array('json_decode', $json_options);

        if (json_last_error() === JSON_ERROR_NONE) {
            $response->body = $json;
        }

        $key = '';
        $headers = [];
        $items = explode(LF, $raw_headers);

        foreach ($items as $index => $item) {
            $item = explode(':', $item, 2);

            if (isset($item[1])) {
                if (! isset($headers[$item[0]])) {
                    $headers[$item[0]] = trim($item[1]);
                } elseif (is_array($headers[$item[0]])) {
                    $headers[$item[0]] = array_merge($headers[$item[0]], [trim($item[1])]);
                } else {
                    $headers[$item[0]] = array_merge([$headers[$item[0]]], [trim($item[1])]);
                }

                $key = $item[0];
            } else {
                if (substr((string) $item[0], 0, 1) === "\t") {
                    $headers[$key] .= "\r\n\t".trim($item[0]);
                } elseif (! $key) {
                    $headers[0] = trim($item[0]);
                }
            }
        }

        $response->headers = $headers;
        return $response;
    }

    /**
     * Answer requests with fake responses instead of sending them, and record them for
     * the assert_*() methods. Patterns match the URL ('*' is a wildcard, the scheme may
     * be left out); a response is one from Curl::response(), a body (array for JSON), or
     * a closure that takes the request and returns one. Without patterns every request
     * gets an empty 200. A request no pattern matches throws, so a test never reaches
     * the network by mistake. Curl::fake(false) sends for real again.
     *
     *     Curl::fake([
     *         'api.github.com/users/*' => Curl::response(['login' => 'esyede'], 200),
     *         'example.com/*' => function ($request) { return Curl::response('', 404); },
     *     ]);
     *
     * @param array|bool|null $responses
     */
    public static function fake($responses = null)
    {
        static::$recorded = [];
        static::$fakes = (false === $responses) ? null : (is_array($responses) ? $responses : ['*' => static::response()]);
    }

    /**
     * Make a fake response, shaped like a real one.
     *
     * @param mixed $body    a string, or an array / object sent as JSON
     * @param int   $code
     * @param array $headers
     *
     * @return \stdClass
     */
    public static function response($body = '', $code = 200, array $headers = [])
    {
        if (is_array($body) || is_object($body)) {
            $body = json_encode($body);
            $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        }

        $response = new \stdClass();
        $response->code = (int) $code;
        $response->raw_body = (string) $body;
        $response->body = (string) $body;
        $response->headers = $headers;

        $json = json_decode((string) $body);

        if ('' !== (string) $body && JSON_ERROR_NONE === json_last_error()) {
            $response->body = $json;
        }

        return $response;
    }

    /**
     * Get the requests sent while faking, optionally only those a closure accepts.
     * Each is an array: method, url, headers (lower-cased names), body.
     *
     * @param \Closure|null $filter
     *
     * @return array
     */
    public static function recorded($filter = null)
    {
        return ($filter instanceof \Closure) ? array_values(array_filter(static::$recorded, $filter)) : static::$recorded;
    }

    /**
     * Assert that a request the closure accepts was sent.
     *
     * @param \Closure $filter
     */
    public static function assert_sent(\Closure $filter)
    {
        static::check(count(static::recorded($filter)) > 0, 'An expected HTTP request was not sent.');
    }

    /**
     * Assert that no request the closure accepts was sent.
     *
     * @param \Closure $filter
     */
    public static function assert_not_sent(\Closure $filter)
    {
        static::check(0 === count(static::recorded($filter)), 'An unexpected HTTP request was sent.');
    }

    /**
     * Assert how many requests were sent.
     *
     * @param int $count
     */
    public static function assert_sent_count($count)
    {
        static::check(count(static::$recorded) === (int) $count, sprintf(
            'Expected %d HTTP requests, %d were sent.',
            $count,
            count(static::$recorded)
        ));
    }

    /**
     * Assert that no request was sent.
     */
    public static function assert_nothing_sent()
    {
        static::assert_sent_count(0);
    }

    /**
     * Fail a test: through PHPUnit when it runs, else with an exception.
     *
     * @param bool   $condition
     * @param string $message
     */
    private static function check($condition, $message)
    {
        if (class_exists('PHPUnit\Framework\Assert')) {
            \PHPUnit\Framework\Assert::assertTrue((bool) $condition, $message);
        } elseif (class_exists('PHPUnit_Framework_Assert')) {
            \PHPUnit_Framework_Assert::assertTrue((bool) $condition, $message);
        } elseif (! $condition) {
            throw new \Exception($message);
        }
    }

    /**
     * Record a request and answer it from the fakes.
     *
     * @param string $method
     * @param string $url
     * @param mixed  $body
     * @param array  $headers
     *
     * @return \stdClass
     */
    private static function faked($method, $url, $body, array $headers)
    {
        if (static::GET === $method && is_array($body)) {
            $url .= ((false !== strpos((string) $url, '?')) ? '&' : '?').http_build_query(static::build_curl_query($body));
            $body = null;
        }

        $request = [
            'method' => $method,
            'url' => (string) $url,
            'headers' => array_change_key_case(array_merge(static::$default_headers, $headers), CASE_LOWER),
            'body' => $body,
        ];

        static::$recorded[] = $request;

        foreach (static::$fakes as $pattern => $response) {
            if (Str::is(Str::start((string) $pattern, '*'), $request['url'])) {
                $response = ($response instanceof \Closure) ? $response($request) : $response;
                return ($response instanceof \stdClass) ? $response : static::response($response);
            }
        }

        throw new \Exception(sprintf('No fake response for the HTTP request: %s %s', $method, $url));
    }

    /**
     * Prepare a file for use as a request parameter.
     *
     * @param string $path
     * @param string $alias
     *
     * @return \CURLFile|string
     */
    public static function body_file($path, $alias = '')
    {
        if (! is_file($path)) {
            throw new \Exception(sprintf('Target file not found: %s', $path));
        }

        $mime = Storage::mime($path);

        if (class_exists('\CURLFile')) {
            return new \CURLFile($path, $mime, $alias);
        }

        if (function_exists('curl_file_create')) {
            return curl_file_create($path, $mime, $alias);
        }

        return sprintf('@%s;filename=%s;type=%s', $path, $alias ?: basename($path), $mime);
    }

    /**
     * Prepare a JSON string for request body.
     *
     * @param mixed $data
     * @param int   $json_options
     *
     * @return string
     */
    public static function body_json($data, $json_options = 0)
    {
        return json_encode($data, $json_options);
    }

    /**
     * Prepare a form-data string for request body.
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function body_form($data)
    {
        if (is_array($data) || is_object($data) || ($data instanceof \Traversable)) {
            return http_build_query(static::build_curl_query($data));
        }

        return $data;
    }

    /**
     * Prepare a multipart form-data string for request body.
     *
     * @param mixed $data
     * @param array $files
     *
     * @return array
     */
    public static function body_multipart($data, array $files = [])
    {
        if (is_object($data)) {
            return get_object_vars($data);
        }

        $data = is_array($data) ? $data : [$data];

        if (count($files) > 0) {
            foreach ($files as $name => $file) {
                $data[$name] = static::body_file($file);
            }
        }

        return $data;
    }

    /**
     * Build a query string for request body.
     *
     * @param mixed $data
     * @param bool  $parent
     *
     * @return array
     */
    public static function build_curl_query($data, $parent = false)
    {
        $result = [];

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        foreach ($data as $key => $value) {
            $name = $parent ? sprintf('%s[%s]', $parent, $key) : $key;

            if (class_exists('\CURLFile')) {
                if (! ($value instanceof \CURLFile) && (is_array($value) || is_object($value))) {
                    $result = array_merge($result, static::build_curl_query($value, $name));
                } else {
                    $result[$name] = $value;
                }
            } else {
                if (is_array($value) || is_object($value)) {
                    $result = array_merge($result, static::build_curl_query($value, $name));
                } else {
                    $result[$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get information about the last transfer.
     *
     * @return array
     */
    public static function info()
    {
        return curl_getinfo(static::$handler);
    }

    /**
     * Get the internal curl handler.
     *
     * @return \CURLHandle|resource
     */
    public static function handler()
    {
        return static::$handler;
    }

    /**
     * Format the request headers.
     *
     * @param array $headers
     *
     * @return array
     */
    public static function format_headers(array $headers)
    {
        $headers = array_merge(static::$default_headers, (array) $headers);
        $headers = array_change_key_case($headers, CASE_LOWER);
        $formatted = [];

        foreach ($headers as $key => $value) {
            $formatted[] = trim(strtolower((string) $key)).': '.$value;
        }

        if (! array_key_exists('user-agent', $headers)) {
            $formatted[] = 'user-agent: '.static::fake_user_agent();
        }

        if (! array_key_exists('expect', $headers)) {
            $formatted[] = 'expect:';
        }

        return $formatted;
    }

    /**
     * Encode URL.
     *
     * The query is kept as given (duplicate keys, encoded delimiters, order):
     * only characters that are not valid in a URL, and stray '%', get escaped.
     *
     * @param string $url
     *
     * @return string
     */
    private static function encode_url($url)
    {
        $url = parse_url($url);
        $scheme = $url['scheme'].'://';
        $host = (string) $url['host'];
        $user = isset($url['user']) ? (string) $url['user'] : '';
        $user .= isset($url['pass']) ? ':'.$url['pass'] : '';
        $user .= ('' !== $user) ? '@' : '';
        $port = isset($url['port']) ? ':'.ltrim((string) $url['port'], ':') : '';
        $path = isset($url['path']) ? (string) $url['path'] : '';
        $query = isset($url['query']) ? (string) $url['query'] : '';
        $query = ('' !== $query) ? '?'.preg_replace_callback(
            '/[^A-Za-z0-9\-._~!$&\'()*+,;=:@\/?%\[\]]|%(?![0-9A-Fa-f]{2})/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        ) : '';

        return $scheme.$user.$host.$port.$path.$query;
    }

    /**
     * Merge curl options.
     *
     * @param array &$existing
     * @param array $new
     *
     * @return array
     */
    private static function merge_options(array &$existing, array $new)
    {
        return $new + $existing;
    }

    /**
     * Generate a fake user agent string.
     *
     * @return string
     */
    public static function fake_user_agent()
    {
        $agents = [
            'Windows' => '(Windows NT 10.0; Win64; x64; rv:[v].[m]) Gecko/[y]0101 Firefox/[v].[m]',
            'Linux' => '(Linux x86_64; rv:[v].[m]) Gecko/[y]0101 Firefox/[v].[m]',
            'Darwin' => '(Macintosh; Intel Mac OS X 10.15; rv:[v].[m]) Gecko/[y]0101 Firefox/[v].[m]',
            'BSD' => '(X11; FreeBSD amd64; rv:[v].[m]) Gecko/[y]0101 Firefox/[v].[m]',
            'Solaris' => '(Solaris; Solaris x86_64; rv:[v].[m]) Gecko/[y]0101 Firefox/[v].[m]',
        ];

        $platform = system_os();
        $platform = ('Unknown' === $platform) ? 'Linux' : $platform;
        $year = (int) gmdate('Y');
        $version = 103 + (((($year < 2020) ? 2020 : $year) - 2020) * 2);
        $minor = rand(0, 3);

        return 'Mozilla/5.0 '.str_replace(['[v]', '[y]', '[m]'], [(string) $version, (string) $year, (string) $minor], $agents[$platform]);
    }
}
