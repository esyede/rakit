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
     * Buat instance exception baru.
     *
     * @param string     $message
     * @param string     $query
     * @param array      $bindings
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $query = '', array $bindings = [], $code = 0, \Exception $previous = null)
    {
        $this->query = $query;
        $this->bindings = $bindings;

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
                $value = is_string($binding) ? "'$binding'" : $binding;
                $query = substr_replace($query, $value, $pos, 1);
            }
        }

        return $query;
    }
}
