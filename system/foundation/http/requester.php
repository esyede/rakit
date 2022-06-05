<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Requester
{
    public $attributes;
    public $request;
    public $query;
    public $server;
    public $files;
    public $cookies;
    public $headers;

    protected $content;
    protected $languages;
    protected $charsets;
    protected $acceptableContentTypes;
    protected $pathInfo;
    protected $requestUri;
    protected $baseUrl;
    protected $basePath;
    protected $method;
    protected $format;
    protected $session;
    protected $locale;
    protected $defaultLocale = 'id';

    protected static $formats;
    protected static $trustProxy = false;
    protected static $trustedProxies = [];
    protected static $trustedHeaders = [
        self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
        self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
    ];

    const HEADER_CLIENT_IP = 'client_ip';
    const HEADER_CLIENT_HOST = 'client_host';
    const HEADER_CLIENT_PROTO = 'client_proto';
    const HEADER_CLIENT_PORT = 'client_port';

    /**
     * Konstruktor.
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
        $this->initialize(
            $query,
            $request,
            $attributes,
            $cookies,
            $files,
            $server,
            $content
        );
    }

    /**
     * Set parameter untuk request saat ini.
     * Method ini juga menginisialisasi ulang seluruh property.
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
     * Buat object request baru menggunakan data milik PHP.
     *
     * @return Request
     */
    public static function createFromGlobals()
    {
        $request = new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

        $type = $request->headers->get('CONTENT_TYPE');
        $method = strtoupper($request->server->get('REQUEST_METHOD', 'GET'));

        if (0 === strpos($type, 'application/x-www-form-urlencoded')
        && in_array($method, ['PUT', 'DELETE', 'PATCH'])) {
            parse_str($request->getContent(), $data);
            $request->request = new Parameter($data);
        }

        return $request;
    }

    /**
     * Buat object request baru berdasarkan URI dan konfigurasi yang diberikan.
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
        $parameters = [],
        $cookies = [],
        $files = [],
        $server = [],
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

        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $defaults['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                // no break, memang sengaja.
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

        $queryString = http_build_query($query, '', '&');
        $uri = $components['path'].('' !== $queryString ? '?'.$queryString : '');

        $server = array_replace($defaults, $server, [
            'REQUEST_METHOD' => strtoupper($method),
            'PATH_INFO' => '',
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => $queryString,
        ]);

        return new static($query, $request, [], $cookies, $files, $server, $content);
    }

    /**
     * Clone object request dan timpa beberapa property-nya.
     *
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server
     *
     * @return static
     */
    public function duplicate(
        array $query = null,
        array $request = null,
        array $attributes = null,
        array $cookies = null,
        array $files = null,
        array $server = null
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
     * Clone object request saat ini.
     * (session tidak akan ikut ter-clone).
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
     * Mereturn object request sebagai string.
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
     * Timpa variabel global PHP menurut instance object request saat ini.
     * Ini akan menimpa value $_GET, $_POST, $_REQUEST, $_SERVER, dan $_COOKIE.
     * Variabel $_FILES tidak akan ditimpa.
     */
    public function overrideGlobals()
    {
        $_GET = $this->query->all();
        $_POST = $this->request->all();
        $_SERVER = $this->server->all();
        $_COOKIE = $this->cookies->all();
        $headers = $this->headers->all();

        foreach ($headers as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $_SERVER[$key] = implode(', ', $value);
            } else {
                $_SERVER['HTTP_'.$key] = implode(', ', $value);
            }
        }

        $request = ['g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE];

        $orderings = ini_get('request_order');
        $orderings = $orderings ? $orderings : ini_get('variable_order');
        $orderings = preg_replace('/[^cgp]/', '', strtolower($orderings));
        $orderings = $orderings ? $orderings : 'gp';

        $_REQUEST = [];
        $orders = str_split($orderings);

        foreach ($orders as $order) {
            $_REQUEST = array_merge($_REQUEST, $request[$order]);
        }
    }

    /**
     * Set list trusted proxy.
     *
     * @param array $proxies
     */
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
        self::$trustProxy = $proxies ? true : false;
    }

    /**
     * Set nama trusted header.
     * Hanya mendukung header - heder berikut:.
     *
     * <code>
     *
     *    Requester::HEADER_CLIENT_IP:    (default: X-Forwarded-For, lihat getClientIp())
     *    Requester::HEADER_CLIENT_HOST:  (default: X-Forwarded-Host, lihat getClientHost())
     *    Requester::HEADER_CLIENT_PORT:  (default: X-Forwarded-Port, lihat getClientPort())
     *    Requester::HEADER_CLIENT_PROTO: (default: X-Forwarded-Proto, lihat getScheme() and isSecure())
     *
     * </code>
     *
     * Mengoper value kosong berarti menonaktifkan trusted header milik key yang diberikan.
     *
     * @param string $key
     * @param string $value
     */
    public static function setTrustedHeaderName($key, $value)
    {
        if (! array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(
                sprintf("Unable to set the trusted header name for key '.%s'", $key)
            );
        }

        self::$trustedHeaders[$key] = $value;
    }

    /**
     * Periksa apakah isi $_SERVER datang dari trusted proxy atau bukan.
     *
     * @return bool
     */
    public static function isProxyTrusted()
    {
        return self::$trustProxy;
    }

    /**
     * Normalisasi query string.
     *
     * Normalisasi ini akan mengurutkan query string mengikuti alfabet,
     * menghapus delimiter yang tidk diperlukan, serta
     * memberi mekanisme escape yang lebih konsisten.
     *
     * @param string $queryString
     *
     * @return string
     */
    public static function normalizeQueryString($queryString)
    {
        if ('' === $queryString) {
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
                ? rawurlencode(urldecode($keyValuePair[0]))
                    .'='.rawurlencode(urldecode($keyValuePair[1]))
                : rawurlencode(urldecode($keyValuePair[0]));

            $order[] = urldecode($keyValuePair[0]);
        }

        array_multisort($order, SORT_ASC, $parts);

        return implode('&', $parts);
    }

    /**
     * Ambil value 'parameter'.
     *
     * Method ini sedianya digunakan untuk fleksibilitas saja.
     * Jangan gunakan method ini pada controller anda karena ia sangat lambat.
     *
     * Urutan: GET, PATH, POST.
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
     * Ambil object session.
     *
     * @return object|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Periksa apakah request saat ini mengandung session yang telah
     * aktif di request - request sebelumnya.
     *
     * @return bool
     */
    public function hasPreviousSession()
    {
        return $this->hasSession()
            && $this->cookies->has($this->session->getName());
    }

    /**
     * Periksa apakah request saat ini mengandung object session.
     *
     * @return bool
     */
    public function hasSession()
    {
        return null !== $this->session;
    }

    /**
     * Set object session.
     *
     * @param object $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * Ambil IP klien.
     *
     * @return string
     */
    public function getClientIp()
    {
        $ip = $this->server->get('REMOTE_ADDR');

        if (! self::$trustProxy) {
            return $ip;
        }

        if (! self::$trustedHeaders[self::HEADER_CLIENT_IP]
        || ! $this->headers->has(self::$trustedHeaders[self::HEADER_CLIENT_IP])) {
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
     * Ambil script name.
     *
     * @return string
     */
    public function getScriptName()
    {
        $original = $this->server->get('ORIG_SCRIPT_NAME', '');

        return $this->server->get('SCRIPT_NAME', $original);
    }

    /**
     * Mereturn path request saat ini. Contoh:.
     *
     * <code>
     *
     *      http://localhost/mysite              mereturn  string kosong
     *      http://localhost/mysite/about        mereturn  '/about'
     *      htpp://localhost/mysite/enco%20ded   mereturn  '/enco%20ded'
     *      http://localhost/mysite/about?var=1  mereturn  '/about'
     *
     * <code>
     *
     * @return string
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }

    /**
     * Mereturn root path request saat ini. Contoh:.
     *
     * <code>
     *
     *      http://localhost/index.php         mereturn  string kosong
     *      http://localhost/index.php/page    mereturn  string kosong
     *      http://localhost/web/index.php     mereturn  '/web'
     *      http://localhost/we%20b/index.php  mereturn  '/we%20b'
     *
     * <code>
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
     * Mereturn URL root (tanpa akhiran '/').
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
     * Ambil skema request (http / https).
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Mereturn port.
     *
     * @return string
     */
    public function getPort()
    {
        $clientPort = self::$trustedHeaders[self::HEADER_CLIENT_PORT];

        if (self::$trustProxy
        && $clientPort
        && $port = $this->headers->get($clientPort)) {
            return $port;
        }

        return $this->server->get('SERVER_PORT');
    }

    /**
     * Mereturn user pada auth basic PHP.
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->server->get('PHP_AUTH_USER');
    }

    /**
     * Mereturn password pada auth basic PHP.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->server->get('PHP_AUTH_PW');
    }

    /**
     * Ambil info user dan password pada auth basic PHP.
     *
     * @return string
     */
    public function getUserInfo()
    {
        $userinfo = $this->getUser();
        $pass = $this->getPassword();
        $userinfo .= ('' === $pass) ? '' : ':'.$pass;

        return $userinfo;
    }

    /**
     * Mereturn host untuk request saat ini.
     * Juga akan ditambahkan portnya jika tidak menggunakan port standar.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' === $scheme && 80 === (int) $port)
        || ('https' === $scheme && 443 === (int) $port)) {
            return $this->getHost();
        }

        return $this->getHost().':'.$port;
    }

    /**
     * Mereturn URI.
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
     * Ambil skema dan host.
     *
     * @return string
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * Mereturn URI request yang telah dinormalisasi.
     *
     * @return string
     */
    public function getUri()
    {
        $queryString = $this->getQueryString();

        if (null !== $queryString) {
            $queryString = '?'.$queryString;
        }

        return $this->getSchemeAndHttpHost().
            $this->getBaseUrl().
            $this->getPathInfo().$queryString;
    }

    /**
     * Mereturn URI ke path yang telah dinormalisasi.
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
     * Mereturn query string yang telah dinormalisasi.
     *
     * @return string|null
     */
    public function getQueryString()
    {
        $queryString = static::normalizeQueryString($this->server->get('QUERY_STRING'));

        return ('' === $queryString) ? null : $queryString;
    }

    /**
     * Periksa apakah request saat ini menggunakan koneksi aman.
     *
     * @return bool
     */
    public function isSecure()
    {
        $clientProto = self::$trustedHeaders[self::HEADER_CLIENT_PROTO];

        if (self::$trustProxy
        && $clientProto
        && $proto = $this->headers->get($clientProto)) {
            return in_array(strtolower($proto), ['https', 'on', '1']);
        }

        $https = $this->server->get('HTTPS');

        return ('on' === $https || 1 === $https);
    }

    /**
     * Mereturn hostname.
     *
     * @return string
     */
    public function getHost()
    {
        $clientHost = self::$trustedHeaders[self::HEADER_CLIENT_HOST];

        if (self::$trustProxy
        && $clientHost
        && $host = $this->headers->get($clientHost)) {
            $elements = explode(',', $host);
            $host = $elements[count($elements) - 1];
        } elseif (! $host = $this->headers->get('HOST')) {
            if (! $host = $this->server->get('SERVER_NAME')) {
                $host = $this->server->get('SERVER_ADDR', '');
            }
        }

        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        if ($host && ! preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host)) {
            throw new \UnexpectedValueException(sprintf('Invalid host: %s', $host));
        }

        return $host;
    }

    /**
     * Set request method.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }

    /**
     * Ambil request method dalam bentuk uppercase.
     *
     * @return string
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));

            if ('POST' === $this->method) {
                $method = $this->query->get('_method', 'POST');
                $method = $this->request->get('_method', $method);
                $method = $this->headers->get('X-HTTP-METHOD-OVERRIDE', $method);
                $this->method = strtoupper($method);
            }
        }

        return $this->method;
    }

    /**
     * Ambil mime-type berdsarkan format yang diberikan.
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
     * Ambil format berdsarkan mimetype yang diberikan.
     *
     * @param string $mimeType
     *
     * @return string|null
     */
    public function getFormat($mimeType)
    {
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
     * Pasangkan format dengan mime-typenya.
     *
     * @param string       $format
     * @param string|array $mimeTypes
     */
    public function setFormat($format, $mimeTypes)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }

        static::$formats[$format] = is_array($mimeTypes) ? $mimeTypes : [$mimeTypes];
    }

    /**
     * Ambil format request.
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
     * Set format request.
     *
     * @param string $format
     */
    public function setRequestFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Ambil format berdasarkan request.
     *
     * @return string|null
     */
    public function getContentType()
    {
        return $this->getFormat($this->headers->get('CONTENT_TYPE'));
    }

    /**
     * Set default bahasa.
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
     * Set bahasa.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->setPhpDefaultLocale($this->locale = $locale);
    }

    /**
     * Ambil bahasa.
     *
     * @return string
     */
    public function getLocale()
    {
        return is_null($this->locale) ? $this->defaultLocale : $this->locale;
    }

    /**
     * Periksa apakah request method saat ini cocok dengan method yang diberikan.
     *
     * @param string $method
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Periksa apakah request method saat ini aman.
     *
     * @return bool
     */
    public function isMethodSafe()
    {
        return in_array($this->getMethod(), ['GET', 'HEAD']);
    }

    /**
     * Mereturn the konten body milik request.
     *
     * @param bool $asResource
     *
     * @return string|resource
     */
    public function getContent($asResource = false)
    {
        if (false === $this->content
        || (true === $asResource && null !== $this->content)) {
            throw new \LogicException('getContent() can only be called once when using the resource return type.');
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
     * Ambil ETag.
     *
     * @return array
     */
    public function getETags()
    {
        return preg_split('/\s*,\s*/', $this->headers->get('if_none_match'), null, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Periksa apakah pragma no-cache aktif atau tidak.
     *
     * @return bool
     */
    public function isNoCache()
    {
        return $this->headers->hasCacheControlDirective('no-cache')
            || ('no-cache' === $this->headers->get('Pragma'));
    }

    /**
     * Mereturn preferred language.
     *
     * @param array $locales
     *
     * @return string|null
     */
    public function getPreferredLanguage(array $locales = null)
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
     * Ambil list bahasa yang bisa diterima oleh browser klien.
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
                            $lang = strtolower($codes[0]);
                        } else {
                            $lang .= '_'.strtoupper($codes[$i]);
                        }
                    }
                }
            }

            $this->languages[] = $lang;
        }

        return $this->languages;
    }

    /**
     * Ambil list charset yang bisa diterima oleh browser klien.
     *
     * @return array
     */
    public function getCharsets()
    {
        if (null !== $this->charsets) {
            return $this->charsets;
        }

        $charsets = $this->headers->get('Accept-Charset');
        $charsets = array_keys($this->splitHttpAcceptHeader($charsets));
        $this->charsets = $charsets;

        return $this->charsets;
    }

    /**
     * Ambil list content-type yang bisa diterima oleh browser klien.
     *
     * @return array
     */
    public function getAcceptableContentTypes()
    {
        if (null !== $this->acceptableContentTypes) {
            return $this->acceptableContentTypes;
        }

        $acceptable = $this->headers->get('Accept');
        $acceptable = array_keys($this->splitHttpAcceptHeader($acceptable));
        $this->acceptableContentTypes = $acceptable;

        return $this->acceptableContentTypes;
    }

    /**
     * Periksa apakah request saat ini menggunakan ajax.
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return 'xmlhttprequest' === strtolower($this->headers->get('X-Requested-With'));
    }

    /**
     * Potong - potong header Accept-*.
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

        $values = [];
        $groups = [];

        foreach (array_filter(explode(',', $header)) as $value) {
            if (preg_match('/;\s*(q=.*$)/', $value, $match)) {
                $q = substr(trim($match[1]), 2);
                $value = trim(substr($value, 0, -mb_strlen($match[0], '8bit')));
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
     * Siapkan URI request.
     *
     * @return string
     */
    protected function prepareRequestUri()
    {
        $requestUri = '';

        if ($this->headers->has('X_ORIGINAL_URL')
        && false !== stripos(PHP_OS, 'WIN')) {
            $requestUri = $this->headers->get('X_ORIGINAL_URL');
        } elseif ($this->headers->has('X_REWRITE_URL')
        && false !== stripos(PHP_OS, 'WIN')) {
            $requestUri = $this->headers->get('X_REWRITE_URL');
        } elseif ('1' === $this->server->get('IIS_WasUrlRewritten')
        && '' !== $this->server->get('UNENCODED_URL')) {
            $requestUri = $this->server->get('UNENCODED_URL');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');
            $schemeAndHttpHost = $this->getSchemeAndHttpHost();

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
     * Siapkan base URL.
     *
     * @return string
     */
    protected function prepareBaseUrl()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));

        if (basename($this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (basename($this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            // Kompatibilitas shared hosting 1and1.com
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME');
        } else {
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);

            $index = 0;
            $last = count($segs);
            $baseUrl = '';

            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== ($pos = strpos($path, $baseUrl))) && 0 !== $pos);
        }

        $requestUri = $this->getRequestUri();
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

        if ((mb_strlen($requestUri, '8bit') >= mb_strlen($baseUrl, '8bit'))
        && ((false !== ($pos = strpos($requestUri, $baseUrl)))
        && (0 !== $pos))) {
            $baseUrl = substr($requestUri, 0, $pos + mb_strlen($baseUrl, '8bit'));
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Siapkan base path.
     *
     * @return string
     */
    protected function prepareBasePath()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        $baseUrl = $this->getBaseUrl();

        if (empty($baseUrl)) {
            return '';
        }

        if (basename($baseUrl) === $filename) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        // Windows memang nyebelin
        if ('\\' === DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    /**
     * Siapkan path info.
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

        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if ((null !== $baseUrl)
        && (false === ($pathInfo = substr($requestUri, mb_strlen($baseUrl, '8bit'))))) {
            return '/';
        } elseif (null === $baseUrl) {
            return $requestUri;
        }

        return (string) $pathInfo;
    }

    /**
     * Inisialisasi format request.
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
     * Set bahasa default PHP.
     *
     * @param string $locale
     */
    private function setPhpDefaultLocale($locale)
    {
        try {
            if (class_exists('Locale', false)) {
                \Locale::setDefault($locale);
            }
        } catch (\Throwable $e) {
            // Skip error
        } catch (\Exception $e) {
            // Skip error
        }
    }

    /**
     * Mereturn prefix yang telah di-encode.
     *
     * @param string $string
     * @param string $prefix
     *
     * @return string|false
     */
    private function getUrlencodedPrefix($string, $prefix)
    {
        $prefix = (string) $prefix;

        if (! $prefix || 0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }

        $len = mb_strlen($prefix, '8bit');

        if (preg_match('#^(%[[:xdigit:]]{2}|.){'.$len.'}#', $string, $match)) {
            return $match[0];
        }

        return false;
    }
}
