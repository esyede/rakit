<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class DatabaseException extends \Exception
{
    /**
     * Contains the query that caused the error.
     *
     * @var string
     */
    protected $query;

    /**
     * Contains the bindings used.
     *
     * @var array
     */
    protected $bindings;

    /**
     * Contains the raw exception data.
     *
     * @var \Exception
     */
    protected $inner;

    /**
     * Constructor.
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
     * Get the query that caused the error.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the bindings used.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get the formatted query with bindings in place.
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
     * Get the raw exception data.
     *
     * @return \Throwable|\Exception|null
     */
    public function getInner()
    {
        return $this->inner;
    }
}
