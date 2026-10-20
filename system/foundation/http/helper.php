<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct access.');

class Helper extends Header
{
    /** @var string */
    const COOKIES_FLAT = 'flat';

    /** @var string */
    const COOKIES_ARRAY = 'array';

    /** @var string */
    const DISPOSITION_ATTACHMENT = 'attachment';

    /** @var string */
    const DISPOSITION_INLINE = 'inline';

    /**
     * The computed cache control value.
     *
     * @var array
     */
    protected $computedCacheControl = [];

    /**
     * The cookies.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * The original-cased header names, keyed by their lowercase lookup key.
     *
     * @var array
     */
    protected $headerNames = [];

    /**
     * Constructor.
     *
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        parent::__construct($headers);

        if (! isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $headers = $this->allPreserveCase();
        ksort($headers);
        $string = '';

        if ($headers) {
            $max = max(array_map(function ($key) {
                return mb_strlen((string) $key, '8bit');
            }, array_keys($headers))) + 1;

            foreach ($headers as $name => $values) {
                foreach ($values as $value) {
                    $string .= sprintf("%-{$max}s %s\r\n", $name.':', $value);
                }
            }
        }

        foreach ($this->getCookies() as $cookie) {
            $string .= 'Set-Cookie: '.$cookie."\r\n";
        }

        return $string;
    }

    /**
     * Get all headers keyed by their original-cased names (for sending), like Symfony.
     *
     * @return array
     */
    public function allPreserveCase()
    {
        $headers = [];

        foreach ($this->headers as $key => $values) {
            $name = isset($this->headerNames[$key]) ? $this->headerNames[$key] : static::displayKey($key);
            $headers[$name] = $values;
        }

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $headers = [])
    {
        $this->headerNames = [];
        parent::replace($headers);

        if (! isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $values, $replace = true)
    {
        $name = str_replace(["\r", "\n", "\0"], '', (string) $key);
        $key = $this->standardizeKey($name);
        $this->headerNames[$key] = $name;
        parent::set($name, $values, $replace);

        if (in_array($key, ['cache-control', 'etag', 'last-modified', 'expires'])) {
            $computed = $this->computeCacheControlValue();
            $this->headers['cache-control'] = [$computed];
            $this->headerNames['cache-control'] = 'Cache-Control';
            $this->computedCacheControl = $this->parseCacheControl($computed);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        parent::remove($key);
        unset($this->headerNames[$this->standardizeKey($key)]);

        if ('cache-control' === $this->standardizeKey($key)) {
            $this->computedCacheControl = [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl) ? $this->computedCacheControl[$key] : null;
    }

    /**
     * Set a cookie.
     *
     * @param Cookie $cookie
     */
    public function setCookie($cookie)
    {
        $domain = $cookie->getDomain();
        $path = $cookie->getPath();
        $this->cookies[null === $domain ? '' : $domain][null === $path ? '' : $path][$cookie->getName()] = $cookie;
    }

    /**
     * Remove cookie by name (cookie in browser will not be removed).
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     */
    public function removeCookie($name, $path = '/', $domain = null)
    {
        if (null === $path) {
            $path = '/';
        }

        if (null === $domain) {
            $domain = '';
        }

        unset($this->cookies[$domain][$path][$name]);

        if (empty($this->cookies[$domain][$path])) {
            unset($this->cookies[$domain][$path]);

            if (empty($this->cookies[$domain])) {
                unset($this->cookies[$domain]);
            }
        }
    }

    /**
     * Get all cookies in specified format.
     *
     * @param string $format
     *
     * @return array
     */
    public function getCookies($format = self::COOKIES_FLAT)
    {
        if (! in_array($format, [self::COOKIES_FLAT, self::COOKIES_ARRAY])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Format "%s" invalid (%s).',
                    $format,
                    implode(', ', [self::COOKIES_FLAT, self::COOKIES_ARRAY])
                )
            );
        }

        if (self::COOKIES_ARRAY === $format) {
            return $this->cookies;
        }

        $flattened = [];

        foreach ($this->cookies as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattened[] = $cookie;
                }
            }
        }

        return $flattened;
    }

    /**
     * Clears a cookie (by setting it's expiration time to the past).
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     */
    public function clearCookie($name, $path = '/', $domain = null)
    {
        $this->setCookie(new Cookie($name, null, 1, $path, $domain));
    }

    /**
     * Create a Content-Disposition header value.
     *
     * @param string $disposition
     * @param string $filename
     * @param string $filenameFallback
     *
     * @return string
     */
    public function makeDisposition($disposition, $filename, $filenameFallback = '')
    {
        $filename = (string) $filename;
        $filenameFallback = (string) $filenameFallback;

        if (! in_array($disposition, [self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE])) {
            throw new \Exception(sprintf(
                "The disposition must be either '%s' or '%s'.",
                self::DISPOSITION_ATTACHMENT,
                self::DISPOSITION_INLINE
            ));
        }

        if ('' === $filenameFallback) {
            $filenameFallback = $filename;
        }

        if (preg_match('/[^\x00-\x7F]/', $filenameFallback)) {
            throw new \Exception('The filename fallback must only contain ASCII characters.');
        }

        if (false !== strpos($filenameFallback, '%')) {
            throw new \Exception("The filename fallback cannot contain the '%' character.");
        }

        if (
            false !== strpos($filename, '/')
            || false !== strpos($filename, '\\')
            || false !== strpos($filenameFallback, '/')
            || false !== strpos($filenameFallback, '\\')
        ) {
            throw new \Exception("The filename and the fallback cannot contain the '/' and '\' characters.");
        }

        $filenameFallback = str_replace('"', '\\"', $filenameFallback);
        $output = $disposition.'; filename="'.$filenameFallback.'"';

        if ($filename !== $filenameFallback) {
            $output .= sprintf("; filename*=utf-8''%s", rawurlencode($filename));
        }

        return $output;
    }

    /**
     * Get the accumulated Cache-Control header, normalized.
     *
     * @return string
     */
    protected function computeCacheControlValue()
    {
        if (
            ! $this->cacheControl
            && ! $this->has('ETag')
            && ! $this->has('Last-Modified')
            && ! $this->has('Expires')
        ) {
            return 'no-cache';
        }

        if (! $this->cacheControl) {
            return 'private, must-revalidate';
        }

        $header = $this->getCacheControlHeader();
        return (isset($this->cacheControl['public']) || isset($this->cacheControl['private']))
            ? $header
            : (isset($this->cacheControl['s-maxage']) ? $header : $header.', private');
    }
}
