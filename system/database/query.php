<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Collection;
use System\Paginator;
use System\Database\Query\Grammars\Grammar as QueryGrammar;

class Query
{
    /**
     * Contains the database connection instance.
     *
     * @var Connection
     */
    public $connection;

    /**
     * Contains the query grammar instance.
     *
     * @var QueryGrammar
     */
    public $grammar;

    /**
     * Contains the selected columns for the SELECT clause.
     *
     * @var array
     */
    public $selects;

    /**
     * Contains aggregate function information.
     *
     * @var array
     */
    public $aggregate;

    /**
     * Indicates whether to select distinct results.
     *
     * @var bool
     */
    public $distinct = false;

    /**
     * BContains the UNION clauses.
     *
     * @var array
     */
    public $unions = [];

    /**
     * Contains the table name for the FROM clause.
     *
     * @var string
     */
    public $from;

    /**
     * Contains the JOIN clauses.
     *
     * @var array
     */
    public $joins;

    /**
     * Contains the WHERE clauses.
     *
     * @var array
     */
    public $wheres;

    /**
     * Contains the GROUP BY clauses.
     *
     * @var array
     */
    public $groupings;

    /**
     * Contains the HAVING clauses.
     *
     * @var array
     */
    public $havings;

    /**
     * Contains the ORDER BY clauses.
     *
     * @var array
     */
    public $orderings;

    /**
     * Contains the LIMIT value.
     *
     * @var int
     */
    public $limit;

    /**
     * Contains the OFFSET value.
     *
     * @var int
     */
    public $offset;

    /**
     * Contains the row locking to apply to the SELECT statement.
     * NULL means no lock, TRUE means an exclusive one, FALSE a shared one,
     * and a string is used as the raw lock clause.
     *
     * @var bool|string|null
     */
    public $lock;

    /**
     * Contains the query bindings.
     *
     * @var array
     */
    public $bindings = [];

    /**
     * Contains the list of valid SQL operators.
     *
     * @var array
     */
    public $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        '&~',
        'like',
        'like binary',
        'not like',
        'ilike',
        'rlike',
        'not rlike',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*',
        '~',
        '~*',
        '!~',
        '!~*',
        'regexp',
        'not regexp',
    ];

    /**
     * Constructor.
     *
     * @param Connection   $connection
     * @param QueryGrammar $grammar
     * @param string       $table
     */
    public function __construct(Connection $connection, QueryGrammar $grammar, $table)
    {
        $this->from = $table;
        $this->grammar = $grammar;
        $this->connection = $connection;
    }

    /**
     * Make the SELECT clause distinct.
     *
     * @return Query
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add columns to the SELECT clause.
     *
     * @param array $columns
     *
     * @return Query
     */
    public function select($columns = ['*'])
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add JOIN clause to the query.
     *
     * @param string $table
     * @param string $column1
     * @param string $operator
     * @param string $column2
     * @param string $type
     *
     * @return Query
     */
    public function join($table, $column1, $operator = null, $column2 = null, $type = 'INNER')
    {
        if ($column1 instanceof \Closure) {
            $this->joins[] = new Query\Join($type, $table);
            call_user_func($column1, end($this->joins));
        } else {
            $this->joins[] = (new Query\Join($type, $table))->on($column1, $operator, $column2);
        }

        return $this;
    }

    /**
     * Add LEFT JOIN clause to the query.
     *
     * @param string $table
     * @param string $column1
     * @param string $operator
     * @param string $column2
     *
     * @return Query
     */
    public function left_join($table, $column1, $operator = null, $column2 = null)
    {
        return $this->join($table, $column1, $operator, $column2, 'LEFT');
    }

    /**
     * Add UNION clause to the query.
     *
     * @param Query $query
     * @param bool  $all
     *
     * @return Query
     */
    public function union($query, $all = false)
    {
        $this->unions[] = ['query' => $query, 'all' => $all];
        $this->bindings = array_merge($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Add UNION ALL clause to the query.
     *
     * @param Query $query
     *
     * @return Query
     */
    public function union_all($query)
    {
        return $this->union($query, true);
    }

    /**
     * Reset the WHERE clauses.
     */
    public function reset_where()
    {
        $this->wheres = [];
        $this->bindings = [];
    }

    /**
     * Add a raw WHERE clause to the query.
     *
     * @param string $where
     * @param array  $bindings
     * @param string $connector
     *
     * @return Query
     */
    public function where_raw($where, array $bindings = [], $connector = 'AND')
    {
        return $this->raw_where($where, $bindings, $connector);
    }

    /**
     * Add a raw WHERE clause joined with OR.
     * Named to match having_raw(), select_raw(), order_by_raw() and group_by_raw().
     *
     * @param string $where
     * @param array  $bindings
     *
     * @return Query
     */
    public function or_where_raw($where, array $bindings = [])
    {
        return $this->raw_where($where, $bindings, 'OR');
    }

    /**
     * @param string $where
     * @param array  $bindings
     * @param string $connector
     *
     * @return Query
     */
    public function raw_where($where, array $bindings = [], $connector = 'AND')
    {
        $this->wheres[] = ['type' => 'where_raw', 'connector' => $connector, 'sql' => $where];
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Add a raw OR WHERE clause to the query.
     *
     * @param string $where
     * @param array  $bindings
     *
     * @return Query
     */
    public function raw_or_where($where, array $bindings = [])
    {
        return $this->raw_where($where, $bindings, 'OR');
    }

    /**
     * Add a WHERE clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function where($column, $operator = null, $value = null, $connector = 'AND')
    {
        if ($column instanceof \Closure) {
            return $this->where_nested($column, $connector);
        }

        if (! in_array(strtolower((string) $operator), $this->operators) && null === $value) {
            $value = $operator;
            $operator = '=';
        }

        $this->validate_operator($operator);

        $type = 'where';
        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'connector');
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add an OR WHERE clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return Query
     */
    public function or_where($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a WHERE clause for the 'id' (default primary key) column.
     *
     * @param mixed $value
     *
     * @return Query
     */
    public function or_where_id($value)
    {
        return $this->or_where('id', '=', $value);
    }

    /**
     * Add a WHERE IN clause to the query.
     *
     * @param string $column
     * @param array  $values
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_in($column, $values, $connector = 'AND', $not = false)
    {
        $type = $not ? 'where_not_in' : 'where_in';
        $this->wheres[] = compact('type', 'column', 'values', 'connector');
        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add an OR WHERE IN clause to the query.
     *
     * @param string $column
     * @param array  $values
     *
     * @return Query
     */
    public function or_where_in($column, array $values)
    {
        return $this->where_in($column, $values, 'OR');
    }

    /**
     * Add a WHERE NOT IN clause to the query.
     *
     * @param string $column
     * @param array  $values
     * @param string $connector
     *
     * @return Query
     */
    public function where_not_in($column, array $values, $connector = 'AND')
    {
        return $this->where_in($column, $values, $connector, true);
    }

    /**
     * Add an OR WHERE NOT IN clause to the query.
     *
     * @param string $column
     * @param array  $values
     *
     * @return Query
     */
    public function or_where_not_in($column, array $values)
    {
        return $this->where_not_in($column, $values, 'OR');
    }

    /**
     * Add a WHERE IN clause with subquery to the query.
     *
     * @param string $column
     * @param Query  $query
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_in_sub($column, Query $query, $connector = 'AND', $not = false)
    {
        $type = $not ? 'where_not_in_sub' : 'where_in_sub';
        $this->wheres[] = compact('type', 'column', 'query', 'connector');
        $this->bindings = array_merge($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Add a WHERE NOT IN clause with subquery to the query.
     *
     * @param string $column
     * @param Query  $query
     * @param string $connector
     *
     * @return Query
     */
    public function where_not_in_sub($column, Query $query, $connector = 'AND')
    {
        return $this->where_in_sub($column, $query, $connector, true);
    }

    /**
     * Add a WHERE EXISTS clause with subquery to the query.
     *
     * @param Query  $query
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_exists($query, $connector = 'AND', $not = false)
    {
        // A closure is handed a query of its own to build the subquery with,
        // the way where_nested() does it.
        if ($query instanceof \Closure) {
            $callback = $query;
            $query = new static($this->connection, $this->grammar, $this->from);

            call_user_func($callback, $query);

            // A subquery the closure left without a SELECT still has to be a
            // complete statement once it lands inside EXISTS ( .. ).
            if (is_null($query->selects)) {
                $query->select(['*']);
            }
        }

        $type = $not ? 'where_not_exists' : 'where_exists';
        $this->wheres[] = compact('type', 'query', 'connector');
        $this->bindings = array_merge($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Add a WHERE NOT EXISTS clause with subquery to the query.
     *
     * @param Query  $query
     * @param string $connector
     *
     * @return Query
     */
    public function where_not_exists($query, $connector = 'AND')
    {
        return $this->where_exists($query, $connector, true);
    }

    /**
     * Reset the LIMIT and OFFSET clauses.
     *
     * @return Query
     */
    public function reset_limit_offset()
    {
        $this->limit = null;
        $this->offset = null;

        return $this;
    }

    /**
     * Reset all parts of the query.
     *
     * @return Query
     */
    public function reset()
    {
        $this->reset_limit_offset();
        $this->reset_where();
        $this->selects = null;
        $this->orderings = null;
        $this->groupings = null;
        $this->havings = null;
        $this->unions = null;
        $this->distinct = false;
        $this->lock = null;
        $this->bindings = [];

        return $this;
    }

    /**
     * Copy the current query instance.
     *
     * @return Query
     */
    public function copy()
    {
        $query = new static($this->connection, $this->grammar, $this->from);

        $query->selects = $this->selects;
        $query->aggregate = $this->aggregate;
        $query->distinct = $this->distinct;
        $query->unions = $this->unions;
        $query->joins = $this->joins;
        $query->wheres = $this->wheres;
        $query->groupings = $this->groupings;
        $query->havings = $this->havings;
        $query->orderings = $this->orderings;
        $query->limit = $this->limit;
        $query->offset = $this->offset;
        $query->lock = $this->lock;
        $query->bindings = $this->bindings;

        return $query;
    }

    /**
     * Make a debug string of the query with bindings.
     *
     * @return string
     */
    public function debug()
    {
        return $this->to_sql(true);
    }

    /**
     * Execute the SELECT query and return the results.
     *
     * @param array $columns
     *
     * @return \System\Collection
     */
    public function get($columns = ['*'])
    {
        if (is_null($this->selects)) {
            $this->select($columns);
        }

        $sql = $this->grammar->select($this);
        return new Collection($this->connection->query($sql, $this->bindings));
    }

    /**
     * EExecute the SELECT query and return the first result.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $this->limit = 1;
        $results = $this->get($columns);
        return (count($results) > 0) ? $results[0] : null;
    }

    /**
     * Get a generator for the results of the query.
     *
     * @param array $columns
     * @param int   $chunk_size
     *
     * @return \Generator|array
     */
    public function cursor($columns = ['*'], $chunk_size = 1000)
    {
        $columns = is_array($columns) ? $columns : [$columns];
        // PHP < 5.5.0 does not support yield, so the whole result set is returned
        // at once. It is handed back as a plain array, the way it always was.
        if (PHP_VERSION_ID < 50500) {
            return $this->get($columns)->all();
        }

        return include __DIR__ . DS . 'cursor.php';
    }

    /**
     * Find a record by primary key.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Execute the INSERT query.
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values)
    {
        $values = is_array(reset($values)) ? $values : [$values];
        $bindings = [];

        $columns = array_keys(reset($values));

        foreach ($values as $value) {
            foreach ($columns as $column) {
                $bindings[] = array_key_exists($column, $value) ? $value[$column] : null;
            }
        }

        $sql = $this->grammar->insert($this, $values);

        // A failing insert throws, so reaching this point means it went through.
        // Returning the (empty) fetch result of the statement would only be
        // confusing, since it is always falsy.
        $this->connection->query($sql, $bindings);

        return true;
    }

    /**
     * Execute the INSERT query and get the inserted ID.
     *
     * @param array  $values
     * @param string $column
     *
     * @return int
     */
    public function insert_get_id(array $values, $column = 'id')
    {
        $sql = $this->grammar->insert_get_id($this, $values, $column);
        $bindings = array_merge(array_values($values), $this->bindings);
        $this->connection->query($sql, $bindings);
        $id = $this->connection->pdo()->lastInsertId();

        return $id ? (int) $id : null;
    }

    /**
     * Execute the UPDATE query.
     *
     * @param array $values
     *
     * @return int
     */
    public function update(array $values)
    {
        $sql = $this->grammar->update($this, $values);
        $bindings = array_merge(array_values($values), $this->bindings);
        return $this->connection->query($sql, $bindings);
    }

    /**
     * Execute the DELETE query.
     *
     * @return int
     */
    public function delete()
    {
        $sql = $this->grammar->delete($this);
        return $this->connection->query($sql, $this->bindings);
    }

    /**
     * Increment the value of a column.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        $values = [$column => $this->raw($this->grammar->wrap($column) . ' + ' . $this->amount($amount))];

        return $this->update(array_merge($values, $extra));
    }

    /**
     * Decrement the value of a column.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        $values = [$column => $this->raw($this->grammar->wrap($column) . ' - ' . $this->amount($amount))];

        return $this->update(array_merge($values, $extra));
    }

    /**
     * Validate an increment / decrement amount.
     * The value is inlined into raw SQL, so anything non numeric is rejected
     * instead of being interpolated.
     *
     * @param mixed $amount
     *
     * @return int|float
     */
    protected function amount($amount)
    {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException(sprintf(
                'Increment / decrement amount must be numeric, %s given.',
                gettype($amount)
            ));
        }

        return $amount + 0;
    }

    /**
     * Make a raw database expression.
     *
     * @param string $value
     *
     * @return Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    /**
     * List of method names that must never be handled as a dynamic WHERE clause.
     * They all exist in Laravel and start with 'where_', so without this guard
     * they would silently be compiled into a column of that name instead of
     * raising an error.
     *
     * @var array
     */
    protected static $reserved_wheres = [
        'where_has' => 'only exists on a facile model query',
        'where_doesnt_have' => 'only exists on a facile model query',
        'where_json_contains' => 'is not supported yet',
        'where_json_length' => 'is not supported yet',
        'where_full_text' => 'is not supported yet',
        'where_relation' => 'is not supported yet',
        'where_belongs_to' => 'is not supported yet',
        'where_morph_relation' => 'is not supported yet',
        'where_integer_in_raw' => 'is not supported yet, use where_in() instead',
        'where_all' => 'is not supported yet',
        'where_any' => 'is not supported yet',
        'where_none' => 'is not supported yet',
        'where_key' => 'only exists on a facile model query',
        'where_key_not' => 'only exists on a facile model query',
    ];

    /**
     * Make sure the given method is not one of the reserved names.
     *
     * @param string $method
     */
    protected static function guard_reserved_where($method)
    {
        if (isset(static::$reserved_wheres[$method])) {
            throw new \Exception(sprintf(
                'Query::%s() %s. It was not compiled as a dynamic where clause to avoid building a wrong query.',
                $method,
                static::$reserved_wheres[$method]
            ));
        }
    }

    /**
     * Handle dynamic WHERE clauses.
     *
     * @param string $method
     * @param array  $parameters
     * @param Query  $query
     *
     * @return Query
     */
    protected function dynamic_where($method, array $parameters, $query = null)
    {
        $query = is_null($query) ? $this : $query;
        $method = substr((string) $method, 6);
        $segments = (array) preg_split('/(_and_|_or_)/i', $method, -1, PREG_SPLIT_DELIM_CAPTURE);
        $connector = 'AND';
        $index = 0;

        foreach ($segments as $segment) {
            if ('_and_' !== $segment && '_or_' !== $segment) {
                $query->where($segment, '=', $parameters[$index], $connector);
                ++$index;
            } else {
                $connector = trim(strtoupper($segment), '_');
            }
        }

        return $query;
    }

    /**
     * Execute an aggregate function query.
     *
     * @param string $aggregator
     * @param array  $columns
     *
     * @return mixed
     */
    public function aggregate($aggregator, array $columns)
    {
        $this->aggregate = compact('aggregator', 'columns');

        $sql = $this->grammar->select($this);
        $result = $this->connection->only($sql, $this->bindings);

        $this->aggregate = null;
        return $result;
    }

    /**
     * Add a WHERE LIKE clause to the query.
     *
     * @param string $column
     * @param string $value
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_like($column, $value, $connector = 'AND', $not = false)
    {
        return $this->where($column, ($not ? 'NOT LIKE' : 'LIKE'), $value, $connector);
    }

    /**
     * Add an OR WHERE LIKE clause to the query.
     *
     * @param string $column
     * @param string $value
     *
     * @return Query
     */
    public function or_where_like($column, $value)
    {
        return $this->where_like($column, $value, 'OR');
    }

    /**
     * Add a WHERE NOT LIKE clause to the query.
     *
     * @param string $column
     * @param string $value
     * @param string $connector
     *
     * @return Query
     */
    public function where_not_like($column, $value, $connector = 'AND')
    {
        return $this->where_like($column, $value, $connector, true);
    }

    /**
     * Add an OR WHERE NOT LIKE clause to the query.
     *
     * @param string $column
     * @param string $value
     *
     * @return Query
     */
    public function or_where_not_like($column, $value)
    {
        return $this->where_like($column, $value, 'OR', true);
    }

    /**
     * Add a nested WHERE clause to the query.
     *
     * @param \Closure $callback
     * @param string   $connector
     *
     * @return Query
     */
    /**
     * Set the table the query runs against.
     *
     * @param string $table
     *
     * @return Query
     */
    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    /**
     * Add a nested where clause to the query.
     *
     * @param \Closure $callback
     * @param string   $connector
     *
     * @return $this
     */
    public function where_nested(\Closure $callback, $connector = 'AND')
    {
        $query = new static($this->connection, $this->grammar, $this->from);

        call_user_func($callback, $query);

        if (! is_null($query->wheres)) {
            $type = 'where_nested';
            $this->wheres[] = compact('type', 'query', 'connector');
        }

        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    /**
     * Compile the query to SQL string.
     *
     * @param bool $with_bindings
     *
     * @return string
     */
    public function to_sql($with_bindings = false)
    {
        $selects = $this->selects;

        if (is_null($this->selects) && is_null($this->aggregate)) {
            $this->selects = ['*'];
        }

        $sql = $this->grammar->select($this);
        $this->selects = $selects;

        if (! $with_bindings) {
            return $sql;
        }

        foreach ($this->bindings as $i => $binding) {
            $type = gettype($binding);

            switch ($type) {
                case 'NULL':
                    $str = 'NULL';
                    break;

                case 'boolean':
                    $str = (int) $binding;
                    $str = "$str";
                    break;

                case 'integer':
                case 'double':
                    $str = "$binding";
                    break;

                case 'string':
                    $str = "'" . str_replace("'", "''", $binding) . "'";
                    break;

                case 'object':
                    if ($binding instanceof Expression) {
                        $str = (string) $binding;
                        break;
                    }

                    if (! ($binding instanceof \DateTime) && ! ($binding instanceof Carbon)) {
                        throw new \Exception(sprintf('Unexpected binding argument class: %s', get_class($binding)));
                    }

                    $str = "'" . $binding->format('Y-m-d H:i:s') . "'";
                    break;

                default:
                    throw new \Exception(sprintf('Unexpected binding argument type: %s', $type));
            }

            $pos = strpos($sql, '?');

            if (false === $pos) {
                throw new \Exception(sprintf('Cannot find binding location in sql for parameter: %s (%s)', $binding, $i));
            }

            $sql = substr($sql, 0, $pos) . $str . substr($sql, $pos + 1);
        }

        return $sql;
    }

    /**
     * Aadd a WHERE BETWEEN clause to the query.
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_between($column, $min, $max, $connector = 'AND', $not = false)
    {
        $type = $not ? 'where_not_between' : 'where_between';
        $this->wheres[] = compact('type', 'column', 'min', 'max', 'connector');

        $this->bindings[] = $min;
        $this->bindings[] = $max;

        return $this;
    }

    /**
     * Add a OR WHERE BETWEEN clause to the query.
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     *
     * @return Query
     */
    public function or_where_between($column, $min, $max)
    {
        return $this->where_between($column, $min, $max, 'OR');
    }

    /**
     * Add a WHERE NOT BETWEEN clause to the query.
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     * @param string $connector
     *
     * @return Query
     */
    public function where_not_between($column, $min, $max, $connector = 'AND')
    {
        return $this->where_between($column, $min, $max, $connector, true);
    }

    /**
     * Add a OR WHERE NOT BETWEEN clause to the query.
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     *
     * @return Query
     */
    public function or_where_not_between($column, $min, $max)
    {
        return $this->where_not_between($column, $min, $max, 'OR');
    }

    /**
     * Add a WHERE NULL clause to the query.
     *
     * @param string $column
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_null($column, $connector = 'AND', $not = false)
    {
        $type = $not ? 'where_not_null' : 'where_null';
        $this->wheres[] = compact('type', 'column', 'connector');

        return $this;
    }

    /**
     * Add a OR WHERE NULL clause to the query.
     *
     * @param string $column
     *
     * @return Query
     */
    public function or_where_null($column)
    {
        return $this->where_null($column, 'OR');
    }

    /**
     * Add a WHERE NOT NULL clause to the query.
     *
     * @param string $column
     * @param string $connector
     *
     * @return Query
     */
    public function where_not_null($column, $connector = 'AND')
    {
        return $this->where_null($column, $connector, true);
    }

    /**
     * Add a OR WHERE NOT NULL clause to the query.
     *
     * @param string $column
     *
     * @return Query
     */
    public function or_where_not_null($column)
    {
        return $this->where_not_null($column, 'OR');
    }

    /**
     * Add a GROUP BY clause to the query.
     *
     * @param string $column
     *
     * @return Query
     */
    public function group_by($column)
    {
        $this->groupings[] = $column;
        return $this;
    }

    /**
     * Add a HAVING clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function having($column, $operator, $value, $connector = 'AND')
    {
        $this->validate_operator($operator);

        $type = 'having';
        $this->havings[] = compact('type', 'column', 'operator', 'value', 'connector');
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add an OR HAVING clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return Query
     */
    public function or_having($column, $operator, $value)
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * Add a raw HAVING clause to the query.
     *
     * @param string $sql
     * @param array  $bindings
     * @param string $connector
     *
     * @return Query
     */
    public function having_raw($sql, array $bindings = [], $connector = 'AND')
    {
        $type = 'having_raw';
        $this->havings[] = compact('type', 'sql', 'connector');
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Add a raw OR HAVING clause to the query.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return Query
     */
    public function or_having_raw($sql, array $bindings = [])
    {
        return $this->having_raw($sql, $bindings, 'OR');
    }

    /**
     * Set the LIMIT and OFFSET clause for pagination.
     *
     * @param int $page
     * @param int $perpage
     *
     * @return Query
     */
    public function for_page($page, $perpage)
    {
        return $this->skip(($page - 1) * $perpage)->take($perpage);
    }

    /**
     * Find a record by primary key or fail.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find_or_fail($id, array $columns = ['*'])
    {
        $result = $this->find($id, $columns);
        return (null === $result) ? abort(404) : $result;
    }

    /**
     * Get only a single column's values from the result set.
     *
     * @param string $column
     *
     * @return array
     */
    public function only($column)
    {
        $sql = $this->grammar->select($this->select([$column]));
        return $this->connection->only($sql, $this->bindings);
    }

    /**
     * Get the first result or fail.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first_or_fail($columns = ['*'])
    {
        $result = $this->first($columns);
        return (null === $result) ? abort(404) : $result;
    }

    /**
     * Get an associative array of column values.
     *
     * @param string $column
     * @param string $key
     *
     * @return array
     */
    public function lists($column, $key = null)
    {
        $results = $this->get()->all();

        if (is_null($key)) {
            return array_map(function ($result) use ($column) {
                return $result->{$column};
            }, $results);
        }

        $list = [];

        foreach ($results as $result) {
            $list[$result->{$key}] = $result->{$column};
        }

        return $list;
    }

    /**
     * Add a RIGHT JOIN clause to the query.
     *
     * @param string $table
     * @param string $column1
     * @param string $operator
     * @param string $column2
     *
     * @return Query
     */
    public function right_join($table, $column1, $operator = null, $column2 = null)
    {
        return $this->join($table, $column1, $operator, $column2, 'RIGHT');
    }

    /**
     * Add a CROSS JOIN clause to the query.
     *
     * @param string $table
     * @param string $column1
     * @param string $operator
     * @param string $column2
     *
     * @return Query
     */
    public function cross_join($table, $column1 = null, $operator = null, $column2 = null)
    {
        if (is_null($column1)) {
            $this->joins[] = new Query\Join('CROSS', $table);
            return $this;
        }

        return $this->join($table, $column1, $operator, $column2, 'CROSS');
    }

    /**
     * Add a raw expression to the SELECT clause.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return Query
     */
    public function select_raw($sql, array $bindings = [])
    {
        $this->selects = [new Expression($sql)];
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Add more columns to the SELECT clause.
     *
     * @param array|string $columns
     *
     * @return Query
     */
    public function add_select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->selects = array_merge(is_array($this->selects) ? $this->selects : [], $columns);

        return $this;
    }

    /**
     * Add a raw ORDER BY clause to the query.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return Query
     */
    public function order_by_raw($sql, array $bindings = [])
    {
        $this->orderings[] = ['column' => new Expression($sql), 'direction' => ''];
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Add a raw GROUP BY clause to the query.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return Query
     */
    public function group_by_raw($sql, array $bindings = [])
    {
        $this->groupings[] = new Expression($sql);
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Order the results randomly.
     *
     * @param string $seed
     *
     * @return Query
     */
    public function in_random_order($seed = '')
    {
        return $this->order_by_raw($this->grammar->random($seed));
    }

    /**
     * Drop every ORDER BY clause that was added so far.
     *
     * @param string $column
     * @param string $direction
     *
     * @return Query
     */
    public function re_order($column = null, $direction = 'asc')
    {
        $this->orderings = null;

        return is_null($column) ? $this : $this->order_by($column, $direction);
    }

    /**
     * Lock the rows the query selects.
     * A lock only holds for the duration of a transaction, so it is only
     * meaningful between a begin_transaction() and its commit().
     *
     * @param bool|string $value
     *
     * @return Query
     */
    public function lock($value = true)
    {
        $this->lock = $value;

        return $this;
    }

    /**
     * Lock the selected rows exclusively, so that no other transaction may
     * read them with a lock, update them or delete them until this one ends.
     * It is what makes a read-check-write sequence safe.
     *
     * @return Query
     */
    public function lock_for_update()
    {
        return $this->lock(true);
    }

    /**
     * Lock the selected rows for sharing, so that other transactions may still
     * read them but none may change them until this one ends.
     *
     * @return Query
     */
    public function shared_lock()
    {
        return $this->lock(false);
    }

    /**
     * Get a single column value of the first result.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function value($column)
    {
        return $this->only($column);
    }

    /**
     * Get the values of a single column as a collection.
     *
     * @param string $column
     * @param string $key
     *
     * @return \System\Collection
     */
    public function pluck($column, $key = null)
    {
        return new Collection($this->lists($column, $key));
    }

    /**
     * Get exactly one result, and complain when there is none or more than one.
     *
     * @param array $columns
     *
     * @return object
     */
    public function sole($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $results = $this->take(2)->get($columns);

        if (0 === count($results)) {
            throw new \Exception(sprintf('No record found for table: %s', $this->from));
        }

        if (count($results) > 1) {
            throw new \Exception(sprintf('More than one record found for table: %s', $this->from));
        }

        return $results[0];
    }

    /**
     * Run the given callback over the results, one chunk at a time.
     * Returning FALSE from the callback stops the iteration.
     *
     * @param int      $count
     * @param callable $callback
     *
     * @return bool
     */
    public function chunk($count, $callback)
    {
        $count = (int) $count;
        $count = ($count < 1) ? 1 : $count;
        $page = 1;

        do {
            $clone = $this->copy();
            $results = $clone->for_page($page, $count)->get();
            $total = count($results);

            if (0 === $total) {
                break;
            }

            if (false === call_user_func($callback, $results, $page)) {
                return false;
            }

            ++$page;
        } while ($total === $count);

        return true;
    }

    /**
     * Run the given callback over every single result.
     * Returning FALSE from the callback stops the iteration.
     *
     * @param callable $callback
     * @param int      $count
     *
     * @return bool
     */
    public function each($callback, $count = 1000)
    {
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $result) {
                if (false === call_user_func($callback, $result, $key)) {
                    return false;
                }
            }
        });
    }

    /**
     * Apply the callback only when the given value is truthy.
     *
     * @param mixed    $value
     * @param callable $callback
     * @param callable $default
     *
     * @return Query
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            $result = call_user_func($callback, $this, $value);
            return is_null($result) ? $this : $result;
        }

        if (! is_null($default)) {
            $result = call_user_func($default, $this, $value);
            return is_null($result) ? $this : $result;
        }

        return $this;
    }

    /**
     * Apply the callback only when the given value is falsy.
     *
     * @param mixed    $value
     * @param callable $callback
     * @param callable $default
     *
     * @return Query
     */
    public function unless($value, $callback, $default = null)
    {
        return $this->when(! $value, $callback, $default);
    }

    /**
     * Hand the query to the callback and keep on chaining.
     *
     * @param callable $callback
     *
     * @return Query
     */
    public function tap($callback)
    {
        call_user_func($callback, $this);

        return $this;
    }

    /**
     * Update the matching record, or insert it when there is none.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return bool
     */
    public function update_or_insert(array $attributes, array $values = [])
    {
        $clone = $this->copy();

        foreach ($attributes as $column => $value) {
            $clone->where($column, '=', $value);
        }

        if (! $clone->exists()) {
            return $this->insert(array_merge($attributes, $values));
        }


        if (0 === count($values)) {
            return true;
        }

        $update = $this->copy();

        foreach ($attributes as $column => $value) {
            $update->where($column, '=', $value);
        }

        $update->update($values);

        return true;
    }

    /**
     * Insert the given records, silently skipping the ones that clash.
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert_or_ignore(array $values)
    {
        if (0 === count($values)) {
            return true;
        }

        $sql = $this->grammar->insert_ignore($this, $values);
        $bindings = [];

        foreach ((is_array(reset($values)) ? $values : [$values]) as $value) {
            $bindings = array_merge($bindings, array_values($value));
        }

        $this->connection->query($sql, $bindings);

        return true;
    }

    /**
     * Paginate the query results.
     *
     * @param int    $perpage
     * @param array  $columns
     * @param string $page_name
     * @param int    $page
     *
     * @return Paginator
     */
    public function paginate($perpage = 20, array $columns = ['*'], $page_name = 'page', $page = null)
    {
        $total = $this->count_for_pagination($columns);
        $page = is_null($page) ? Paginator::page($total, $perpage, $page_name) : (int) $page;
        $results = ($total > 0) ? $this->for_page($page, $perpage)->get($columns) : new Collection();

        return Paginator::make($results, $total, $perpage, $page_name, $page);
    }

    /**
     * Count the total number of records for pagination purposes.
     *
     * @param array $columns
     *
     * @return int
     */
    protected function count_for_pagination(array $columns)
    {
        $orderings = $this->orderings;
        $limit = $this->limit;
        $offset = $this->offset;

        $this->orderings = null;
        $this->limit = null;
        $this->offset = null;

        $columns = $this->without_select_aliases($columns);
        $grouped = (! empty($this->groupings) || ! empty($this->havings));
        $total = $grouped ? $this->count_grouped($columns) : $this->aggregate('COUNT', $columns);

        $this->orderings = $orderings;
        $this->limit = $limit;
        $this->offset = $offset;

        return (int) $total;
    }

    /**
     * Count the number of records of a query containing GROUP BY or HAVING clause.
     *
     * @param array $columns
     *
     * @return int
     */
    protected function count_grouped(array $columns)
    {
        $selects = $this->selects;

        if (is_null($this->selects)) {
            $this->select($columns);
        }

        $sql = $this->grammar->select($this);
        $this->selects = $selects;

        $sql = 'SELECT COUNT(*) AS ' . $this->grammar->wrap('aggregate')
            . ' FROM (' . $sql . ') AS ' . $this->grammar->wrap('aggregate_table');

        return $this->connection->only($sql, $this->bindings);
    }

    /**
     * Strip the aliases off the given columns.
     * Aliases are not valid inside an aggregate function call.
     *
     * @param array $columns
     *
     * @return array
     */
    protected function without_select_aliases(array $columns)
    {
        return array_map(function ($column) {
            return is_string($column) ? preg_replace('/\s+as\s+.+$/i', '', $column) : $column;
        }, $columns);
    }

    /**
     * Count the number of records.
     *
     * @param string $column
     *
     * @return int
     */
    public function count($column = '*')
    {
        return $this->aggregate('COUNT', [$column]);
    }

    /**
     * Add an ORDER BY clause to the query.
     *
     * @param string $column
     * @param string $direction
     *
     * @return Query
     */
    public function order_by($column, $direction = 'asc')
    {
        $direction = strtolower(trim((string) $direction));

        if ('asc' !== $direction && 'desc' !== $direction) {
            throw new \InvalidArgumentException(sprintf(
                'Order direction must be "asc" or "desc", %s given.',
                $direction
            ));
        }

        $this->orderings[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Make sure an operator is one the grammar knows, so it never reaches the
     * SQL as something the caller wrote.
     *
     * @param string $operator
     */
    protected function validate_operator($operator)
    {
        if (! in_array(strtolower((string) $operator), $this->operators)) {
            throw new \InvalidArgumentException(sprintf('Unsupported SQL operator: %s', $operator));
        }
    }

    /**
     * Add a WHERE clause for date.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function where_date($column, $operator, $value, $connector = 'AND')
    {
        return $this->where($this->wrap_date_column($column, 'DATE'), $operator, $value, $connector);
    }

    /**
     * Add a WHERE clause for month.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function where_month($column, $operator, $value, $connector = 'AND')
    {
        return $this->where($this->wrap_date_column($column, 'MONTH'), $operator, $value, $connector);
    }

    /**
     * Add a WHERE clause for day.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function where_day($column, $operator, $value, $connector = 'AND')
    {
        return $this->where($this->wrap_date_column($column, 'DAY'), $operator, $value, $connector);
    }

    /**
     * Add a WHERE clause for year.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function where_year($column, $operator, $value, $connector = 'AND')
    {
        return $this->where($this->wrap_date_column($column, 'YEAR'), $operator, $value, $connector);
    }

    /**
     * Add a WHERE clause for time.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     * @param string $connector
     *
     * @return Query
     */
    public function where_time($column, $operator, $value, $connector = 'AND')
    {
        return $this->where($this->wrap_date_column($column, 'TIME'), $operator, $value, $connector);
    }

    /**
     * Wrap column for DATE/MONTH/etc helpers safely via grammar.
     *
     * @param string $column
     * @param string $function
     * @return Expression
     */
    protected function wrap_date_column($column, $function)
    {
        if ($column instanceof Expression) {
            // Allow explicit Expression, but still wrap function call
            return $this->raw($function . '(' . $column->get() . ')');
        }

        $this->validate_column($column);

        return $this->raw($function . '(' . $this->grammar->wrap($column) . ')');
    }

    /**
     * Validate that a column identifier is safe (no injection).
     *
     * @param string $column
     */
    protected function validate_column($column)
    {
        if (!is_string($column) || '' === trim($column)) {
            throw new \InvalidArgumentException('Invalid column identifier.');
        }

        // Allow letters, digits, underscore, dot for table.column
        // Disallow anything that could close the function call or inject SQL
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)*$/', $column)) {
            throw new \InvalidArgumentException(sprintf('Invalid column identifier: %s', $column));
        }
    }

    /**
     * Add a WHERE clause comparing two columns.
     *
     * @param string $column1
     * @param string $operator
     * @param string $column2
     * @param string $connector
     *
     * @return Query
     */
    public function where_column($column1, $operator, $column2, $connector = 'AND')
    {
        $this->validate_operator($operator);
        $this->validate_column($column1);
        $this->validate_column($column2);

        $this->wheres[] = [
            'type' => 'where_column',
            'column1' => $column1,
            'operator' => $operator,
            'column2' => $column2,
            'connector' => $connector,
        ];
        return $this;
    }

    /**
     * Add an ORDER BY for latest record.
     *
     * @param string $column
     *
     * @return Query
     */
    public function latest($column = 'created_at')
    {
        return $this->order_by($column, 'desc');
    }

    /**
     * Add an ORDER BY for oldest record.
     *
     * @param string $column
     *
     * @return Query
     */
    public function oldest($column = 'created_at')
    {
        return $this->order_by($column, 'asc');
    }

    /**
     * Check if query has any results.
     *
     * @return bool
     */
    public function exists()
    {
        $query = $this->copy();
        $query->selects = ['*'];
        $query->limit = 1;
        $sql = $query->grammar->select($query);
        $result = $query->connection->query($sql, $query->bindings);

        return count($result) > 0;
    }

    /**
     * Check if query has no results.
     *
     * @return bool
     */
    public function doesnt_exist()
    {
        return ! $this->exists();
    }

    /**
     * Chunk the results by primary key.
     *
     * @param int      $count
     * @param callable $callback
     * @param string   $column
     * @param string   $alias
     *
     * @return bool
     */
    public function chunk_by_id($count, callable $callback, $column = 'id', $alias = null)
    {
        $count = (int) $count;
        $alias = $alias ?: $column;
        $last_id = null;

        do {
            $clone = $this->copy();

            if (! is_null($last_id)) {
                $clone->where($column, '>', $last_id);
            }

            $clone->order_by($column, 'asc')->take($count);
            $results = $clone->get();
            $counts = count($results);

            if ($counts === 0) {
                break;
            }

            if ($callback($results) === false) {
                return false;
            }

            $last_id = $results[$counts - 1]->{$alias};
        } while ($counts === $count);

        return true;
    }

    /**
     * Dump the query then die for debugging.
     *
     * @return void
     */
    public function dd()
    {
        dd($this->debug());
    }

    /**
     * Dump the query to the debug bar for debugging.
     *
     * @return void
     */
    public function bd()
    {
        bd($this->debug());
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        $method = (string) $method;

        if ($method === 'take') {
            $this->limit = isset($parameters[0]) ? $parameters[0] : null;
            return $this;
        }

        if ($method === 'skip') {
            $this->offset = isset($parameters[0]) ? $parameters[0] : null;
            return $this;
        }

        if (0 === strpos($method, 'where_')) {
            static::guard_reserved_where($method);
            return $this->dynamic_where($method, $parameters, $this);
        }

        if (in_array($method, ['min', 'max', 'avg', 'sum'])) {
            $parameters[0] = (0 === count($parameters)) ? '*' : $parameters[0];
            return $this->aggregate(strtoupper($method), (array) $parameters[0]);
        }

        throw new \Exception(sprintf('Method is not defined: %s', $method));
    }
}
