<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class Postgres extends Grammar
{
    /**
     * Row id used to update or delete joined rows through a subquery.
     *
     * @var string
     */
    protected $row_key = 'ctid';

    /**
     * Compile the SQL to insert a new record and get the ID of the inserted record.
     *
     * @param Query  $query
     * @param array  $values
     * @param string $column
     *
     * @return string
     */
    public function insert_get_id(Query $query, array $values, $column)
    {
        return $this->insert($query, $values).' RETURNING '.$this->wrap($column);
    }

    /**
     * Compile an INSERT that updates the records already there.
     *
     * @param Query $query
     * @param array $values
     * @param array $unique_by
     * @param array $update
     *
     * @return string
     */
    public function upsert(Query $query, array $values, array $unique_by, array $update)
    {
        return $this->upsert_on_conflict($query, $values, $unique_by, $update);
    }

    /**
     * Compile an INSERT statement that silently skips clashing records.
     *
     * @param Query $query
     * @param array $values
     *
     * @return string
     */
    public function insert_ignore(Query $query, array $values)
    {
        return $this->insert($query, $values) . ' ON CONFLICT DO NOTHING';
    }

    /**
     * Compile a date function on a wrapped column; PostgreSQL only has EXTRACT().
     *
     * @param string $type
     * @param string $column
     *
     * @return string
     */
    public function date_function($type, $column)
    {
        if ('DATE' === $type || 'TIME' === $type) {
            return 'CAST('.$column.' AS '.$type.')';
        }

        return 'EXTRACT('.$type.' FROM '.$column.')';
    }
}
