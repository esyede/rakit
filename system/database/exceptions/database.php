<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class DatabaseException extends \Exception
{
    /**
     * Query yang menyebabkan error.
     *
     * @var string
     */
    protected $query;

    /**
     * Bindings yang digunakan dalam query.
     *
     * @var array
     */
    protected $bindings;

    /**
     * Berisi data exception mentah.
     *
     * @var \Exception
     */
    protected $inner;

    /**
     * Buat instance exception baru.
     *
     * @param string                     $message
     * @param string                     $query
     * @param array                      $bindings
     * @param int                        $code
     * @param \Throwable|\Exception|null $previous
     * @param \Throwable|\Exception|null $inner
     */
    public function __construct($message = '', $query = '', array $bindings = [], $code = 0, $previous = null, $inner = null)
    {
        $this->query = $query;
        $this->bindings = $bindings;
        $this->inner = $inner ?: $previous;

        if (PHP_VERSION_ID >= 70000) {
            if ($this->inner instanceof \Exception || $this->inner instanceof \Throwable) {
                $message = $this->inner->getMessage() . ' (SQL: ' . $this->getFormattedQuery() . ')';
            }
        } elseif ($this->inner instanceof \Exception) {
            $message = $this->inner->getMessage() . ' (SQL: ' . $this->getFormattedQuery() . ')';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Ambil query yang menyebabkan error.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Ambil bindings yang digunakan.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Format query dengan bindings untuk debugging.
     *
     * @return string
     */
    public function getFormattedQuery()
    {
        $query = $this->query;
        $bindings = $this->bindings;

        foreach ($bindings as $binding) {
            $pos = strpos($query, '?');

            if (false !== $pos) {
                $query = substr_replace($query, (is_string($binding) ? "'$binding'" : $binding), $pos, 1);
            }
        }

        return $query;
    }

    /**
     * Ambil data exception mentah.
     *
     * @return \Throwable|\Exception|null
     */
    public function getInner()
    {
        return $this->inner;
    }
}
