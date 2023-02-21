<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Curl
{
    /**
     * HTTP method registry.
     * Lihat: https://www.iana.org/assignments/http-methods/http-methods.xhtml.
     */
    const GET = 'GET';
    const HEAD = 'HEAD';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const CONNECT = 'CONNECT';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';
    const BASELINE = 'BASELINE';
    const LINK = 'LINK';
    const UNLINK = 'UNLINK';
    const MERGE = 'MERGE';
    const BASELINECONTROL = 'BASELINE-CONTROL';
    const MKACTIVITY = 'MKACTIVITY';
    const VERSIONCONTROL = 'VERSION-CONTROL';
    const REPORT = 'REPORT';
    const CHECKOUT = 'CHECKOUT';
    const CHECKIN = 'CHECKIN';
    const UNCHECKOUT = 'UNCHECKOUT';
    const MKWORKSPACE = 'MKWORKSPACE';
    const UPDATE = 'UPDATE';
    const LABEL = 'LABEL';
    const ORDERPATCH = 'ORDERPATCH';
    const ACL = 'ACL';
    const MKREDIRECTREF = 'MKREDIRECTREF';
    const UPDATEREDIRECTREF = 'UPDATEREDIRECTREF';
    const MKCALENDAR = 'MKCALENDAR';
    const PROPFIND = 'PROPFIND';
    const LOCK = 'LOCK';
    const UNLOCK = 'UNLOCK';
    const PROPPATCH = 'PROPPATCH';
    const MKCOL = 'MKCOL';
    const COPY = 'COPY';
    const MOVE = 'MOVE';
    const SEARCH = 'SEARCH';
    const PATCH = 'PATCH';
    const BIND = 'BIND';
    const UNBIND = 'UNBIND';
    const REBIND = 'REBIND';

    private static $handler;
    private static $cookie;
    private static $cookie_file;
    private static $curl_options = [];
    private static $default_headers = [];
    private static $json_options = [];
    private static $socket_timeout;
    private static $verify_peer = 0;
    private static $verify_host = 0;
    private static $auth = [
        'user' => '',
        'pass' => '',
        'method' => CURLAUTH_BASIC,
    ];

    private static $proxy = [
        'port' => false,
        'tunnel' => false,
        'address' => false,
        'type' => CURLPROXY_HTTP,
        'auth' => [
            'user' => '',
            'pass' => '',
            'method' => CURLAUTH_BASIC,
        ],
    ];

    /**
     * Set mode json decode.
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
     * Verifikasi ssl peer.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public static function verify_peer($enabled = true)
    {
        return static::$verify_peer = $enabled ? 1 : 0;
    }

    /**
     * Verifikasi ssl host.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public static function verify_host($enabled = true)
    {
        return static::$verify_host = $enabled ? 2 : 0;
    }

    /**
     * Set request timeout (dalam detik).
     *
     * @param int|null $seconds
     *
     * @return int
     */
    public static function timeout($seconds = null)
    {
        $seconds = (null === $seconds || $seconds < 1) ? PHP_INT_MAX : (int) $seconds;
        return static::$socket_timeout = $seconds;
    }

    /**
     * Set default request header (batch).
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
     * Hapus default request headers.
     *
     * @return array
     */
    public static function clear_default_headers()
    {
        return static::$default_headers = [];
    }

    /**
     * Set curl option (batch).
     *
     * @param array $options
     *
     * @return array
     */
    public static function curl_options(array $options)
    {
        return static::merge_options(static::$curl_options, $options);
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
     * Hapus curl options.
     *
     * @return array
     */
    public static function clear_curl_options()
    {
        return static::$curl_options = [];
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
     * Set metode otentikasi request.
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
     * Set mode otentikasi proxy.
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
     * Jalankan GET request.
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
     * Jalankan HEAD request.
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
     * Jalankan OPTIONS request.
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
     * Jalankan CONNECT request.
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
     * Jalankan POST request.
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
     * Jalankan DELETE request.
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
     * Jalankan PUT request.
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
     * Jalankan PATCH request.
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
     * Jalankan TRACE request.
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
     * Jalankan curl request.
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
            $url .= urldecode(http_build_query(static::build_curl_query($body)));
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

        $timeout = is_int(static::$socket_timeout) ? static::$socket_timeout : PHP_INT_MAX;
        curl_setopt_array(static::$handler, static::merge_options($defaults, static::$curl_options));
        curl_setopt(static::$handler, CURLOPT_TIMEOUT, $timeout);

        if (static::$cookie) {
            curl_setopt(static::$handler, CURLOPT_COOKIE, static::$cookie);
        }

        if (static::$cookie_file) {
            curl_setopt(static::$handler, CURLOPT_COOKIEFILE, static::$cookie_file);
            curl_setopt(static::$handler, CURLOPT_COOKIEJAR, static::$cookie_file);
        }

        if (!empty(static::$auth['user'])) {
            curl_setopt_array(static::$handler, [
                CURLOPT_HTTPAUTH => static::$auth['method'],
                CURLOPT_USERPWD => static::$auth['user'] . ':' . static::$auth['pass'],
            ]);
        }

        if (static::$proxy['address'] !== false) {
            $user_pass = static::$proxy['auth']['user'] . ':' . static::$proxy['auth']['pass'];
            curl_setopt_array(static::$handler, [
                CURLOPT_PROXYTYPE => static::$proxy['type'],
                CURLOPT_PROXY => static::$proxy['address'],
                CURLOPT_PROXYPORT => static::$proxy['port'],
                CURLOPT_HTTPPROXYTUNNEL => static::$proxy['tunnel'],
                CURLOPT_PROXYAUTH => static::$proxy['auth']['method'],
                CURLOPT_PROXYUSERPWD => $user_pass,
            ]);
        }

        $response = curl_exec(static::$handler);
        $error = curl_error(static::$handler);
        $info = static::info();

        if ($error) {
            throw new \Exception($error);
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
        $items = explode("\n", $raw_headers);

        foreach ($items as $index => $item) {
            $item = explode(':', $item, 2);

            if (isset($item[1])) {
                if (!isset($headers[$item[0]])) {
                    $headers[$item[0]] = trim($item[1]);
                } elseif (is_array($headers[$item[0]])) {
                    $headers[$item[0]] = array_merge($headers[$item[0]], [trim($item[1])]);
                } else {
                    $headers[$item[0]] = array_merge([$headers[$item[0]]], [trim($item[1])]);
                }

                $key = $item[0];
            } else {
                if (substr((string) $item[0], 0, 1) === "\t") {
                    $headers[$key] .= "\r\n\t" . trim($item[0]);
                } elseif (!$key) {
                    $headers[0] = trim($item[0]);
                }
            }
        }

        $response->headers = $headers;
        return $response;
    }

    /**
     * Siapkan file untuk request body.
     * Untuk digunakan di dalam deklarasi parameter request.
     *
     * @param string $path
     * @param string $alias
     *
     * @return string
     */
    public static function body_file($path, $alias = '')
    {
        if (!is_file($path)) {
            throw new \Exception(sprintf('Target file not found: %s', $path));
        }

        $mime = Storage::mime($path);

        if (class_exists('\CURLFile')) {
            return new \CURLFile($path, $mime, $alias);
        }

        if (function_exists('curl_file_create')) {
            return curl_file_create($path, $mime, $alias);
        }

        return sprintf('@%s;filename=%s;type=%s', $path, $alias ? $alias : basename($path), $mime);
    }

    /**
     * Ubah deklarasi parameter menjadi string json.
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
     * Ubah deklarasi parameter menjadi string form-data.
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
     * Ubah deklarasi parameter menjadi string multipart form-data.
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
     * Format query untuk request.
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
                if (!($value instanceof \CURLFile) && (is_array($value) || is_object($value))) {
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
     * Ambil informasi transfer curl.
     *
     * @return array
     */
    public static function info()
    {
        return curl_getinfo(static::$handler);
    }

    /**
     * Mereturn curl handler internal.
     *
     * @return \CURLHandle|resource
     */
    public static function handler()
    {
        return static::$handler;
    }

    /**
     * Format request headers.
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
            $formatted[] = trim(strtolower((string) $key)) . ': ' . $value;
        }

        if (!array_key_exists('user-agent', $headers)) {
            $formatted[] = 'user-agent: ' . static::fake_user_agent();
        }

        if (!array_key_exists('expect', $headers)) {
            $formatted[] = 'expect:';
        }

        return $formatted;
    }

    /**
     * Format curl query.
     *
     * @param string $query
     *
     * @return array
     */
    private static function format_query($query)
    {
        $query = preg_replace_callback('/(?:^|(?<=&))[^=[]+/', function ($match) {
            return bin2hex(urldecode($match[0]));
        }, $query);

        parse_str($query, $values);
        return array_combine(array_map('hex2bin', array_keys($values)), $values);
    }

    /**
     * Encode URL.
     *
     * @param string $url
     *
     * @return string
     */
    private static function encode_url($url)
    {
        $url = parse_url($url);
        $scheme = $url['scheme'] . '://';
        $host = (string) $url['host'];
        $port = isset($url['port']) ? ':' . ltrim((string) $url['port'], ':') : null;
        $path = isset($url['path']) ? (string) $url['path'] : null;
        $query = isset($url['query']) ? (string) $url['query'] : null;
        $query = $query ? '?' . http_build_query(static::format_query($query)) : '';

        return $scheme . $host . $port . $path . $query;
    }

    /**
     * Merge curl options.
     *
     * @param array &$existsing
     * @param array $new
     *
     * @return array
     */
    private static function merge_options(array &$existsing, array $new)
    {
        return $new + $existsing;
    }

    /**
     * Buat user-aget palsu.
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

        $agents = str_replace(['[v]', '[y]', '[m]'], [$version, $year, $minor], $agents[$platform]);
        return 'Mozilla/5.0 ' . $agents;
    }
}
