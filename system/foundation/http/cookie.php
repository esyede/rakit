<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct access.');

class Cookie
{
    /**
     * The cookie name.
     *
     * @var string
     */
    protected $name;

    /**
     * The cookie value.
     *
     * @var string
     */
    protected $value;

    /**
     * The cookie domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * The cookie expiration time.
     *
     * @var int
     */
    protected $expire;

    /**
     * The cookie path.
     *
     * @var string
     */
    protected $path;

    /**
     * Whether the cookie is secure.
     *
     * @var bool
     */
    protected $secure;

    /**
     * Whether the cookie is HTTP only.
     *
     * @var bool
     */
    protected $httpOnly;

    /**
     * The cookie SameSite attribute.
     *
     * @var string
     */
    protected $sameSite;

    /**
     * Constructor.
     *
     * @param string               $name
     * @param string               $value
     * @param int|string|\DateTime $expire
     * @param string               $path
     * @param string               $domain
     * @param bool                 $secure
     * @param bool                 $httpOnly
     * @param string               $sameSite
     */
    public function __construct($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true, $sameSite = 'lax')
    {
        if (preg_match('/[=,; \t\r\n\013\014]/', $name)) {
            throw new \InvalidArgumentException(sprintf("The cookie name '%s' contains invalid characters.", $name));
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        // The characters setcookie() refuses, for the same reason: a separator or line
        // break ends the attribute early. Checked here so it also holds for workers.
        if (! is_null($path) && preg_match('/[,; \t\r\n\013\014]/', $path)) {
            throw new \InvalidArgumentException(sprintf("The cookie path '%s' contains invalid characters.", $path));
        }

        if (! is_null($domain) && preg_match('/[,; \t\r\n\013\014]/', $domain)) {
            throw new \InvalidArgumentException(sprintf("The cookie domain '%s' contains invalid characters.", $domain));
        }

        if ($expire instanceof \DateTime || $expire instanceof \DateTimeInterface) {
            $expire = $expire->format('U');
        } elseif (! is_numeric($expire)) {
            $expire = strtotime((string) $expire);

            if (false === $expire || -1 === $expire) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }

        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->expire = (int) $expire;
        $this->path = empty($path) ? '/' : $path;
        $this->secure = (bool) $secure;
        $this->httpOnly = (bool) $httpOnly;

        if (! in_array(strtolower((string) $sameSite), ['lax', 'strict', 'none'])) {
            throw new \InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }

        $this->sameSite = $sameSite;
    }

    /**
     * Return cookie as string.
     *
     * @return string
     */
    public function __toString()
    {
        $str = urlencode($this->getName()).'=';
        $str .= ('' === (string) $this->getValue())
            ? 'deleted; expires='.gmdate('D, d-M-Y H:i:s T', time() - 31536001)
            : urlencode($this->getValue()).((0 !== $this->getExpiresTime()) ? '; expires='.gmdate('D, d-M-Y H:i:s T', (int) $this->getExpiresTime()) : '');
        $str .= ('/' !== $this->path) ? '; path='.$this->path : '';
        $str .= (null !== $this->getSameSite()) ? '; samesite='.$this->getSameSite() : '';
        $str .= (null !== $this->getDomain()) ? '; domain='.$this->getDomain() : '';
        $str .= (true === $this->isSecure()) ? '; secure' : '';
        $str .= (true === $this->isHttpOnly()) ? '; httponly' : '';

        return $str;
    }

    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the cookie value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the cookie domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Get the cookie expiration time.
     *
     * @return int
     */
    public function getExpiresTime()
    {
        return $this->expire;
    }

    /**
     * Get the cookie path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Check if the cookie may only be sent over a secure HTTPS connection.
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * Check if cookie can only be accessed via HTTP protocol.
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
     * Check if the cookie has been cleared.
     *
     * @return bool
     */
    public function isCleared()
    {
        return 0 !== $this->expire && $this->expire < time();
    }

    /**
     * Get the SameSite attribute.
     *
     * @return string
     */
    public function getSameSite()
    {
        return $this->sameSite;
    }
}
