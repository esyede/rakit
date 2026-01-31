<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class QueryException extends \PDOException
{
    /**
     * Berisi nama koneksi database.
     *
     * @var string
     */
    protected $connection;

    /**
     * Berisi SQL untuk query.
     *
     * @var string
     */
    protected $sql;

    /**
     * Berisi bindings untuk query.
     *
     * @var array
     */
    protected $bindings;

    /**
     * Buat instance baru dari QueryException.
     *
     * @param string                $connection
     * @param string                $sql
     * @param array                 $bindings
     * @param \Throwable|\Exception $previous
     */
    public function __construct($connection, $sql, array $bindings, $previous)
    {
        $this->connection = $connection;
        $this->sql = $sql;
        $this->bindings = $bindings;
        parent::__construct($this->formatMessage($connection, $sql, $bindings, $previous), 0, $previous);
    }

    /**
     * Format pesan error.
     *
     * @param string                $connection
     * @param string                $sql
     * @param array                 $bindings
     * @param \Throwable|\Exception $previous
     *
     * @return string
     */
    protected function formatMessage($connection, $sql, array $bindings, $previous)
    {
        $query = $this->substituteBindings($sql, $bindings);
        return $previous->getMessage() . ' (Connection: ' . $connection . ', SQL: ' . $query . ')';
    }

    /**
     * Ambil nama koneksi database.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Ambil SQL untuk query.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Ambil bindings untuk query.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Substitute bindings ke dalam SQL untuk debugging.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return string
     */
    protected function substituteBindings($sql, array $bindings)
    {
        foreach ($bindings as $binding) {
            $sql = preg_replace('/\?/', (is_string($binding) ? "'$binding'" : $binding), $sql, 1);
        }

        return $sql;
    }
}
