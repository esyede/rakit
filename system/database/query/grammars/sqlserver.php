<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class SQLServer extends Grammar
{
    /**
     * Contains the wrapper format.
     *
     * @var string
     */
    protected $wrapper = '[%s]';

    /**
     * Format for datetime columns.
     *
     * @var string
     */
    public $datetime = 'Y-m-d H:i:s.000';

    /**
     * Compile an INSERT that updates the records already there, as a MERGE.
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
        $columns = array_keys(reset($values));
        $source = $this->wrap('rakit_source');
        $table = $this->wrap_table($query->from);
        $rows = implode(', ', array_fill(0, count($values), '('.$this->parameterize(reset($values)).')'));
        $on = [];
        $sets = [];

        foreach ($unique_by as $column) {
            $on[] = $source.'.'.$this->wrap($column).' = '.$table.'.'.$this->wrap($column);
        }

        foreach ($update as $column) {
            $sets[] = $this->wrap($column).' = '.$source.'.'.$this->wrap($column);
        }

        return 'MERGE '.$table.' USING (VALUES '.$rows.') '.$source.' ('.$this->columnize($columns).')'
            .' ON '.implode(' AND ', $on)
            .' WHEN MATCHED THEN UPDATE SET '.implode(', ', $sets)
            .' WHEN NOT MATCHED THEN INSERT ('.$this->columnize($columns).') VALUES ('
            .implode(', ', array_map(function ($column) use ($source) {
                return $source.'.'.$this->wrap($column);
            }, $columns)).');';
    }

    /**
     * Compile the SELECT statement.
     *
     * @param Query $query
     *
     * @return string
     */
    public function select(Query $query)
    {
        $sql = parent::components($query);

        if ($query->offset > 0) {
            return $this->ansi_offset($query, $sql);
        }

        return $this->concatenate($sql);
    }

    /**
     * Compile the SELECT clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function selects(Query $query)
    {
        if (! is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'SELECT DISTINCT ' : 'SELECT ';

        if ($query->limit > 0 && $query->offset <= 0) {
            $select .= 'TOP '.(int) $query->limit.' ';
        }

        return $select.$this->columnize(empty($query->selects) ? ['*'] : $query->selects);
    }

    /**
     * Make an ANSI-compliant OFFSET clause.
     *
     * @param Query $query
     * @param array $components
     *
     * @return string
     */
    protected function ansi_offset(Query $query, $components)
    {
        if (empty($components['orderings'])) {
            $components['orderings'] = 'ORDER BY (SELECT 0)';
        }

        $orderings = $components['orderings'];
        $components['selects'] .= ', ROW_NUMBER() OVER ('.$orderings.') AS RowNum';

        unset($components['orderings']);

        $start = (int) $query->offset + 1;

        if ($query->limit > 0) {
            $finish = (int) $query->offset + (int) $query->limit;
            $constraint = 'BETWEEN '.$start.' AND '.$finish;
        } else {
            $constraint = '>= '.$start;
        }

        $sql = $this->concatenate($components);

        return 'SELECT * FROM ('.$sql.') AS TempTable WHERE RowNum '.$constraint;
    }

    /**
     * Compile the LIMIT clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function limit(Query $query)
    {
        return '';
    }

    /**
     * Compile the OFFSET clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function offset(Query $query)
    {
        return '';
    }

    /**
     * Compile an UPDATE with joins: UPDATE t SET ... FROM t INNER JOIN j ON ... WHERE ...
     *
     * @param Query $query
     * @param array $values
     *
     * @return string
     */
    protected function update_with_joins(Query $query, array $values)
    {
        return trim('UPDATE '.$this->wrap_table($this->table_alias($query->from))
            .' SET '.$this->update_columns($values, false)
            .' FROM '.$this->wrap_table($query->from).' '.$this->joins($query).' '.$this->wheres($query));
    }

    /**
     * Get the UPDATE bindings; the SET list comes before the joins here.
     *
     * @param Query $query
     * @param array $values
     * @param array $clauses
     *
     * @return array
     */
    public function update_bindings(Query $query, array $values, array $clauses)
    {
        return array_merge(array_values($values), $clauses['join'], $clauses['where']);
    }

    /**
     * Get the sql used to order the results randomly.
     *
     * @param string $seed
     *
     * @return string
     */
    public function random($seed = '')
    {
        return 'NEWID()';
    }

    /**
     * Compile a date function on a wrapped column. SQL Server has no DATE() or TIME().
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

        return parent::date_function($type, $column);
    }

    /**
     * Compile the FROM clause, with the lock attached as a table hint.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function from(Query $query)
    {
        $from = parent::from($query);

        if (is_null($query->lock)) {
            return $from;
        }

        if (is_string($query->lock)) {
            return $from . ' ' . $query->lock;
        }

        if ('SKIP LOCKED' === $query->lock_option) {
            return $from . ($query->lock
                ? ' WITH (ROWLOCK, UPDLOCK, READPAST)'
                : ' WITH (ROWLOCK, REPEATABLEREAD, READPAST)');
        }

        $nowait = ('NOWAIT' === $query->lock_option) ? ', NOWAIT' : '';

        return $from . ($query->lock
            ? ' WITH (ROWLOCK, UPDLOCK, HOLDLOCK' . $nowait . ')'
            : ' WITH (ROWLOCK, HOLDLOCK' . $nowait . ')');
    }

    /**
     * Compile the row locking clause; nothing, the FROM clause already carries it.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function lock(Query $query)
    {
        return '';
    }

    /**
     * Compile the statement that opens a savepoint.
     *
     * @param string $name
     *
     * @return string
     */
    public function savepoint($name)
    {
        return 'SAVE TRANSACTION ' . $name;
    }

    /**
     * Compile the release of a savepoint. SQL Server has none: it ends with its transaction.
     *
     * @param string $name
     *
     * @return string
     */
    public function release_savepoint($name)
    {
        return '';
    }

    /**
     * Compile the statement that rolls back to a savepoint.
     *
     * @param string $name
     *
     * @return string
     */
    public function rollback_savepoint($name)
    {
        return 'ROLLBACK TRANSACTION ' . $name;
    }
}
