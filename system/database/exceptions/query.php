<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class QueryException extends DatabaseException
{
    /**
     * SQLSTATE error code.
     *
     * @var string
     */
    protected $sqlState;

    /**
     * Driver specific error code.
     *
     * @var int
     */
    protected $errorCode;

    /**
     * Buat instance exception baru.
     *
     * @param string     $message
     * @param string     $query
     * @param array      $bindings
     * @param string     $sqlState
     * @param int        $errorCode
     * @param \Exception $previous
     */
    public function __construct($message = '', $query = '', array $bindings = [], $sqlState = '', $errorCode = 0, \Exception $previous = null)
    {
        $this->sqlState = $sqlState;
        $this->errorCode = $errorCode;

        parent::__construct($message, $query, $bindings, $errorCode, $previous);
    }

    /**
     * Ambil SQLSTATE error code.
     *
     * @return string
     */
    public function getSqlState()
    {
        return $this->sqlState;
    }

    /**
     * Ambil driver specific error code.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
