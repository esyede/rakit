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
            if (!is_null($query->{$component})) {
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
        if (!is_null($query->aggregate)) {
            return;
        }

        return ($query->distinct ? 'SELECT DISTINCT ' : 'SELECT ') . $this->columnize($query->selects);
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
        $column = $this->columnize($query->aggregate['columns']);

        if ($query->distinct && '*' !== $column) {
            $column = 'DISTINCT ' . $column;
        }

        return 'SELECT ' . $query->aggregate['aggregator'] . '(' . $column . ') AS ' . $this->wrap('aggregate');
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
        return 'FROM ' . $this->wrap_table($query->from);
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
                $clauses[] = sprintf(
                    '%s %s %s %s',
                    $clause['connector'],
                    $this->wrap($clause['column1']),
                    $clause['operator'],
                    $this->wrap($clause['column2'])
                );
            }

            $clauses[0] = str_replace(['AND ', 'OR '], '', $clauses[0]);
            $clauses = implode(' ', $clauses);
            $sql[] = $join->type . ' JOIN ' . $table . ' ON ' . $clauses;
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
        if (is_null($query->wheres)) {
            return '';
        }

        $sql = [];

        foreach ($query->wheres as $where) {
            $sql[] = $where['connector'] . ' ' . $this->{$where['type']}($where);
        }

        if (isset($sql)) {
            return 'WHERE ' . preg_replace('/AND |OR /i', '', implode(' ', $sql), 1);
        }
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
        return '(' . substr((string) $this->wheres($where['query']), 6) . ')';
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
        return $this->wrap($where['column']) . ' ' . $where['operator'] . ' ' . $parameter;
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
        $parameters = $this->parameterize($where['values']);
        return $this->wrap($where['column']) . ' IN (' . $parameters . ')';
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
        $parameters = $this->parameterize($where['values']);
        return $this->wrap($where['column']) . ' NOT IN (' . $parameters . ')';
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
        $query = '(' . $where['query']->grammar->select($where['query']) . ')';
        return $this->wrap($where['column']) . ' IN ' . $query;
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
        $query = '(' . $where['query']->grammar->select($where['query']) . ')';
        return $this->wrap($where['column']) . ' NOT IN ' . $query;
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
        $query = '(' . $where['query']->grammar->select($where['query']) . ')';
        return 'EXISTS ' . $query;
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
        $query = '(' . $where['query']->grammar->select($where['query']) . ')';
        return 'NOT EXISTS ' . $query;
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
        return $this->wrap($where['column']) . ' BETWEEN ' . $min . ' AND ' . $max;
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
        return $this->wrap($where['column']) . ' NOT BETWEEN ' . $min . ' AND ' . $max;
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
        return $this->wrap($where['column']) . ' IS NULL';
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
        return $this->wrap($where['column']) . ' IS NOT NULL';
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
        return $this->wrap($where['column1']) . ' ' . $where['operator'] . ' ' . $this->wrap($where['column2']);
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
     * Compile the GROUP BY clause.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function groupings(Query $query)
    {
        return 'GROUP BY ' . $this->columnize($query->groupings);
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

        if (is_null($query->havings)) {
            return '';
        }

        foreach ($query->havings as $having) {
            $parameter = $this->parameter($having['value']);
            $sql[] = 'AND ' . $this->wrap($having['column']) . ' ' . $having['operator'] . ' ' . $parameter;
        }

        return 'HAVING ' . preg_replace('/AND /', '', implode(' ', $sql), 1);
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
        $sql = [];

        foreach ($query->orderings as $ordering) {
            $ordering['direction'] = strtoupper((string) $ordering['direction']);
            $sql[] = $this->wrap($ordering['column']) . ' ' . $ordering['direction'];
        }

        return 'ORDER BY ' . implode(', ', $sql);
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
            $sql[] = ($union['all'] ? 'UNION ALL ' : 'UNION ') . $union_sql;
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
        return 'LIMIT ' . $query->limit;
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
        return 'OFFSET ' . $query->offset;
    }

    /**
     * Compile the INSERT statement.
     * This method handles inserting multiple records at once using a single query.
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
        $parameters = implode(', ', array_fill(0, count($values), '(' . $parameters . ')'));

        return 'INSERT INTO ' . $table . ' (' . $columns . ') VALUES ' . $parameters;
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
        $columns = [];

        foreach ($values as $column => $value) {
            $columns[] = $this->wrap($column) . ' = ' . $this->parameter($value);
        }

        return 'UPDATE ' . $table . ' SET ' . implode(', ', $columns) . ' ' . trim($this->wheres($query));
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
        return 'DELETE FROM ' . $this->wrap_table($query->from) . ' ' . trim($this->wheres($query));
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
                    $sql = preg_replace('/\(\.\.\.\)/', '(' . $parameters . ')', $sql, 1);
                }
            }
        }

        return trim($sql);
    }
}
