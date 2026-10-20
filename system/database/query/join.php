<?php

namespace System\Database\Query;

defined('DS') or exit('No direct access.');

class Join
{
    /**
     * Contains the type of join.
     *
     * @var string
     */
    public $type;

    /**
     * Contains the table to join.
     *
     * @var string
     */
    public $table;

    /**
     * Contains the join clauses.
     *
     * @var array
     */
    public $clauses = [];

    /**
     * Contains the values bound by where() clauses, in compiled order.
     *
     * @var array
     */
    public $bindings = [];

    /**
     * Contains the list of valid SQL operators for a join clause.
     *
     * @var array
     */
    public $operators = ['=', '<', '>', '<=', '>=', '<>', '!=', '<=>'];

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $table
     */
    public function __construct($type, $table)
    {
        $this->type = $type;
        $this->table = $table;
    }

    /**
     * Add an ON clause to the join query.
     *
     * @param string $column1
     * @param string $operator
     * @param string $column2
     * @param string $connector
     *
     * @return Join
     */
    public function on($column1, $operator, $column2, $connector = 'AND')
    {
        if (! in_array(strtolower((string) $operator), $this->operators)) {
            throw new \InvalidArgumentException(sprintf('Unsupported SQL operator: %s', $operator));
        }

        $this->clauses[] = compact('column1', 'operator', 'column2', 'connector');
        return $this;
    }

    /**
     * Add an OR ON clause to the join query.
     *
     * @param string $column1
     * @param string $operator
     * @param string $column2
     *
     * @return Join
     */
    public function or_on($column1, $operator, $column2)
    {
        return $this->on($column1, $operator, $column2, 'OR');
    }

    /**
     * Add a WHERE condition to the join, comparing a column against a bound value.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Join
     */
    public function where($column, $operator = null, $value = null, $connector = 'AND')
    {
        $operators = array_merge($this->operators, ['like', 'not like']);

        if (! in_array(strtolower((string) $operator), $operators) && null === $value) {
            $value = $operator;
            $operator = '=';
        }

        if (! in_array(strtolower((string) $operator), $operators)) {
            throw new \InvalidArgumentException(sprintf('Unsupported SQL operator: %s', $operator));
        }

        $this->clauses[] = compact('column', 'operator', 'value', 'connector');

        if (null !== $value) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    /**
     * Add an OR WHERE condition to the join.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return Join
     */
    public function or_where($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }
}
