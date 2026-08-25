<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

abstract class Grammar
{
    /**
     * Contains the wrapper format for keyword identifiers.
     *
     * @var string
     */
    protected $wrapper = '"%s"';

    /**
     * Contains the database connection instance.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Wrap table name in keyword identifier.
     *
     * @param string $table
     *
     * @return string
     */
    public function wrap_table($table)
    {
        if ($table instanceof Expression) {
            return $this->wrap($table);
        }

        $prefix = '';

        if (isset($this->connection->config['prefix'])) {
            $prefix = $this->connection->config['prefix'];
        }

        return $this->wrap($prefix.$table);
    }

    /**
     * Wrap value in keyword identifier.
     *
     * @param string $value
     *
     * @return string
     */
    public function wrap($value)
    {
        if ($value instanceof Expression) {
            return $value->get();
        }

        if (preg_match('/^(.+?)\s+as\s+(.+)$/i', (string) $value, $matches)) {
            return sprintf('%s AS %s', $this->wrap(trim($matches[1])), $this->wrap(trim($matches[2])));
        }

        $segments = explode('.', $value);
        $wrapped = [];

        foreach ($segments as $key => $value) {
            $wrapped[] = (0 === $key && count($segments) > 1) ? $this->wrap_table($value) : $this->wrap_value($value);
        }

        return implode('.', $wrapped);
    }

    /**
     * Wrap a single string value in keyword identifier.
     *
     * @param string $value
     *
     * @return string
     */
    protected function wrap_value($value)
    {
        if ('*' === $value) {
            return '*';
        }

        // Escape the closing identifier character by doubling it. That is the
        // escape form for every wrapper in use here: '"' -> '""' (ANSI/sqlite/
        // postgres), '`' -> '``' (mysql) and ']' -> ']]' (sqlserver).
        $quote = substr($this->wrapper, -1);

        return sprintf($this->wrapper, str_replace($quote, $quote.$quote, (string) $value));
    }

    /**
     * Create a comma-separated list of parameter place-holders.
     *
     * @param array $values
     *
     * @return string
     */
    final public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * Get the parameter place-holder for a value.
     *
     * @param mixed $value
     *
     * @return string
     */
    final public function parameter($value)
    {
        return ($value instanceof Expression) ? $value->get() : '?';
    }

    /**
     * Create a comma-separated list of wrapped column names.
     *
     * @param array $columns
     *
     * @return string
     */
    final public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }
}
