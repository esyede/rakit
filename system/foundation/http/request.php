<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct access.');

class Request
{
    /** @var string */
    const HEADER_CLIENT_IP = 'client_ip';

    /** @var string */
    const HEADER_CLIENT_HOST = 'client_host';

    /** @var string */
    const HEADER_CLIENT_PROTO = 'client_proto';

    /** @var string */
    const HEADER_CLIENT_PORT = 'client_port';

    /**
     * Request attributes.
     *
     * @var Parameter
     */
    public $attributes;

    /**
     * Request input.
     *
     * @var Parameter
     */
    public $request;

    /**
     * Request query parameters.
     *
     * @var Parameter
     */
    public $query;

    /**
     * Request server parameters.
     *
     * @var Server
     */
    public $server;

    /**
     * Request files.
     *
     * @var File
     */
    public $files;

    /**
     * Request cookies.
     *
     * @var object|array
     */
    public $cookies;

    /**
     * Request headers.
     *
     * @var Header
     */
    public $headers;

    /**
     * Request content.
     *
     * @var string|false
     */
    protected $content;

    /**
     * Request languages.
     *
     * @var array|null
     */
    protected $languages;

    /**
     * Request charsets.
     *
     * @var array|null
     */
    protected $charsets;

    /**
     * Request acceptable content types.
     *
     * @var array|null
     */
    protected $acceptableContentTypes;

    /**
     * Request path info.
     *
     * @var string|null
     */
    protected $pathInfo;

    /**
     * Request URI.
     *
     * @var string|null
     */
    protected $requestUri;

    /**
     * Request base URL.
     *
     * @var string|null
     */
    protected $baseUrl;

    /**
     * Request base path.
     *
     * @var string|null
     */
    protected $basePath;

    /**
     * Request method.
     *
     * @var string|null
     */
    protected $method;

    /**
     * Request format.
     *
     * @var string|null
     */
    protected $format;

    /**
     * Request session.
     *
     * @var object
     */
    protected $session;

    /**
     * Request locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Request default locale.
     *
     * @var string
     */
    protected $defaultLocale = 'id';

    /**
     * Request formats.
     *
     * @var array
     */
    protected static $formats;

    /**
     * Trusted proxy.
     *
     * @var bool
     */
    protected static $trustProxy = false;

    /**
     * Trusted proxies.
     *
     * @var array
     */
    protected static $trustedProxies = [];

    /**
     * Trusted hosts.
     *
     * @var array
     */
    protected static $trustedHosts = [];

    /**
     * Trusted headers.
     *
     * @var array
     */
    protected static $trustedHeaders = [
        self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
        self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
    ];

    /**
     * Constructor.
     *
     * @param array  $query
     * @param array  $request
     * @param array  $attributes
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param string $content
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * Set the request parameters, re-initializing the request.
     *
     * @param array  $query
     * @param array  $request
     * @param array  $attributes
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param string $content
     */
    public function initialize(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        $this->request = new Parameter($request);
        $this->query = new Parameter($query);
        $this->attributes = new Parameter($attributes);
        $this->cookies = new Parameter($cookies);
        $this->files = new File($files);
        $this->server = new Server($server);
        $this->headers = new Header($this->server->getHeaders());

        $this->content = $content;
        $this->languages = null;
        $this->charsets = null;
        $this->acceptableContentTypes = null;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;
    }

    /**
     * Build a request from the PHP globals, reading php://input unless a body is given.
     *
     * @param string|null $content
     *
     * @return Request
     */
    public static function createFromGlobals($content = null)
    {
        $request = new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER, $content);

        $type = (string) $request->server->get('CONTENT_TYPE');
        $httpType = (string) $request->server->get('HTTP_CONTENT_TYPE');
        $method = (string) $request->server->get('REQUEST_METHOD', 'GET');

        if (
            (0 === strpos($type, 'application/x-www-form-urlencoded') || (0 === strpos($httpType, 'application/x-www-form-urlencoded')))
            && in_array(strtoupper($method), ['PUT', 'DELETE', 'PATCH'])
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new Parameter($data);
        }

        return $request;
    }

    /**
     * Make a request object from the given parameters.
     *
     * @param string $uri
     * @param string $method
     * @param array  $parameters
     * @param array  $cookies
     * @param array  $files
     * @param array  $server
     * @param string $content
     *
     * @return static
     */
    public static function create(
        $uri,
        $method = 'GET',
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        $defaults = [
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'rakit/'.RAKIT_VERSION,
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ];

        $components = parse_url(trim($uri));

        if (isset($components['host'])) {
            $defaults['SERVER_NAME'] = $components['host'];
            $defaults['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme']) && 'https' === $components['scheme']) {
            $defaults['HTTPS'] = 'on';
            $defaults['SERVER_PORT'] = 443;
        }

        if (isset($components['port'])) {
            $defaults['SERVER_PORT'] = $components['port'];
            $defaults['HTTP_HOST'] = $defaults['HTTP_HOST'].':'.$components['port'];
        }

        if (isset($components['user'])) {
            $defaults['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $defaults['PHP_AUTH_PW'] = $components['pass'];
        }

        if (! isset($components['path'])) {
            $components['path'] = '/';
        }

        $method = strtoupper((string) $method);

        switch ($method) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $defaults['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                // No break, intended to fall through

            case 'PATCH':
                $request = $parameters;
                $query = [];
                break;

            default:
                $request = [];
                $query = $parameters;
                break;
        }

        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $string);
            $query = array_replace($string, $query);
        }

        $qs = http_build_query($query, '', '&');
        $uri = $components['path'].('' !== $qs ? '?'.$qs : '');
        $server = array_replace($defaults, $server, [
            'REQUEST_METHOD' => $method,
            'PATH_INFO' => '',
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => $qs,
        ]);
        return new static($query, $request, [], $cookies, $files, $server, $content);
    }

    /**
     * Clone the current request object with modified parameters.
     *
     * @param array|null $query
     * @param array|null $request
     * @param array|null $attributes
     * @param array|null $cookies
     * @param array|null $files
     * @param array|null $server
     *
     * @return static
     */
    public function duplicate(
        $query = null,
        $request = null,
        $attributes = null,
        $cookies = null,
        $files = null,
        $server = null
    ) {
        $clone = clone $this;

        if (null !== $query) {
            $clone->query = new Parameter($query);
        }

        if (null !== $request) {
            $clone->request = new Parameter($request);
        }

        if (null !== $attributes) {
            $clone->attributes = new Parameter($attributes);
        }

        if (null !== $cookies) {
            $clone->cookies = new Parameter($cookies);
        }

        if (null !== $files) {
            $clone->files = new File($files);
        }

        if (null !== $server) {
            $clone->server = new Server($server);
            $clone->headers = new Header($clone->server->getHeaders());
        }

        $clone->languages = null;
        $clone->charsets = null;
        $clone->acceptableContentTypes = null;
        $clone->pathInfo = null;
        $clone->requestUri = null;
        $clone->baseUrl = null;
        $clone->basePath = null;
        $clone->method = null;
        $clone->format = null;

        return $clone;
    }

    /**
     * Clone current object request (session will not be cloned).
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->attributes = clone $this->attributes;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }

    /**
     * Convert request to string.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s %s %s',
            $this->getMethod(),
            $this->getRequestUri(),
            $this->server->get('SERVER_PROTOCOL')
        )."\r\n".$this->headers."\r\n".$this->getContent();
    }

    /**
     * Write the request back into $_GET, $_POST, $_REQUEST, $_SERVER and $_COOKIE.
     * $_FILES is left alone.
     */
    public function overrideGlobals()
    {
        $_GET = $this->query->all();
        $_POST = $this->request->all();
        $_SERVER = $this->server->all();
        $_COOKIE = $this->cookies->all();
        $headers = $this->headers->all();

        foreach ($headers as $key => $value) {
            $key = strtoupper(str_replace('-', '_', (string) $key));

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $_SERVER[$key] = implode(', ', $value);
            } else {
                $_SERVER['HTTP_'.$key] = implode(', ', $value);
            }
        }

        $request = ['g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE];

        $orderings = ini_get('request_order');
        $orderings = (string) ($orderings ?: ini_get('variable_order'));
        $orderings = preg_replace('/[^cgp]/', '', strtolower($orderings));
        $orderings = $orderings ?: 'gp';

        $_REQUEST = [];
        $orders = str_split($orderings);

        foreach ($orders as $order) {
            $_REQUEST = array_merge($_REQUEST, $request[$order]);
        }
    }

    /**
     * Set list of trusted proxies.
     *
     * @param array $proxies
     */
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
        self::$trustProxy = count($proxies) > 0;
    }

    /**
     * Set the host names this application answers to. The client writes the Host
     * header, so anything else is refused once this list is filled in; an empty list
     * accepts anything. A name may start with '*.' to cover its subdomains.
     *
     * @param array $hosts
     */
    public static function setTrustedHosts(array $hosts)
    {
        self::$trustedHosts = [];

        foreach ($hosts as $host) {
            $host = strtolower(trim((string) $host));

            if ('' !== $host) {
                self::$trustedHosts[] = $host;
            }
        }
    }

    /**
     * Check a host name against the list of the ones this application answers to.
     *
     * @param string $host
     *
     * @return bool
     */
    protected static function trustedHost($host)
    {
        if (empty(self::$trustedHosts)) {
            return true;
        }

        foreach (self::$trustedHosts as $trusted) {
            if ($host === $trusted) {
                return true;
            }

            if (0 === strpos($trusted, '*.')) {
                $suffix = substr($trusted, 1);

                if ($host === substr($suffix, 1)
                    || $suffix === substr($host, -mb_strlen($suffix, '8bit'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Set a trusted header name, from the list below. An empty value disables it.
     *
     * @param string $key
     * @param string $value
     */
    public static function setTrustedHeaderName($key, $value)
    {
        if (! array_key_exists($key, self::$trustedHeaders)) {
            throw new \Exception(sprintf("Unable to set the trusted header name for key '.%s'", $key));
        }

        self::$trustedHeaders[$key] = $value;
    }

    /**
     * Check if proxy is trusted.
     *
     * @return bool
     */
    public static function isProxyTrusted()
    {
        return self::$trustProxy;
    }

    /**
     * Normalize a query string: sorted, without stray delimiters, consistently escaped.
     *
     * @param string $queryString
     *
     * @return string
     */
    public static function normalizeQueryString($queryString)
    {
        if (is_null($queryString)) {
            return '';
        }

        $parts = [];
        $order = [];
        $params = explode('&', $queryString);

        foreach ($params as $param) {
            if ('' === $param || '=' === $param[0]) {
                continue;
            }

            $keyValuePair = explode('=', $param, 2);
            $parts[] = isset($keyValuePair[1])
                ? rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1]))
                : rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }

        array_multisort($order, SORT_ASC, $parts);
        return implode('&', $parts);
    }

    /**
     * Get a parameter from any bag, searching GET, then PATH, then POST.
     * Convenient but slow, so avoid it in a controller.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $deep
     *
     * @return mixed
     */
    public function get($key, $default = null, $deep = false)
    {
        $result = $this->request->get($key, $default, $deep);
        $result = $this->attributes->get($key, $result, $deep);
        $result = $this->query->get($key, $result, $deep);
        return $result;
    }

    /**
     * Get the session object.
     *
     * @return object|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Check if the request contains active session from previous request.
     *
     * @return bool
     */
    public function hasPreviousSession()
    {
        return $this->hasSession() && $this->cookies->has($this->session->getName());
    }

    /**
     * Check if the request contains a session object.
     *
     * @return bool
     */
    public function hasSession()
    {
        return null !== $this->session;
    }

    /**
     * Set the session object.
     *
     * @param object $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    public function getClientIp()
    {
        $ip = $this->server->get('REMOTE_ADDR');

        if (! self::$trustProxy) {
            return $ip;
        }

        if (
            ! self::$trustedHeaders[self::HEADER_CLIENT_IP]
            || ! $this->headers->has(self::$trustedHeaders[self::HEADER_CLIENT_IP])
        ) {
            return $ip;
        }

        $clientIps = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_IP]);
        $clientIps = array_map('trim', explode(',', $clientIps));
        $clientIps[] = $ip;
        $trustedProxies = (self::$trustProxy && ! self::$trustedProxies) ? [$ip] : self::$trustedProxies;
        $clientIps = array_diff($clientIps, $trustedProxies);
        return array_pop($clientIps);
    }

    /**
     * Get the script name.
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    /**
     * Get the part of the URI after the base URL, still encoded and without the query
     * string. Answers '/' when there is nothing after it.
     *
     * @return string
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
            $this->pathInfo = $this->pathInfo ?: '/';
        }

        return $this->pathInfo;
    }

    /**
     * Get the directory the front controller lives in, still encoded and without a
     * trailing slash. Empty when it sits at the document root.
     *
     * @return string
     */
    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath();
        }

        return $this->basePath;
    }

    /**
     * Get the base URL (without trailng slash suffix).
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }

        return $this->baseUrl;
    }

    /**
     * Get the http scheme of the request.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Get the port number of the request.
     *
     * @return string
     */
    public function getPort()
    {
        $clientPort = self::$trustedHeaders[self::HEADER_CLIENT_PORT];

        if (self::$trustProxy && $clientPort && $port = $this->headers->get($clientPort)) {
            return $port;
        }

        return $this->server->get('SERVER_PORT');
    }

    /**
     * Get the user on Basic Auth.
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->server->get('PHP_AUTH_USER');
    }

    /**
     * Get the password on Basic Auth.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->server->get('PHP_AUTH_PW');
    }

    /**
     * Get the Basic Auth user and password (in 'user:pass' format).
     *
     * @return string
     */
    public function getUserInfo()
    {
        $pass = $this->getPassword();
        return $this->getUser().((null === $pass || '' === $pass) ? '' : ':'.$pass);
    }

    /**
     * Get the HTTP host (with port if not usinng the standard port).
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        return $this->getHost()
            . ((
                ('http' === $scheme && 80 === (int) $port)
                || ('https' === $scheme && 443 === (int) $port)
            ) ? '' : ':'.$port);
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * Get the scheme and HTTP host.
     *
     * @return string
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * Get the full URI for the request.
     *
     * @return string
     */
    public function getUri()
    {
        $query = $this->getQueryString();
        return $this->getSchemeAndHttpHost()
            . $this->getBaseUrl()
            . $this->getPathInfo()
            . ((null !== $query) ? '?'.$query : '');
    }

    /**
     * Get the full URI for the given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getUriForPath($path)
    {
        return $this->getSchemeAndHttpHost().$this->getBaseUrl().$path;
    }

    /**
     * Get the query string.
     *
     * @return string|null
     */
    public function getQueryString()
    {
        $query = static::normalizeQueryString($this->server->get('QUERY_STRING'));
        return ('' === $query) ? null : $query;
    }

    /**
     * Checj if the request is secure (https).
     *
     * @return bool
     */
    public function isSecure()
    {
        $clientProto = self::$trustedHeaders[self::HEADER_CLIENT_PROTO];

        if (self::$trustProxy && $clientProto && $proto = $this->headers->get($clientProto)) {
            return in_array(strtolower((string) $proto), ['https', 'on', 'ssl', '1']);
        }

        $https = (string) $this->server->get('HTTPS');
        return 'on' === $https || '1' === $https || ($https && 'off' !== strtolower($https));
    }

    /**
     * Get the host name.
     *
     * @return string
     */
    public function getHost()
    {
        $clientHost = self::$trustedHeaders[self::HEADER_CLIENT_HOST];

        if (self::$trustProxy && $clientHost && $host = $this->headers->get($clientHost)) {
            $elements = explode(',', $host);
            $host = $elements[count($elements) - 1];
        } elseif (! $host = $this->headers->get('Host')) {
            if (! $host = $this->server->get('SERVER_NAME')) {
                $host = $this->server->get('SERVER_ADDR', '');
            }
        }

        $host = strtolower(preg_replace('/:\d+$/', '', trim((string) $host)));

        if ($host && ! preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host)) {
            throw new \UnexpectedValueException(sprintf('Invalid host: %s', $host));
        }

        if ($host && ! static::trustedHost($host)) {
            throw new \UnexpectedValueException(sprintf('Untrusted host: %s', $host));
        }

        return $host;
    }

    /**
     * Set the request method.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }

    /**
     * Get the method the server reported, ignoring '_method' and 'X-Http-Method-Override'.
     *
     * @return string
     */
    public function getRealMethod()
    {
        return strtoupper((string) $this->server->get('REQUEST_METHOD', 'GET'));
    }

    /**
     * Get the request method (uppercased).
     *
     * @return string
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper((string) $this->server->get('REQUEST_METHOD', 'GET'));

            if ('POST' === $this->method) {
                $method = $this->request->get('_method', $this->query->get('_method', 'POST'));
                // X-Http-Method-Override is opt-in: it spoofs the method, so access
                // control should use real_method().
                $allow_header_override = false;

                try {
                    // Use Config if available, otherwise default to false (secure)
                    if (class_exists('\System\Config')) {
                        $allow_header_override = (bool) \System\Config::get('application.allow_method_override', false);
                    }
                } catch (\Throwable $e) {
                    // ignore
                } catch (\Exception $e) {
                    // ignore
                }

                if ($allow_header_override) {
                    $method = $this->headers->get('X-Http-Method-Override', $method);
                }

                $this->method = strtoupper((string) $method);
            }
        }

        return $this->method;
    }

    /**
     * Get the mime-type for the given format.
     *
     * @param string $format
     *
     * @return string
     */
    public function getMimeType($format)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }

        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }

    /**
     * Get the format for the given mime-type.
     *
     * @param string $mimeType
     *
     * @return string|null
     */
    public function getFormat($mimeType)
    {
        $mimeType = (string) $mimeType;

        if (false !== $pos = strpos($mimeType, ';')) {
            $mimeType = substr($mimeType, 0, $pos);
        }

        if (null === static::$formats) {
            static::initializeFormats();
        }

        foreach (static::$formats as $format => $mimeTypes) {
            if (in_array($mimeType, (array) $mimeTypes)) {
                return $format;
            }
        }
    }

    /**
     * Pair format with mime-types.
     *
     * @param string       $format
     * @param string|array $mimeTypes
     */
    public function setFormat($format, $mimeTypes)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }

        static::$formats[null === $format ? '' : $format] = is_array($mimeTypes) ? $mimeTypes : [$mimeTypes];
    }

    /**
     * Get the request format.
     *
     * @param string $default
     *
     * @return string
     */
    public function getRequestFormat($default = 'html')
    {
        if (null === $this->format) {
            $this->format = $this->get('_format', $default);
        }

        return $this->format;
    }

    /**
     * Set the request format.
     *
     * @param string $format
     */
    public function setRequestFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Get the content type of the request.
     *
     * @return string|null
     */
    public function getContentType()
    {
        return $this->getFormat($this->headers->get('Content-Type'));
    }

    /**
     * Set default language.
     *
     * @param string $locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;

        if (null === $this->locale) {
            $this->setPhpDefaultLocale($locale);
        }
    }

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->setPhpDefaultLocale($this->locale = $locale);
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return is_null($this->locale) ? $this->defaultLocale : $this->locale;
    }

    /**
     * Check if the request method matches the given method.
     *
     * @param string $method
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper((string) $method);
    }

    /**
     * Check if the request method is safe (GET, HEAD).
     *
     * @return bool
     */
    public function isMethodSafe()
    {
        return in_array($this->getMethod(), ['GET', 'HEAD']);
    }

    /**
     * Get the root URL.
     *
     * @return string
     */
    public function getRootUrl()
    {
        return $this->getScheme().'://'.$this->getHttpHost().$this->getBasePath();
    }

    /**
     * Get the request body content.
     *
     * @param bool $asResource
     *
     * @return string|resource
     */
    public function getContent($asResource = false)
    {
        if (false === $this->content || (true === $asResource && null !== $this->content)) {
            throw new \LogicException('File::getContent() can only be called once when using the resource return type.');
        }

        if (true === $asResource) {
            $this->content = false;
            return fopen('php://input', 'rb');
        }

        if (null === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * Get the ETags from If-None-Match header.
     *
     * @return array
     */
    public function getETags()
    {
        return preg_split('/\s*,\s*/', (string) $this->headers->get('If-None-Match'), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Check if the request has a no-cache directive.
     *
     * @return bool
     */
    public function isNoCache()
    {
        return $this->headers->hasCacheControlDirective('no-cache') || ('no-cache' === $this->headers->get('Pragma'));
    }

    /**
     * Get the prefered language.
     *
     * @param array $locales
     *
     * @return string|null
     */
    public function getPreferredLanguage(array $locales = [])
    {
        $preferred = $this->getLanguages();

        if (empty($locales)) {
            return isset($preferred[0]) ? $preferred[0] : null;
        }

        if (! $preferred) {
            return $locales[0];
        }

        $preferred = array_values(array_intersect($preferred, $locales));
        return isset($preferred[0]) ? $preferred[0] : $locales[0];
    }

    /**
     * Get client's accepted language list.
     *
     * @return array
     */
    public function getLanguages()
    {
        if (null !== $this->languages) {
            return $this->languages;
        }

        $accept = $this->headers->get('Accept-Language');
        $languages = $this->splitHttpAcceptHeader($accept);
        $this->languages = [];

        foreach ($languages as $lang => $q) {
            if (strstr($lang, '-')) {
                $codes = explode('-', $lang);

                if ('i' === $codes[0]) {
                    if (count($codes) > 1) {
                        $lang = $codes[1];
                    }
                } else {
                    $count = count($codes);

                    for ($i = 0, $max = $count; $i < $max; ++$i) {
                        if (0 === $i) {
                            $lang = strtolower((string) $codes[0]);
                        } else {
                            $lang .= '_'.strtoupper((string) $codes[$i]);
                        }
                    }
                }
            }

            $this->languages[] = $lang;
        }

        return $this->languages;
    }

    /**
     * Get client's accepted charset list.
     *
     * @return array
     */
    public function getCharsets()
    {
        if (null !== $this->charsets) {
            return $this->charsets;
        }

        $charsets = array_keys($this->splitHttpAcceptHeader($this->headers->get('Accept-Charset')));
        $this->charsets = $charsets;
        return $this->charsets;
    }

    /**
     * Get client's accepted content-type list.
     *
     * @return array
     */
    public function getAcceptableContentTypes()
    {
        if (null !== $this->acceptableContentTypes) {
            return $this->acceptableContentTypes;
        }

        $acceptable = array_keys($this->splitHttpAcceptHeader($this->headers->get('Accept')));
        $this->acceptableContentTypes = $acceptable;
        return $this->acceptableContentTypes;
    }

    /**
     * Check if current request is AJAX.
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return 'xmlhttprequest' === strtolower((string) $this->headers->get('X-Requested-With'));
    }

    /**
     * Splits the Accept-* headers.
     *
     * @param string $header
     *
     * @return array
     */
    public function splitHttpAcceptHeader($header)
    {
        if (! $header) {
            return [];
        }

        $items = array_filter(explode(',', $header));
        $values = [];
        $groups = [];

        foreach ($items as $value) {
            if (preg_match('/;\s*(q=.*$)/', $value, $match)) {
                $q = substr(trim((string) $match[1]), 2);
                $value = trim(substr((string) $value, 0, -mb_strlen((string) $match[0], '8bit')));
            } else {
                $q = 1;
            }

            $groups[$q][] = $value;
        }

        krsort($groups);

        foreach ($groups as $q => $items) {
            $q = (float) $q;

            if (0 < $q) {
                foreach ($items as $value) {
                    $values[trim($value)] = $q;
                }
            }
        }

        return $values;
    }

    /**
     * Prepare the request URI.
     *
     * @return string
     */
    protected function prepareRequestUri()
    {
        $requestUri = '';

        if ('1' === (string) $this->server->get('IIS_WasUrlRewritten') && '' !== (string) $this->server->get('UNENCODED_URL')) {
            $requestUri = $this->server->get('UNENCODED_URL');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = (string) $this->server->get('REQUEST_URI');
            $schemeAndHttpHost = (string) $this->getSchemeAndHttpHost();

            if (0 === strpos($requestUri, $schemeAndHttpHost)) {
                $requestUri = substr($requestUri, mb_strlen($schemeAndHttpHost, '8bit'));
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            $requestUri = $this->server->get('ORIG_PATH_INFO');

            if ('' !== $this->server->get('QUERY_STRING')) {
                $requestUri .= '?'.$this->server->get('QUERY_STRING');
            }
        }

        return $requestUri;
    }

    /**
     * Prepare the base URL.
     *
     * @return string
     */
    protected function prepareBaseUrl()
    {
        $filename = basename((string) $this->server->get('SCRIPT_FILENAME'));

        if (basename((string) $this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = (string) $this->server->get('SCRIPT_NAME');
        } elseif (basename((string) $this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = (string) $this->server->get('PHP_SELF');
        } elseif (basename((string) $this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            // Compatibility with 1and1.com (ionos.com) shared hosting
            $baseUrl = (string) $this->server->get('ORIG_SCRIPT_NAME');
        } else {
            $path = (string) $this->server->get('PHP_SELF', '');
            $file = (string) $this->server->get('SCRIPT_FILENAME', '');
            $segments = array_reverse(explode('/', trim($file, '/')));

            $index = 0;
            $last = count($segments);
            $baseUrl = '';

            do {
                $segment = $segments[$index];
                $baseUrl = '/'.$segment.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== ($pos = strpos($path, $baseUrl))) && 0 !== $pos);
        }

        $requestUri = (string) $this->getRequestUri();
        $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl);

        if ($baseUrl && false !== $prefix) {
            return $prefix;
        }

        $prefix = $this->getUrlencodedPrefix($requestUri, dirname($baseUrl));

        if ($baseUrl && false !== $prefix) {
            return rtrim($prefix, '/');
        }

        $truncatedUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        if (empty($basename) || ! strpos(rawurldecode($truncatedUri), $basename)) {
            return '';
        }

        if ((mb_strlen($requestUri, '8bit') >= mb_strlen($baseUrl, '8bit')) && ((false !== ($pos = strpos($requestUri, $baseUrl))) && (0 !== $pos))) {
            $baseUrl = substr($requestUri, 0, $pos + mb_strlen($baseUrl, '8bit'));
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Prepare the base path.
     *
     * @return string
     */
    protected function prepareBasePath()
    {
        $filename = basename((string) $this->server->get('SCRIPT_FILENAME'));
        $baseUrl = (string) $this->getBaseUrl();

        if (empty($baseUrl)) {
            return '';
        }

        $basePath = (basename($baseUrl) === $filename) ? dirname($baseUrl) : $baseUrl;
        $basePath = ('\\' === DS) ? str_replace('\\', '/', $basePath) : $basePath;
        return rtrim($basePath, '/');
    }

    /**
     * Prepare the path info.
     *
     * @return string
     */
    protected function preparePathInfo()
    {
        $baseUrl = $this->getBaseUrl();

        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }

        $pathInfo = '/';

        if ($pos = strpos((string) $requestUri, '?')) {
            $requestUri = substr((string) $requestUri, 0, $pos);
        }

        if ((null !== $baseUrl) && (false === ($pathInfo = substr((string) $requestUri, mb_strlen($baseUrl, '8bit'))))) {
            return '/';
        } elseif (null === $baseUrl) {
            return $requestUri;
        }

        return (string) $pathInfo;
    }

    /**
     * Initialize the request formats.
     */
    protected static function initializeFormats()
    {
        static::$formats = [
            'html' => ['text/html', 'application/xhtml+xml'],
            'txt' => ['text/plain'],
            'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
            'css' => ['text/css'],
            'json' => ['application/json', 'application/x-json'],
            'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
            'rdf' => ['application/rdf+xml'],
            'atom' => ['application/atom+xml'],
            'rss' => ['application/rss+xml'],
        ];
    }

    /**
     * Set PHP default language.
     *
     * @param string $locale
     */
    private function setPhpDefaultLocale($locale)
    {
        try {
            if (class_exists('\Locale', false)) {
                \Locale::setDefault($locale);
            }
        } catch (\Throwable $e) {
            // Skip error
        } catch (\Exception $e) {
            // Skip error
        }
    }

    /**
     * Get url-encoded prefix.
     *
     * @param string $string
     * @param string $prefix
     *
     * @return string|false
     */
    private function getUrlencodedPrefix($string, $prefix)
    {
        $prefix = (string) $prefix;
        return (! $prefix || 0 !== strpos((string) rawurldecode($string), $prefix))
            ? false
            : (preg_match('#^(%[[:xdigit:]]{2}|.){'.mb_strlen($prefix, '8bit').'}#', $string, $match) ? $match[0] : false);
    }
}
