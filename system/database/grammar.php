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

        return $this->wrap($prefix . $table);
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

        if (false !== stripos((string) $value, ' as ')) {
            $segments = explode(' ', $value);
            return sprintf('%s AS %s', $this->wrap($segments[0]), $this->wrap($segments[2]));
        }

        $segments = explode('.', $value);
        $wrapped = [];

        foreach ($segments as $key => $value) {
            $wrapped[] = (0 === $key && count($segments) > 1)
                ? $this->wrap_table($value)
                : $this->wrap_value($value);
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
        return ('*' === $value) ? '*' : sprintf($this->wrapper, $value);
    }

    /**
     * Create a comma-separated list of parameter place-holders.
     *
     * <code>
     *
     *      // Returning '?, ?, ?', which can be used as place-holders
     *      $parameters = $grammar->parameterize([1, 2, 3]);
     *
     *      // Returning '?, "Budi"' because a raw query is used
     *      $parameters = $grammar->parameterize([1, DB::raw('Budi')]);
     *
     * </code>
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
     * <code>
     *
     *      // Returning a '?' as a place-holder
     *      $value = $grammar->parameter('Budi Purnomo');
     *
     *      // Returning 'Budi Purnomo' because a raw query is used
     *      $value = $grammar->parameter(DB::raw('Budi Purnomo'));
     *
     * </code>
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
     * <code>
     *
     *      // Returning '"Budi", "Purnomo"' when the wrapper is '"%s"'
     *      $columns = $grammar->columnize(['Budi', 'Purnomo']);
     *
     * </code>
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
