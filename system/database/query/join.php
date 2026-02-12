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
}
