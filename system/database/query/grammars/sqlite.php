<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;
use System\Database\Expression;

class SQLite extends Grammar
{
    /**
     * Compile the "order by" portions of the query.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function orderings(Query $query)
    {
        if (empty($query->orderings)) {
            return '';
        }

        $sql = [];

        foreach ($query->orderings as $ordering) {
            $direction = strtoupper((string) $ordering['direction']);

            // Raw ordering is left alone: COLLATE has to come before ASC / DESC.
            if ($ordering['column'] instanceof Expression) {
                $sql[] = rtrim($this->wrap($ordering['column']) . ' ' . $direction);
                continue;
            }

            $sql[] = rtrim($this->wrap($ordering['column']) . ' COLLATE NOCASE ' . $direction);
        }

        return 'ORDER BY '.implode(', ', $sql);
    }

    /**
     * Compile the INSERT statement, several records in one query.
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
            $columns[] = '? AS '.$this->wrap($column);
        }

        $columns = array_fill(0, count($values), implode(', ', $columns));

        // UNION ALL, since a plain UNION sorts the rows and drops the identical ones.
        return 'INSERT INTO '.$table.' ('.$names.') SELECT '.implode(' UNION ALL SELECT ', $columns);
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
        return preg_replace('/^INSERT INTO /', 'INSERT OR IGNORE INTO ', $this->insert($query, $values), 1);
    }

    /**
     * Compile a date function on a wrapped column. SQLite only has strftime(), whose
     * zero padded text is cast to an integer so it can equal 1.
     *
     * @param string $type
     * @param string $column
     *
     * @return string
     */
    public function date_function($type, $column)
    {
        $formats = ['DATE' => '%Y-%m-%d', 'TIME' => '%H:%M:%S', 'DAY' => '%d', 'MONTH' => '%m', 'YEAR' => '%Y'];
        $sql = "strftime('".$formats[$type]."', ".$column.')';

        return ('DATE' === $type || 'TIME' === $type) ? $sql : 'CAST('.$sql.' AS INTEGER)';
    }

    /**
     * Compile the row locking clause. SQLite locks the whole file, so there is none.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function lock(Query $query)
    {
        return '';
    }
}
