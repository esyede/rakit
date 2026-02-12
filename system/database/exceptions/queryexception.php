<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class QueryException extends \PDOException
{
    /**
     * Contains the name of the database connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * Contains the SQL for the query.
     *
     * @var string
     */
    protected $sql;

    /**
     * Contains the bindings for the query.
     *
     * @var array
     */
    protected $bindings;

    /**
     * Constructor.
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
     * Format the error message.
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
     * Get the name of the database connection.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Get the SQL for the query.
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the bindings for the query.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Substitute the bindings into the SQL query.
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
