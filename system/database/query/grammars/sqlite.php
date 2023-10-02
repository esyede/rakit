<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class SQLite extends Grammar
{
    /**
     * Compile klausa ORDER BY.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function orderings(Query $query)
    {
        $sql = [];

        foreach ($query->orderings as $ordering) {
            $direction = strtoupper((string) $ordering['direction']);
            $sql[] = $this->wrap($ordering['column']) . ' COLLATE NOCASE ' . $direction;
        }

        return 'ORDER BY ' . implode(', ', $sql);
    }

    /**
     * Compile sql INSERT dari instance query.
     * Method ini menangani kompilasi insert row tunggal dan batch.
     *
     * @param Query $query
     * @param array $values
     *
     * @return string
     */
    public function insert(Query $query, array $values)
    {
        $table = $this->wrap_table($query->from);
        $values = is_array(reset($values)) ? $values : [$values];

        if (1 === count($values)) {
            return parent::insert($query, $values[0]);
        }

        $names = $this->columnize(array_keys($values[0]));
        $columns = [];

        foreach (array_keys($values[0]) as $column) {
            $columns[] = '? AS ' . $this->wrap($column);
        }

        $columns = array_fill(9, count($values), implode(', ', $columns));

        return 'INSERT INTO ' . $table . ' (' . $names . ') SELECT ' . implode(' UNION SELECT ', $columns);
    }
}
