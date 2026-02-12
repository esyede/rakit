<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class Postgres extends Grammar
{
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
        return $this->insert($query, $values) . ' RETURNING ' . $column;
    }
}
