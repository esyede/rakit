<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Header implements \IteratorAggregate, \Countable
{
    protected $headers;
    protected $cacheControl;

    /**
     * Konstruktor.
     *
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        $this->cacheControl = [];
        $this->headers = [];

        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Return data header dalam bentuk string.
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->headers) {
            return '';
        }

        $max = max(array_map(function ($key) {
            return mb_strlen((string) $key, '8bit');
        }, array_keys($this->headers))) + 1;

        ksort($this->headers);

        $content = '';

        foreach ($this->headers as $name => $values) {
            $name = $this->standardizeKey($name);

            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Mereturn seluruh data header.
     *
     * @return array
     */
    public function all()
    {
        return $this->headers;
    }

    /**
     * Meretrn seluruh key header.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->headers);
    }

    /**
     * Ganti seluruh header dengan yang baru.
     *
     * @param array $headers
     */
    public function replace(array $headers = [])
    {
        $this->headers = [];
        $this->add($headers);
    }

    /**
     * Tambahkan data header baru.
     *
     * @param array $headers
     */
    public function add(array $headers)
    {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Mereturn value header berdasarkan key yang diberikan.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $first
     *
     * @return string|array
     */
    public function get($key, $default = null, $first = true)
    {
        $key = $this->standardizeKey($key);

        if (!array_key_exists($key, $this->headers)) {
            if (null === $default) {
                return $first ? null : [];
            }

            return $first ? $default : [$default];
        }

        if ($first) {
            return count($this->headers[$key]) ? $this->headers[$key][0] : $default;
        }

        return $this->headers[$key];
    }

    /**
     * Set data header berdasarkan key.
     *
     * @param string       $key
     * @param string|array $values
     * @param bool         $replace
     */
    public function set($key, $values, $replace = true)
    {
        $key = $this->standardizeKey($key);
        $values = array_values((array) $values);
        $this->headers[$key] = (true === $replace || !isset($this->headers[$key]))
            ? $values
            : array_merge($this->headers[$key], $values);

        if ('Cache-Control' === $key) {
            $this->cacheControl = $this->parseCacheControl($values[0]);
        }
    }

    /**
     * Periksa ada tidaknya suatu header.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $key = $this->standardizeKey($key);
        return array_key_exists($key, $this->headers);
    }

    /**
     * Mereturn TRUE jika header mengandung value yang diberikan.
     *
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public function contains($key, $value)
    {
        return in_array($value, $this->get($key, null, false));
    }

    /**
     * Hapus header berdasarkan key-nya.
     *
     * @param string $key
     */
    public function remove($key)
    {
        $key = $this->standardizeKey($key);

        unset($this->headers[$key]);

        if ('Cache-Control' === $key) {
            $this->cacheControl = [];
        }
    }

    /**
     * Mereturn value header yang dikonversikan ke bentuk tanggal.
     *
     * @param string    $key
     * @param \DateTime $default
     *
     * @return \DateTime|null
     */
    public function getDate($key, \DateTime $default = null)
    {
        if (null === ($value = $this->get($key))) {
            return $default;
        }

        if (false === ($date = \DateTime::createFromFormat(DATE_RFC2822, $value))) {
            throw new \RuntimeException(sprintf("The '%s' HTTP header is not parseable (%s).", $key, $value));
        }

        return $date;
    }

    /**
     * Tambahkan header Cache-Control.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function addCacheControlDirective($key, $value = true)
    {
        $this->cacheControl[$key] = $value;
        $this->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Periksa ada tidaknya suatu header Cache-Control.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->cacheControl);
    }

    /**
     * Ambil penunjuk Cache-Control berdasarkan key-nya.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->cacheControl) ? $this->cacheControl[$key] : null;
    }

    /**
     * Hapus penunjuk Cache-Control berdasarkan key-nya.
     *
     * @param string $key
     */
    public function removeCacheControlDirective($key)
    {
        unset($this->cacheControl[$key]);
        $this->set('Cache-Control', $this->getCacheControlHeader());
    }

    /**
     * Mereturn array iterator untuk data header.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }

    /**
     * Hitung jumlah seluruh header.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->headers);
    }

    /**
     * Ambil seluruh data Cache-Control.
     *
     * @return string
     */
    protected function getCacheControlHeader()
    {
        ksort($this->cacheControl);

        $parts = [];

        foreach ($this->cacheControl as $key => $value) {
            if (true === $value) {
                $parts[] = $key;
            } else {
                if (preg_match('/[^a-zA-Z0-9._-]/', $value)) {
                    $value = '"' . $value . '"';
                }

                $parts[] = $key . '=' . $value;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Parse header Cache-Control.
     *
     * @param string $header
     *
     * @return array
     */
    protected function parseCacheControl($header)
    {
        preg_match_all(
            '/([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?/',
            $header,
            $matches,
            PREG_SET_ORDER
        );

        $parsed = [];

        foreach ($matches as $match) {
            $parsed[strtolower((string) $match[1])] = isset($match[3])
                ? $match[3]
                : (isset($match[2]) ? $match[2] : true);
        }

        return $parsed;
    }

    /**
     * Standarisasi nama header
     *
     * @param string $key
     *
     * @return string
     */
    protected static function standardizeKey($key)
    {
        $key = strtr(strtolower((string) $key), '_', '-');
        return str_replace(' ', '-', ucwords(strtr($key, '-', ' ')));
    }
}
