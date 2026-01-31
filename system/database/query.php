<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Paginator;
use System\Database\Query\Grammars\Grammar as QueryGrammar;

class Query
{
    /**
     * Berisi instance koneksi database.
     *
     * @var Connection
     */
    public $connection;

    /**
     * Berisi instance query grammar.
     *
     * @var QueryGrammar
     */
    public $grammar;

    /**
     * Berisi klausa SELECT.
     *
     * @var array
     */
    public $selects;

    /**
     * Berisi kolom dan fungsi agregasi.
     *
     * @var array
     */
    public $aggregate;

    /**
     * Menunjukkan apakah query harus mereturn distinct result atau tidak.
     *
     * @var bool
     */
    public $distinct = false;

    /**
     * Berisi union queries.
     *
     * @var array
     */
    public $unions = [];

    /**
     * Berisi nama tabel.
     *
     * @var string
     */
    public $from;

    /**
     * Berisi kalusa join tabel.
     *
     * @var array
     */
    public $joins;

    /**
     * Berisi kalusa WHERE.
     *
     * @var array
     */
    public $wheres;

    /**
     * Berisi kalusa GROUP BY.
     *
     * @var array
     */
    public $groupings;

    /**
     * Berisi kalusa HAVING.
     *
     * @var array
     */
    public $havings;

    /**
     * Berisi kalusa ORDER BY.
     *
     * @var array
     */
    public $orderings;

    /**
     * Berisi nilai LIMIT.
     *
     * @var int
     */
    public $limit;

    /**
     * Berisi nilai OFFSET.
     *
     * @var int
     */
    public $offset;

    /**
     * Berisi binding data untuk query.
     *
     * @var array
     */
    public $bindings = [];

    /**
     * Berisi daftar operator komparasi.
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
        'like',
        'like binary',
        'not like',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        '&~',
        'rlike',
        'not rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*',
    ];

    /**
     * Buat instance query baru.
     *
     * @param Connection   $connection
     * @param QueryGrammar $grammar
     * @param string       $table
     *
     */
    public function __construct(Connection $connection, QueryGrammar $grammar, $table)
    {
        $this->from = $table;
        $this->grammar = $grammar;
        $this->connection = $connection;
    }

    /**
     * Paksa query untuk mereturn distinct result.
     *
     * @return Query
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Tambahkan beberapa kolom ke klausa SELECT.
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
     * Tambahkan klausa join ke query.
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
     * Tambahkan klausa LEFT JOIN ke query.
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
     * Tambahkan UNION ke query.
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
     * Tambahkan UNION ALL ke query.
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
     * Reset klausa WHERE.
     */
    public function reset_where()
    {
        $this->wheres = [];
        $this->bindings = [];
    }

    /**
     * Tambahkan klausa WHERE mentah ke query.
     *
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
     * Tambahkan klausa OR WHERE mentah ke query.
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
     * Tambahkan klausa WHERE ke query.
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

        if (!in_array(strtolower((string) $operator), $this->operators) && null === $value) {
            $value = $operator;
            $operator = '=';
        }

        $type = 'where';
        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'connector');
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Tambahkan klausa OR WHERE ke query.
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
     * Tambahkan klausa OR WHERE untuk PRIMARY KEY ke query.
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
     * Tambahkan klausa WHERE IN ke query.
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
     * Tambahkan klausa OR WHERE IN ke query.
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
     * Tambahkan klausa WHERE NOT IN ke query.
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
     * Tambahkan klausa OR WHERE NOT IN ke query.
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
     * Tambahkan klausa WHERE IN dengan subquery ke query.
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
     * Tambahkan klausa WHERE NOT IN dengan subquery ke query.
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
     * Tambahkan klausa WHERE EXISTS dengan subquery ke query.
     *
     * @param Query  $query
     * @param string $connector
     * @param bool   $not
     *
     * @return Query
     */
    public function where_exists($query, $connector = 'AND', $not = false)
    {
        $type = $not ? 'where_not_exists' : 'where_exists';
        $this->wheres[] = compact('type', 'query', 'connector');
        $this->bindings = array_merge($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Tambahkan klausa WHERE NOT EXISTS dengan subquery ke query.
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
     * Reset klausa LIMIT dan OFFSET.
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
     * Reset semua klausa query.
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
        $this->bindings = [];

        return $this;
    }

    /**
     * Buat salinan query saat ini.
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
        $query->bindings = $this->bindings;

        return $query;
    }

    /**
     * Buat query untuk keperluan debugging.
     *
     * @return string
     */
    public function debug()
    {
        $sql = $this->to_sql(true);
        $bindings = $this->bindings;

        foreach ($bindings as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_object($value)) {
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif ($value instanceof Carbon) {
                    $value = $value->toDateTimeString();
                } else {
                    $value = get_class($value);
                }
            }

            $bindings[$key] = $value;
        }

        return vsprintf(str_replace('?', '%s', $sql), $bindings);
    }

    /**
     * Eksekusi query SELECT dan return hasilnya.
     *
     * @param array $columns
     *
     * @return array
     */
    public function get($columns = ['*'])
    {
        if (is_null($this->selects)) {
            $this->select($columns);
        }

        $sql = $this->grammar->select($this);
        return $this->connection->query($sql, $this->bindings);
    }

    /**
     * Eksekusi query SELECT dan return record pertama.
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
     * Ambil generator untuk iterate hasil query satu per satu (memory efficient).
     * Method ini menggunakan generator (PHP 5.5+) untuk efisiensi memori.
     * Untuk PHP 5.4, akan fallback ke get() biasa.
     *
     * @param array $columns
     * @param int   $chunk_size
     *
     * @return \Generator|array
     */
    public function cursor($columns = ['*'], $chunk_size = 1000)
    {
        $columns = is_array($columns) ? $columns : [$columns];
        // PHP < 5.5.0 tidak mendukung generator yield, langsung return hasil get()
        return (PHP_VERSION_ID < 50500) ? $this->get($columns) : include __DIR__ . DS . 'cursor.php';
    }
    /**
     * Cari record berdasarkan primary key.
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
     * Eksekusi query INSERT.
     *
     * @param array $values
     *
     * @return bool
     */
    public function insert(array $values)
    {
        $values = is_array(reset($values)) ? $values : [$values];
        $bindings = [];

        foreach ($values as $value) {
            $bindings = array_merge($bindings, array_values($value));
        }

        $sql = $this->grammar->insert($this, $values);
        return $this->connection->query($sql, $bindings);
    }

    /**
     * Eksekusi query INSERT dan return ID yang dihasilkan.
     *
     * @param array $values
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

        return $id ? intval($id) : null;
    }

    /**
     * Eksekusi query UPDATE.
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
     * Eksekusi query DELETE.
     *
     * @return int
     */
    public function delete()
    {
        $sql = $this->grammar->delete($this);
        return $this->connection->query($sql, $this->bindings);
    }

    /**
     * Increment nilai kolom.
     *
     * @param string $column
     * @param int    $amount
     *
     * @return int
     */
    public function increment($column, $amount = 1)
    {
        return $this->update([$column => $this->raw($column . ' + ' . $amount)]);
    }

    /**
     * Decrement nilai kolom.
     *
     * @param string $column
     * @param int    $amount
     *
     * @return int
     */
    public function decrement($column, $amount = 1)
    {
        return $this->update([$column => $this->raw($column . ' - ' . $amount)]);
    }

    /**
     * Buat raw expression untuk query.
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
     * Handle dynamic where methods seperti where_name, where_email, dll.
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
     * Eksekusi fungsi agregasi seperti COUNT, SUM, AVG, dll.
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
     * Tambahkan nested WHERE clause dengan callback.
     *
     * @param \Closure $callback
     * @param string   $connector
     *
     * @return Query
     */
    public function where_nested(\Closure $callback, $connector = 'AND')
    {
        $query = new static($this->connection, $this->grammar, $this->from);

        call_user_func($callback, $query);

        if (!is_null($query->wheres)) {
            $type = 'where_nested';
            $this->wheres[] = compact('type', 'query', 'connector');
        }

        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    /**
     * Compile query menjadi SQL string.
     *
     * @param bool $with_bindings
     *
     * @return string
     */
    public function to_sql($with_bindings = false)
    {
        $sql = $this->grammar->select($this);

        if (!$with_bindings) {
            return $sql;
        }

        foreach ($this->bindings as $i => $binding) {
            $type = gettype($binding);

            switch ($type) {
                case 'boolean':
                    $str = (int) $binding;
                    $str = "$str";
                    break;

                case 'integer':
                case 'double':
                    $str = "$binding";
                    break;

                case 'string':
                    $str = "'$binding'";
                    break;

                case 'object':
                    if (!($binding instanceof \DateTime) && !($binding instanceof Carbon)) {
                        throw new \Exception(sprintf('Unexpected binding argument class: %s', get_class($binding)));
                    }

                    $str = "'" . $binding->format('Y-m-d H:i:s');
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
     * Tambahkan klausa WHERE BETWEEN ke query.
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
     * Tambahkan klausa OR WHERE BETWEEN ke query.
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
     * Tambahkan klausa WHERE NOT BETWEEN ke query.
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
     * Tambahkan klausa OR WHERE NOT BETWEEN ke query.
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
     * Tambahkan klausa WHERE NULL ke query.
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
     * Tambahkan klausa OR WHERE NULL ke query.
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
     * Tambahkan klausa WHERE NOT NULL ke query.
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
     * Tambahkan klausa OR WHERE NOT NULL ke query.
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
     * Tambahkan klausa GROUP BY ke query.
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
     * Tambahkan klausa HAVING ke query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return Query
     */
    public function having($column, $operator, $value)
    {
        $this->havings[] = compact('column', 'operator', 'value');
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Set pagination untuk query.
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
     * Cari record berdasarkan primary key atau fail.
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
     * Return hanya kolom tertentu dari hasil query.
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
     * Return record pertama atau fail.
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
     * Return array dari kolom tertentu.
     *
     * @param string $column
     * @param string $key
     *
     * @return array
     */
    public function lists($column, $key = null)
    {
        $results = $this->get();

        if (is_null($key)) {
            return array_map(function ($result) use ($column) {
                return $result->$column;
            }, $results);
        }

        $list = [];
        foreach ($results as $result) {
            $list[$result->$key] = $result->$column;
        }

        return $list;
    }

    /**
     * Lakukan pagination pada query.
     *
     * @param int   $perpage
     * @param array $columns
     *
     * @return Paginator
     */
    public function paginate($perpage = 20, array $columns = ['*'])
    {
        $orderings = $this->orderings;
        $this->orderings = null;

        $total = $this->count(reset($columns));
        $page = Paginator::page($total, $perpage);

        $this->orderings = $orderings;

        $results = $this->for_page($page, $perpage)->get($columns);
        return Paginator::make($results, $total, $perpage);
    }

    /**
     * Hitung jumlah record.
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
     * Tambahkan klausa ORDER BY ke query.
     *
     * @param string $column
     * @param string $direction
     *
     * @return Query
     */
    public function order_by($column, $direction = 'asc')
    {
        $this->orderings[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Tambahkan klausa WHERE untuk tanggal.
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
        return $this->where($this->raw('DATE(' . $column . ')'), $operator, $value, $connector);
    }

    /**
     * Tambahkan klausa WHERE untuk bulan.
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
        return $this->where($this->raw('MONTH(' . $column . ')'), $operator, $value, $connector);
    }

    /**
     * Tambahkan klausa WHERE untuk hari.
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
        return $this->where($this->raw('DAY(' . $column . ')'), $operator, $value, $connector);
    }

    /**
     * Tambahkan klausa WHERE untuk tahun.
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
        return $this->where($this->raw('YEAR(' . $column . ')'), $operator, $value, $connector);
    }

    /**
     * Tambahkan klausa WHERE untuk waktu.
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
        return $this->where($this->raw('TIME(' . $column . ')'), $operator, $value, $connector);
    }

    /**
     * Tambahkan klausa WHERE untuk membandingkan dua kolom.
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
     * Tambahkan ORDER BY untuk record terbaru.
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
     * Tambahkan ORDER BY untuk record tertua.
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
     * Check apakah query memiliki hasil.
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
     * Check apakah query tidak memiliki hasil.
     *
     * @return bool
     */
    public function doesnt_exist()
    {
        return !$this->exists();
    }

    /**
     * Lakukan chunk berdasarkan ID.
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

            if (!is_null($last_id)) {
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

            $last_id = $results[$counts - 1]->$alias;
        } while ($counts === $count);

        return true;
    }

    /**
     * Dump query dan die.
     *
     * @return void
     */
    public function dd()
    {
        dd($this->debug());
    }

    /**
     * Tangani pemanggilan method secara dinamis.
     * Seperti fungsi agregasi dan where.
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
            return $this->dynamic_where($method, $parameters, $this);
        }

        if (in_array($method, ['min', 'max', 'avg', 'sum'])) {
            $parameters[0] = (0 === count($parameters)) ? '*' : $parameters[0];
            return $this->aggregate(strtoupper($method), (array) $parameters[0]);
        }

        throw new \Exception(sprintf('Method is not defined: %s', $method));
    }
}
