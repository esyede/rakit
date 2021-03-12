<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Parameter implements \IteratorAggregate, \Countable
{
    /**
     * Berisi list parameter.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Konstruktor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Ambil seluruh data parameter.
     *
     * @return array
     */
    public function all()
    {
        return $this->parameters;
    }

    /**
     * Ambil seluruh key parameter.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->parameters);
    }

    /**
     * Ganti parameter saat ini dengan yang baru.
     *
     * @param array
     */
    public function replace(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Tambahkan parameter.
     *
     * @param array $parameters
     */
    public function add(array $parameters = [])
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    /**
     * Ambil data parameter berdasarkan nama.
     *
     * @param string $path
     * @param mixed  $default
     * @param bool   $deep
     *
     * @return mixed
     */
    public function get($path, $default = null, $deep = false)
    {
        if (! $deep || false === ($pos = strpos($path, '['))) {
            return array_key_exists($path, $this->parameters)
                ? $this->parameters[$path]
                : $default;
        }

        $root = substr($path, 0, $pos);

        if (! array_key_exists($root, $this->parameters)) {
            return $default;
        }

        $value = $this->parameters[$root];
        $currentKey = null;

        for ($i = $pos, $count = strlen($path); $i < $count; ++$i) {
            $char = $path[$i];

            if ('[' === $char) {
                if (null !== $currentKey) {
                    throw new \InvalidArgumentException(
                        sprintf("Malformed path. Unexpected '[' at position %s", $i)
                    );
                }

                $currentKey = '';
            } elseif (']' === $char) {
                if (null === $currentKey) {
                    throw new \InvalidArgumentException(
                        sprintf("Malformed path. Unexpected ']' at position %s", $i)
                    );
                }

                if (! is_array($value) || ! array_key_exists($currentKey, $value)) {
                    return $default;
                }

                $value = $value[$currentKey];
                $currentKey = null;
            } else {
                if (null === $currentKey) {
                    throw new \InvalidArgumentException(
                        sprintf("Malformed path. Unexpected '%s' at position %s", $char, $i)
                    );
                }

                $currentKey .= $char;
            }
        }

        if (null !== $currentKey) {
            throw new \InvalidArgumentException("Malformed path. Path must ended with ']'.");
        }

        return $value;
    }

    /**
     * Set sebuah parameter berdasarkan nama.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * Cek apakah parameter ada.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Hapus sebuah parameter.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->parameters[$key]);
    }

    /**
     * Mereturn hanya karakter alfabet milik suatu parameter.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $deep
     *
     * @return string
     */
    public function getAlpha($key, $default = '', $deep = false)
    {
        return preg_replace('/[^[:alpha:]]/', '', $this->get($key, $default, $deep));
    }

    /**
     * Mereturn karakter alfabet dan angka milik suatu parameter.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $deep
     *
     * @return string
     */
    public function getAlnum($key, $default = '', $deep = false)
    {
        return preg_replace('/[^[:alnum:]]/', '', $this->get($key, $default, $deep));
    }

    /**
     * Mereturn hanya karakter angka milik suatu parameter.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $deep
     *
     * @return string
     */
    public function getDigits($key, $default = '', $deep = false)
    {
        $digits = $this->filter($key, $default, $deep, FILTER_SANITIZE_NUMBER_INT);
        // Lewati karakter - dan + karena karakter ini boleh dipkai oleh filter
        $digits = str_replace(['-', '+'], '', $digits);

        return $digits;
    }

    /**
     * Mereturn value milik parameter yang telah dikonversikan ke integer.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $deep
     *
     * @return int
     */
    public function getInt($key, $default = 0, $deep = false)
    {
        return (int) $this->get($key, $default, $deep);
    }

    /**
     * Filter parameter.
     *
     * @param string $key
     * @param mixed  $default
     * @param bool   $deep
     * @param int    $filter
     * @param mixed  $options
     *
     * @return mixed
     */
    public function filter(
        $key,
        $default = null,
        $deep = false,
        $filter = FILTER_DEFAULT,
        $options = []
    ) {
        $value = $this->get($key, $default, $deep);

        if (! is_array($options) && $options) {
            $options = ['flags' => $options];
        }

        if (is_array($value) && ! isset($options['flags'])) {
            $options['flags'] = FILTER_REQUIRE_ARRAY;
        }

        return filter_var($value, $filter, $options);
    }

    /**
     * Mereturn array iterator untuk data parameter.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }

    /**
     * Hitung jumlah seluruh parameter.
     *
     * @return int
     */
    public function count()
    {
        return count($this->parameters);
    }
}
