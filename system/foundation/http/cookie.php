<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Cookie
{
    protected $name;
    protected $value;
    protected $domain;
    protected $expire;
    protected $path;
    protected $secure;
    protected $httpOnly;
    protected $sameSite;

    const SAMESITE_NONE = 'none';
    const SAMESITE_LAX = 'lax';
    const SAMESITE_STRICT = 'strict';

    /**
     * Konstruktor.
     *
     * @param string               $name
     * @param string               $value
     * @param int|string|\DateTime $expire
     * @param string               $path
     * @param string               $domain
     * @param bool                 $secure
     * @param bool                 $httpOnly
     */
    public function __construct(
        $name,
        $value = null,
        $expire = 0,
        $path = '/',
        $domain = null,
        $secure = false,
        $httpOnly = true,
        $sameSite = 'lax'
    ) {
        if (preg_match('/[=,; \t\r\n\013\014]/', $name)) {
            $exception = "The cookie name '%s' contains invalid characters.";
            throw new \InvalidArgumentException(sprintf($exception, $name));
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        if ($expire instanceof \DateTime) {
            $expire = $expire->format('U');
        } elseif (! is_numeric($expire)) {
            $expire = strtotime($expire);

            if (false === $expire || -1 === $expire) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }

        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->expire = $expire;
        $this->path = empty($path) ? '/' : $path;
        $this->secure = (bool) $secure;
        $this->httpOnly = (bool) $httpOnly;

        if ('' === $sameSite) {
            $sameSite = null;
        } elseif (null !== $sameSite) {
            $sameSite = strtolower($sameSite);
        }

        $sameSites = [self::SAMESITE_LAX, self::SAMESITE_STRICT, self::SAMESITE_NONE, null];

        if (! in_array($sameSite, $sameSites, true)) {
            throw new \InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }

        $this->sameSite = $sameSite;
    }

    /**
     * Mereturn object cookie sebagai string.
     *
     * @return string
     */
    public function __toString()
    {
        $str = urlencode($this->getName()).'=';

        if ('' === (string) $this->getValue()) {
            $str .= 'deleted; expires='.gmdate('D, d-M-Y H:i:s T', time() - 31536001);
        } else {
            $str .= urlencode($this->getValue());

            if (0 !== $this->getExpiresTime()) {
                $str .= '; expires='.gmdate('D, d-M-Y H:i:s T', $this->getExpiresTime());
            }
        }

        if ('/' !== $this->path) {
            $str .= '; path='.$this->path;
        }

        if (null !== $this->getSameSite()) {
            $str .= '; samesite='.$this->getSameSite();
        }

        if (null !== $this->getDomain()) {
            $str .= '; domain='.$this->getDomain();
        }

        if (true === $this->isSecure()) {
            $str .= '; secure';
        }

        if (true === $this->isHttpOnly()) {
            $str .= '; httponly';
        }

        return $str;
    }

    /**
     * Ambil nama cookie.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Ambil value cookie.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Ambil domain cookie.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Ambil waktu kedaluwarsa cookie.
     *
     * @return int
     */
    public function getExpiresTime()
    {
        return $this->expire;
    }

    /**
     * Ambil path cookie di server.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Cek apakah cookie hanya boleh dikirimkan via HTTPS saja.
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * Cek apakah cookie hanya boleh tersedia via HTTP saja.
     *
     * @return bool
     *
     * @api
     */
    public function isHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * Cek apakah cookie sudah waktunya untuk dibersihkan.
     *
     * @return bool
     */
    public function isCleared()
    {
        return $this->expire < time();
    }

    /**
     * Ambil atribut samesite cookie.
     *
     * @return string
     */
    public function getSameSite()
    {
        return $this->sameSite;
    }
}
