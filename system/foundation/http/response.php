<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Response
{
    protected $content;
    protected $version;
    protected $statusCode;
    protected $statusText;
    protected $charset;

    public $headers;

    public static $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        419 => 'Page Expired',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Konstruktor.
     *
     * @param string $content
     * @param int    $status
     * @param array  $headers
     */
    public function __construct($content = '', $status = 200, array $headers = [])
    {
        $this->headers = new Helper($headers);
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setProtocolVersion('1.0');

        if (!$this->headers->has('Date')) {
            $this->setDate(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }

    /**
     * Factory method untuk chainability.
     *
     * <code>
     *
     *     return Response::create($body, 200)->setSharedMaxAge(300);
     *
     * </code>
     *
     * @param string $content
     * @param int    $status
     * @param array  $headers
     *
     * @return static
     */
    public static function create($content = '', $status = 200, array $headers = [])
    {
        return new static($content, $status, $headers);
    }

    /**
     * Mereturn object Response sebagai string.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)
            . "\r\n" . $this->headers . "\r\n" . $this->getContent();
    }

    /**
     * Clone instance Response saat ini.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    /**
     * Prepares the Response before it is sent to the client.
     * Siapkan response untuk dikirim ke klien (mengikuti RFC 2616).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function prepare(Request $request)
    {
        $headers = $this->headers;

        if ($this->isInformational() || in_array($this->statusCode, [204, 304])) {
            $this->setContent(null);
        }

        if (!$headers->has('Content-Type')) {
            $format = $request->getRequestFormat();

            if (null !== $format && $mimeType = $request->getMimeType($format)) {
                $headers->set('Content-Type', $mimeType);
            }
        }

        $charset = $this->charset ? $this->charset : 'UTF-8';

        if (!$headers->has('Content-Type')) {
            $headers->set('Content-Type', 'text/html; charset=' . $charset);
        } elseif (
            0 === strpos((string) $headers->get('Content-Type'), 'text/')
            && false === strpos((string) $headers->get('Content-Type'), 'charset')
        ) {
            $headers->set('Content-Type', $headers->get('Content-Type') . '; charset=' . $charset);
        }

        if ($headers->has('Transfer-Encoding')) {
            $headers->remove('Content-Length');
        }

        if ('HEAD' === $request->getMethod()) {
            $length = $headers->get('Content-Length');
            $this->setContent(null);

            if ($length) {
                $headers->set('Content-Length', $length);
            }
        }

        if ('HTTP/1.0' !== $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }

        if (
            '1.0' === $this->getProtocolVersion()
            && 'no-cache' === $this->headers->get('Cache-Control')
        ) {
            $this->headers->set('pragma', 'no-cache');
            $this->headers->set('expires', -1);
        }

        return $this;
    }

    /**
     * Kirim HTTP header.
     *
     * @return $this
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return $this;
        }

        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        $headers = $this->headers->all();

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }

        $cookies = $this->headers->getCookies();

        foreach ($cookies as $cookie) {
            if (PHP_VERSION_ID < 70300) {
                setcookie(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpiresTime(),
                    $cookie->getPath() . '; samesite=' . $cookie->getSameSite(),
                    $cookie->getDomain(),
                    $cookie->isSecure(),
                    $cookie->isHttpOnly()
                );
            } else {
                setcookie($cookie->getName(), $cookie->getValue(), [
                    'expires' => $cookie->getExpiresTime(),
                    'path' => $cookie->getPath(),
                    'domain' => $cookie->getDomain(),
                    'secure' => $cookie->isSecure(),
                    'httponly' => $cookie->isHttpOnly(),
                    'samesite' => $cookie->getSameSite(),
                ]);
            }
        }

        return $this;
    }

    /**
     * Kirim konten response ke browser.
     *
     * @return $this
     */
    public function sendContent()
    {
        echo $this->content;

        return $this;
    }

    /**
     * Kirim response saat ini.
     *
     * @param bool $finishRequest
     *
     * @return $this
     */
    public function send($finishRequest = false)
    {
        $this->sendHeaders();
        $this->sendContent();

        if ($finishRequest) {
            $this->finish();
        }

        return $this;
    }

    /**
     * Set konten response.
     * (berupa string, angka atau object yang mengimplementasikan magic method __toString()).
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        if (
            null !== $content
            && !is_string($content)
            && !is_numeric($content)
            && !is_callable([$content, '__toString'])
        ) {
            throw new \UnexpectedValueException(sprintf(
                'Response content must be a string or object implementing __toString(), %s given.',
                gettype($content)
            ));
        }

        $this->content = (string) $content;

        return $this;
    }

    /**
     * Ambil konten response saat ini.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set versi protokol http (1.0 atau 1.1).
     *
     * @param string $version
     *
     * @return $this
     */
    public function setProtocolVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Ambil versi protokol http.
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Set status code.
     *
     * @param int   $code
     * @param mixed $text
     *
     * @return $this
     */
    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = (int) $code;

        if ($this->isInvalid()) {
            throw new \Exception(sprintf("The HTTP status code '%s' is not valid.", $code));
        }

        if (null === $text) {
            $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : '';
            return $this;
        }

        if (false === $text) {
            $this->statusText = '';
            return $this;
        }

        $this->statusText = $text;
        return $this;
    }

    /**
     * Ambil status code saat ini.
     *
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set charset.
     *
     * @param string $charset
     *
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Ambil charset.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Periksa apakah response bisa di-cache atau tidak.
     *
     * @return bool
     */
    public function isCacheable()
    {
        $cacheable = [200, 203, 300, 301, 302, 404, 410];

        if (!in_array($this->statusCode, $cacheable)) {
            return false;
        }

        if (
            $this->headers->hasCacheControlDirective('no-store')
            || $this->headers->getCacheControlDirective('private')
        ) {
            return false;
        }

        return $this->isValidateable() || $this->isFresh();
    }

    /**
     * Periksa apakah response masih 'fresh'.
     * Sebuah response dianggap fresh ketika time-to-live-nya lebih besar dari nol.
     *
     * @return bool
     */
    public function isFresh()
    {
        return $this->getTtl() > 0;
    }

    /**
     * Periksa apakah response memiliki header validasi.
     *
     * @return bool
     */
    public function isValidateable()
    {
        return $this->headers->has('Last-Modified') || $this->headers->has('ETag');
    }

    /**
     * Tandai response sebagai 'private'.
     * Ini akan membuat response tidak dapat digunakan untuk melayani klien lain.
     *
     * @return $this
     */
    public function setPrivate()
    {
        $this->headers->removeCacheControlDirective('public');
        $this->headers->addCacheControlDirective('private');

        return $this;
    }

    /**
     * Tandai response sebagai 'public'.
     * Ini akan membuat response dapat digunakan untuk melayani klien lain.
     *
     * @return $this
     */
    public function setPublic()
    {
        $this->headers->addCacheControlDirective('public');
        $this->headers->removeCacheControlDirective('private');

        return $this;
    }

    /**
     * Periksa apakah response harus di validasi ulang menurut cachenya.
     *
     * @return bool
     */
    public function mustRevalidate()
    {
        return $this->headers->hasCacheControlDirective('Must-Revalidate')
            || $this->headers->has('Proxy-Revalidate');
    }

    /**
     * Ambil value header Date sebagai instance object \DateTime.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->headers->getDate('Date', new \DateTime());
    }

    /**
     * Set header Date.
     *
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setDate(\DateTime $date)
    {
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->headers->set('Date', $date->format('D, d M Y H:i:s') . ' GMT');

        return $this;
    }

    /**
     * Mereturn usia response.
     *
     * @return int
     */
    public function getAge()
    {
        $age = $this->headers->get('Age');
        return $age ? $age : max(time() - $this->getDate()->format('U'), 0);
    }

    /**
     * Tandai response sebagai 'sudah kedaluwarsa'.
     *
     * @return $this
     */
    public function expire()
    {
        if ($this->isFresh()) {
            $this->headers->set('Age', $this->getMaxAge());
        }

        return $this;
    }

    /**
     * Ambil value header Expires sebagai instance object \DateTime.
     *
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->headers->getDate('Expires');
    }

    /**
     * Set value header Expires.
     * jika yang dioper adalah NULL, header Expires akan dihapus.
     *
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setExpires(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Expires');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    /**
     * Ambil value header Max-Age.
     *
     * @return int|null
     */
    public function getMaxAge()
    {
        if ($age = $this->headers->getCacheControlDirective('s-maxage')) {
            return $age;
        }

        if ($age = $this->headers->getCacheControlDirective('max-age')) {
            return $age;
        }

        if (null !== $this->getExpires()) {
            return $this->getExpires()->format('U') - $this->getDate()->format('U');
        }
    }

    /**
     * Set value header Max-Age.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setMaxAge($value)
    {
        $this->headers->addCacheControlDirective('max-age', $value);
        return $this;
    }

    /**
     * Set value header S-MaxAge (shared max-age).
     *
     * @param int $value
     *
     * @return $this
     */
    public function setSharedMaxAge($value)
    {
        $this->setPublic();
        $this->headers->addCacheControlDirective('s-maxage', $value);

        return $this;
    }

    /**
     * Ambil time-to-live (TTL) response dalam detik.
     *
     * @return int|null
     */
    public function getTtl()
    {
        $maxAge = $this->getMaxAge();
        return $maxAge ? ($maxAge - $this->getAge()) : null;
    }

    /**
     * Set TTL untuk shared max-age (s-maxage).
     *
     * @param int $seconds
     *
     * @return $this
     */
    public function setTtl($seconds)
    {
        $this->setSharedMaxAge($this->getAge() + $seconds);
        return $this;
    }

    /**
     * Set TTL untuk cache private/client (max-age).
     *
     * @param int $seconds
     *
     * @return $this
     */
    public function setClientTtl($seconds)
    {
        $this->setMaxAge($this->getAge() + $seconds);
        return $this;
    }

    /**
     * Ambil value header Last-Modified dalam bentuk object \DateTime.
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->headers->getDate('Last-Modified');
    }

    /**
     * Set value header Last-Modified
     * Jika yang dioper adalah NULL, maka header Last-Modified akan dihapus.
     *
     * @param \DateTime $date
     *
     * @return $this
     */
    public function setLastModified(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Last-Modified');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    /**
     * Ambil value header ETag.
     *
     * @return string
     */
    public function getEtag()
    {
        return $this->headers->get('ETag');
    }

    /**
     * Set value header ETag.
     *
     * @param string $etag
     * @param bool   $weak
     *
     * @return $this
     */
    public function setEtag($etag = null, $weak = false)
    {
        if (null === $etag) {
            $this->headers->remove('Etag');
        } else {
            $etag = (0 !== strpos((string) $etag, '"')) ? '"' . $etag . '"' : $etag;
            $this->headers->set('ETag', ($weak ? 'W/' : '') . $etag);
        }

        return $this;
    }

    /**
     * Set header - header untuk caching.
     * Opsi yang tersedia adalah: etag, last_modified, max_age, s_maxage, private dan public.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setCache(array $options)
    {
        $caching = ['etag', 'last_modified', 'max_age', 's_maxage', 'private', 'public'];

        if ($diff = array_diff(array_keys($options), $caching)) {
            throw new \Exception(sprintf(
                'Response does not support the following options: %s',
                implode('", "', array_values($diff))
            ));
        }

        if (isset($options['etag'])) {
            $this->setEtag($options['etag']);
        }

        if (isset($options['last_modified'])) {
            $this->setLastModified($options['last_modified']);
        }

        if (isset($options['max_age'])) {
            $this->setMaxAge($options['max_age']);
        }

        if (isset($options['s_maxage'])) {
            $this->setSharedMaxAge($options['s_maxage']);
        }

        if (isset($options['public'])) {
            if ($options['public']) {
                $this->setPublic();
            } else {
                $this->setPrivate();
            }
        }

        if (isset($options['private'])) {
            if ($options['private']) {
                $this->setPrivate();
            } else {
                $this->setPublic();
            }
        }

        return $this;
    }

    /**
     * Modifikasi response agar mengikuti aturan http status 304.
     *
     * @return $this
     */
    public function setNotModified()
    {
        $this->setStatusCode(304);
        $this->setContent(null);

        $headers = [
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified',
        ];

        foreach ($headers as $header) {
            $this->headers->remove($header);
        }

        return $this;
    }

    /**
     * Periksa apakah response memiliki header Vary.
     *
     * @return bool
     */
    public function hasVary()
    {
        return (bool) $this->headers->get('Vary');
    }

    /**
     * Ambil value header Vary.
     *
     * @return array
     */
    public function getVary()
    {
        $vary = $this->headers->get('Vary');
        return $vary ? (is_array($vary) ? $vary : preg_split('/[\s,]+/', $vary)) : [];
    }

    /**
     * Sets value header Vary.
     *
     * @param string|array $headers
     * @param bool         $replace
     *
     * @return $this
     */
    public function setVary($headers, $replace = true)
    {
        $this->headers->set('Vary', $headers, $replace);
        return $this;
    }

    /**
     * Periksa apakah validator response (ETag, Last-Modified) tidak berubah.
     *
     * Jika
     *
     * @param Request $request
     *
     * @return bool
     */
    public function isNotModified(Request $request)
    {
        if (!$request->isMethodSafe()) {
            return false;
        }

        $lastModified = $request->headers->get('If-Modified-Since');
        $etags = $request->getEtags();
        $notModified = false;

        if ($etags) {
            $notModified = (in_array($this->getEtag(), $etags) || in_array('*', $etags))
                && (!$lastModified || $this->headers->get('Last-Modified') === $lastModified);
        } elseif ($lastModified) {
            $notModified = ($lastModified === $this->headers->get('Last-Modified'));
        }

        if ($notModified) {
            $this->setNotModified();
        }

        return $notModified;
    }

    /**
     * Periksa apakah response saat ini invalid.
     *
     * @return bool
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * Periksa apakah response saat ini informasional.
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Periksa apakah response saat ini sukses.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Periksa apakah response saat ini adalah redireksi.
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Periksa apakah response saat ini adalah client error.
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Periksa apakah response saat ini adalah server error.
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Periksa apakah response saat ini OK.
     *
     * @return bool
     */
    public function isOk()
    {
        return 200 === $this->statusCode;
    }

    /**
     * Periksa apakah response saat ini forbidden.
     *
     * @return bool
     */
    public function isForbidden()
    {
        return 403 === $this->statusCode;
    }

    /**
     * Periksa apakah response saat ini not found.
     *
     * @return bool
     */
    public function isNotFound()
    {
        return 404 === $this->statusCode;
    }

    /**
     * Periksa apakah response saat ini merupakan redireksi.
     *
     * @param string $location
     *
     * @return bool
     */
    public function isRedirect($location = null)
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308])
            && ((null === $location)
                ? true
                : ((string) $location === (string) $this->headers->get('Location'))
            );
    }

    /**
     * Periksa apakah response saat ini empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, [201, 204, 304]);
    }

    /**
     * Finish/flush request buffer.
     *
     * @return void
     */
    public function finish()
    {
        $cliRequest = defined('STDIN')
            || 'cli' === php_sapi_name()
            || ('cgi' === substr((string) PHP_SAPI, 0, 3) && is_callable('getenv') && getenv('TERM'));

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (!$cliRequest) {
            $previous = null;
            $ob = ob_get_status(true);

            while (($level = ob_get_level()) > 0 && $level !== $previous) {
                $previous = $level;

                if (isset($ob[$level - 1]) && isset($ob[$level - 1]['del']) && $ob[$level - 1]['del']) {
                    ob_end_flush();
                }
            }

            flush();
        }
    }
}
