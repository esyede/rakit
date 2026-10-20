<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;
use System\Database\Grammar as BaseGrammar;

class Grammar extends BaseGrammar
{
    /**
     * Format for date columns.
     *
     * @var string
     */
    public $datetime = 'Y-m-d H:i:s';

    /**
     * List of all components of a select statement.
     *
     * @var array
     */
    protected $components = [
        'aggregate',
        'selects',
        'from',
        'joins',
        'wheres',
        'groupings',
        'havings',
        'unions',
        'orderings',
        'limit',
        'offset',
        'lock',
    ];

    /**
     * Compile the SELECT statement.
     *
     * @param Query $query
     *
     * @return string
     */
    public function select(Query $query)
    {
        return $this->concatenate($this->components($query));
    }

    /**
     * Make array of sql segments from each query component.
     *
     * @param Query $query
     *
     * @return array
     */
    final protected function components(Query $query)
    {
        $sql = [];

        foreach ($this->components as $component) {
            if (! is_null($query->{$component}) || 'selects' === $component) {
                $sql[$component] = call_user_func([$this, $component], $query);
            }
        }

        return $sql;
    }

    /**
     * Concatenate the sql segments into a single string.
     *
     * @param array $components
     *
     * @return string
     */
    final protected function concatenate(array $components)
    {
        return implode(' ', array_filter($components, function ($value) {
            return '' !== (string) $value;
        }));
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

        $selects = empty($query->selects) ? ['*'] : $query->selects;
        return ($query->distinct ? 'SELECT DISTINCT ' : 'SELECT ').$this->columnize($selects);
    }

    /**
     * Compile the SELECT aggregate clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function aggregate(Query $query)
    {
        $aggregator = strtoupper((string) $query->aggregate['aggregator']);

        if (! in_array($aggregator, ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid aggregate function: %s', $query->aggregate['aggregator']));
        }

        $column = $this->columnize($query->aggregate['columns']);

        if ($query->distinct && '*' !== $column) {
            $column = 'DISTINCT '.$column;
        }

        return 'SELECT '.$aggregator.'('.$column.') AS '.$this->wrap('aggregate');
    }

    /**
     * Check a boolean connector: it goes into the SQL as written.
     *
     * @param string $connector
     *
     * @return string
     */
    protected function connector($connector)
    {
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $connector)));

        if (! in_array($upper, ['AND', 'OR', 'AND NOT', 'OR NOT'], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid boolean connector: %s', $connector));
        }

        return $upper;
    }

    /**
     * Check a join type: it goes into the SQL as written.
     *
     * @param string $type
     *
     * @return string
     */
    protected function join_type($type)
    {
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $type)));
        $types = ['INNER', 'LEFT', 'RIGHT', 'CROSS', 'FULL', 'LEFT OUTER', 'RIGHT OUTER', 'FULL OUTER'];

        if (! in_array($upper, $types, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid join type: %s', $type));
        }

        return $upper;
    }

    /**
     * Compile the FROM clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function from(Query $query)
    {
        return 'FROM '.$this->wrap_table($query->from);
    }

    /**\
     * Compile the JOIN clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function joins(Query $query)
    {
        $sql = [];

        foreach ($query->joins as $join) {
            $table = $this->wrap_table($join->table);
            $clauses = [];

            foreach ($join->clauses as $clause) {
                if (! array_key_exists('value', $clause)) {
                    $clauses[] = sprintf(
                        '%s %s %s %s',
                        $this->connector($clause['connector']),
                        $this->wrap($clause['column1']),
                        $clause['operator'],
                        $this->wrap($clause['column2'])
                    );
                } elseif (null === $clause['value']) {
                    $not = in_array($clause['operator'], ['!=', '<>'], true) ? 'NOT ' : '';
                    $clauses[] = $this->connector($clause['connector']).' '.$this->wrap($clause['column']).' IS '.$not.'NULL';
                } else {
                    $clauses[] = $this->connector($clause['connector']).' '.$this->wrap($clause['column']).' '
                        .$clause['operator'].' '.$this->parameter($clause['value']);
                }
            }

            if (0 === count($clauses)) {
                $sql[] = $this->join_type($join->type) . ' JOIN ' . $table;
                continue;
            }

            $clauses[0] = preg_replace('/^(AND|OR) /', '', $clauses[0]);
            $clauses = implode(' ', $clauses);
            $sql[] = $this->join_type($join->type) . ' JOIN ' . $table . ' ON ' . $clauses;
        }

        return implode(' ', $sql);
    }

    /**
     * Compile the WHERE clause.
     *
     * @param Query $query
     *
     * @return string
     */
    final protected function wheres(Query $query)
    {
        if (empty($query->wheres)) {
            return '';
        }

        $sql = [];

        foreach ($query->wheres as $where) {
            $sql[] = $this->connector($where['connector']).' '.$this->{$where['type']}($where);
        }

        return 'WHERE '.preg_replace('/AND |OR /i', '', implode(' ', $sql), 1);
    }

    /**
     * Compile the nested WHERE clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_nested($where)
    {
        return '('.substr((string) $this->wheres($where['query']), 6).')';
    }

    /**
     * Compile the negated nested WHERE clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_not_nested($where)
    {
        return 'NOT '.$this->where_nested($where);
    }

    /**
     * Compile the basic WHERE clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where($where)
    {
        $parameter = $this->parameter($where['value']);
        return $this->wrap($where['column']).' '.$where['operator'].' '.$parameter;
    }

    /**
     * Compile the WHERE IN clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_in($where)
    {
        if (empty($where['values'])) {
            return '0 = 1';
        }

        $parameters = $this->parameterize($where['values']);
        return $this->wrap($where['column']).' IN ('.$parameters.')';
    }

    /**
     * Compile the WHERE NOT IN clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_not_in($where)
    {
        if (empty($where['values'])) {
            return '1 = 1';
        }

        $parameters = $this->parameterize($where['values']);
        return $this->wrap($where['column']).' NOT IN ('.$parameters.')';
    }

    /**
     * Compile the WHERE IN clause with inlined integer values.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_integer_in_raw($where)
    {
        if (empty($where['values'])) {
            return '0 = 1';
        }

        return $this->wrap($where['column']).' IN ('.implode(', ', $where['values']).')';
    }

    /**
     * Compile the WHERE NOT IN clause with inlined integer values.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_integer_not_in_raw($where)
    {
        if (empty($where['values'])) {
            return '1 = 1';
        }

        return $this->wrap($where['column']).' NOT IN ('.implode(', ', $where['values']).')';
    }

    /**
     * Compile the WHERE IN clause with subquery.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_in_sub($where)
    {
        $query = '('.$where['query']->grammar->select($where['query']).')';
        return $this->wrap($where['column']).' IN '.$query;
    }

    /**
     * Compile the WHERE NOT IN clause with subquery.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_not_in_sub($where)
    {
        $query = '('.$where['query']->grammar->select($where['query']).')';
        return $this->wrap($where['column']).' NOT IN '.$query;
    }

    /**
     * Compile the WHERE EXISTS with subquery.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_exists($where)
    {
        $query = '('.$where['query']->grammar->select($where['query']).')';
        return 'EXISTS '.$query;
    }

    /**
     * Compile the WHERE NOT EXISTS with subquery.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_not_exists($where)
    {
        $query = '('.$where['query']->grammar->select($where['query']).')';
        return 'NOT EXISTS '.$query;
    }

    /**
     * Compile the WHERE BETWEEN clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_between($where)
    {
        $min = $this->parameter($where['min']);
        $max = $this->parameter($where['max']);
        return $this->wrap($where['column']).' BETWEEN '.$min.' AND '.$max;
    }

    /**
     * Compile the WHERE NOT BETWEEN clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_not_between($where)
    {
        $min = $this->parameter($where['min']);
        $max = $this->parameter($where['max']);
        return $this->wrap($where['column']).' NOT BETWEEN '.$min.' AND '.$max;
    }

    /**
     * Compile the WHERE NULL clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_null($where)
    {
        return $this->wrap($where['column']).' IS NULL';
    }

    /**
     * Compile the WHERE NOT NULL clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_not_null($where)
    {
        return $this->wrap($where['column']).' IS NOT NULL';
    }

    /**
     * Compile the WHERE column to column comparison clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_column($where)
    {
        return $this->wrap($where['column1']).' '.$where['operator'].' '.$this->wrap($where['column2']);
    }

    /**
     * Compile the raw WHERE clause.
     *
     * @param array $where
     *
     * @return string
     */
    protected function where_raw($where)
    {
        return $where['sql'];
    }

    /**
     * Compile a date function on a wrapped column, in MySQL spelling. Others override it.
     *
     * @param string $type
     * @param string $column
     *
     * @return string
     */
    public function date_function($type, $column)
    {
        return $type.'('.$column.')';
    }

    /**
     * Compile the GROUP BY clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function groupings(Query $query)
    {
        return empty($query->groupings) ? '' : 'GROUP BY '.$this->columnize($query->groupings);
    }

    /**
     * Compile the HAVING clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function havings(Query $query)
    {
        $sql = [];

        if (empty($query->havings)) {
            return '';
        }

        foreach ($query->havings as $having) {
            $connector = $this->connector(isset($having['connector']) ? $having['connector'] : 'AND');

            if (isset($having['type']) && 'having_raw' === $having['type']) {
                $sql[] = $connector . ' ' . $having['sql'];
                continue;
            }

            $parameter = $this->parameter($having['value']);
            $sql[] = $connector . ' ' . $this->wrap($having['column']) . ' ' . $having['operator'] . ' ' . $parameter;
        }

        return 'HAVING ' . preg_replace('/^(AND|OR) /', '', implode(' ', $sql), 1);
    }

    /**
     * Compile the ORDER BY clause.
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
            $ordering['direction'] = strtoupper((string) $ordering['direction']);
            $sql[] = rtrim($this->wrap($ordering['column']) . ' ' . $ordering['direction']);
        }

        return 'ORDER BY '.implode(', ', $sql);
    }

    /**
     * Compile the UNION clauses.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function unions(Query $query)
    {
        $sql = [];

        foreach ($query->unions as $union) {
            $union_sql = $union['query']->grammar->select($union['query']);
            $sql[] = ($union['all'] ? 'UNION ALL ' : 'UNION ').$union_sql;
        }

        return implode(' ', $sql);
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
        return 'LIMIT '.(int) $query->limit;
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
        return 'OFFSET '.(int) $query->offset;
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
        return 'SAVEPOINT ' . $name;
    }

    /**
     * Compile the release of a savepoint; empty means the driver has nothing to release.
     *
     * @param string $name
     *
     * @return string
     */
    public function release_savepoint($name)
    {
        return 'RELEASE SAVEPOINT ' . $name;
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
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * Compile the row locking clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function lock(Query $query)
    {
        if (is_string($query->lock)) {
            return $query->lock;
        }

        $sql = $query->lock ? 'FOR UPDATE' : 'FOR SHARE';

        return $query->lock_option ? $sql . ' ' . $query->lock_option : $sql;
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
        return 'RANDOM()';
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
        throw new \Exception(sprintf(
            'This database driver does not support insert_or_ignore(): %s',
            get_class($this)
        ));
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
        throw new \Exception(sprintf('This database driver does not support upsert(): %s', get_class($this)));
    }

    /**
     * Compile the "ON CONFLICT (...) DO UPDATE" form shared by PostgreSQL and SQLite.
     *
     * @param Query $query
     * @param array $values
     * @param array $unique_by
     * @param array $update
     *
     * @return string
     */
    protected function upsert_on_conflict(Query $query, array $values, array $unique_by, array $update)
    {
        $sets = [];

        foreach ($update as $column) {
            $sets[] = $this->wrap($column).' = excluded.'.$this->wrap($column);
        }

        return $this->insert($query, $values).' ON CONFLICT ('.$this->columnize($unique_by).') DO UPDATE SET '.implode(', ', $sets);
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
        $columns = $this->columnize(array_keys(reset($values)));
        $parameters = $this->parameterize(reset($values));
        $parameters = implode(', ', array_fill(0, count($values), '('.$parameters.')'));

        return 'INSERT INTO '.$table.' ('.$columns.') VALUES '.$parameters;
    }

    /**
     * Compile the INSERT statement and get the last inserted ID.
     *
     * @param Query  $query
     * @param array  $values
     * @param string $column
     *
     * @return string
     */
    public function insert_get_id(Query $query, array $values, $column)
    {
        return $this->insert($query, $values);
    }

    /**
     * Name of the row id used to update or delete joined rows through a subquery,
     * null when the driver joins in the UPDATE / DELETE statement itself.
     *
     * @var string|null
     */
    protected $row_key;

    /**
     * Compile the UPDATE statement.
     *
     * @param Query $query
     * @param array $values
     *
     * @return string
     */
    public function update(Query $query, array $values)
    {
        $table = $this->wrap_table($query->from);

        if (empty($query->joins)) {
            return 'UPDATE '.$table.' SET '.$this->update_columns($values, false).' '.trim($this->wheres($query));
        }

        if ($this->row_key) {
            return 'UPDATE '.$table.' SET '.$this->update_columns($values, true).' '.$this->row_subquery($query);
        }

        return $this->update_with_joins($query, $values);
    }

    /**
     * Compile an UPDATE with joins: UPDATE t INNER JOIN j ON ... SET ... WHERE ...
     *
     * @param Query $query
     * @param array $values
     *
     * @return string
     */
    protected function update_with_joins(Query $query, array $values)
    {
        return trim('UPDATE '.$this->wrap_table($query->from).' '.$this->joins($query)
            .' SET '.$this->update_columns($values, false).' '.$this->wheres($query));
    }

    /**
     * Compile the SET list, optionally stripping table qualifiers from the columns.
     *
     * @param array $values
     * @param bool  $unqualify
     *
     * @return string
     */
    protected function update_columns(array $values, $unqualify)
    {
        $columns = [];

        foreach ($values as $column => $value) {
            if ($unqualify && false !== strpos((string) $column, '.')) {
                $column = substr(strrchr((string) $column, '.'), 1);
            }

            $columns[] = $this->wrap($column).' = '.$this->parameter($value);
        }

        return implode(', ', $columns);
    }

    /**
     * Compile "WHERE <row id> IN (SELECT t.<row id> FROM t <joins> WHERE ...)".
     *
     * @param Query $query
     *
     * @return string
     */
    protected function row_subquery(Query $query)
    {
        $select = clone $query;
        $select->aggregate = null;
        $select->selects = [$this->table_alias($query->from).'.'.$this->row_key];

        return 'WHERE '.$this->wrap($this->row_key).' IN ('.$this->select($select).')';
    }

    /**
     * Get the name the table is referred by: its alias when it has one.
     *
     * @param string $table
     *
     * @return string
     */
    protected function table_alias($table)
    {
        $segments = preg_split('/\s+as\s+/i', (string) $table);
        return trim(end($segments));
    }

    /**
     * Get the UPDATE bindings, in the order the statement uses them.
     *
     * @param Query $query
     * @param array $values
     * @param array $clauses
     *
     * @return array
     */
    public function update_bindings(Query $query, array $values, array $clauses)
    {
        $values = array_values($values);

        if (empty($query->joins)) {
            return array_merge($values, $clauses['where']);
        }

        if ($this->row_key) {
            unset($clauses['select']);
            return call_user_func_array('array_merge', array_merge([$values], array_values($clauses)));
        }

        return array_merge($clauses['join'], $values, $clauses['where']);
    }

    /**
     * Compile the DELETE statement.
     *
     * @param Query $query
     *
     * @return string
     */
    public function delete(Query $query)
    {
        $table = $this->wrap_table($query->from);

        if (empty($query->joins)) {
            return 'DELETE FROM '.$table.' '.trim($this->wheres($query));
        }

        if ($this->row_key) {
            return 'DELETE FROM '.$table.' '.$this->row_subquery($query);
        }

        return trim('DELETE '.$this->wrap_table($this->table_alias($query->from)).' FROM '.$table.' '
            .$this->joins($query).' '.$this->wheres($query));
    }

    /**
     * Get the DELETE bindings, in the order the statement uses them.
     *
     * @param Query $query
     * @param array $clauses
     *
     * @return array
     */
    public function delete_bindings(Query $query, array $clauses)
    {
        if (empty($query->joins)) {
            return $clauses['where'];
        }

        if ($this->row_key) {
            unset($clauses['select']);
            return call_user_func_array('array_merge', array_values($clauses));
        }

        return array_merge($clauses['join'], $clauses['where']);
    }
    /**
     * Replace the (...) in the sql with parameterized values.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return string
     */
    public function shortcut($sql, array &$bindings)
    {
        if (false !== strpos((string) $sql, '(...)')) {
            for ($i = 0; $i < count($bindings); ++$i) {
                if (is_array($bindings[$i])) {
                    $parameters = $this->parameterize($bindings[$i]);
                    array_splice($bindings, $i, 1, $bindings[$i]);
                    $sql = preg_replace('/\(\.\.\.\)/', '('.$parameters.')', $sql, 1);
                }
            }
        }

        return trim($sql);
    }
}
