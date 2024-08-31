<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Database;
use System\Paginator;
use System\Database\Query\Grammars\Grammar;
use System\Database\Query\Grammars\Postgres;
use System\Database\Query\Grammars\SQLServer;

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
     * @var Query\Grammars\Grammar
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
     * @param Connection $connection
     * @param Grammar    $grammar
     * @param string     $table
     */
    public function __construct(Connection $connection, Grammar $grammar, $table)
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
     * Tambahkan klausa BETWEEN ke query.
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
     * Tambahkan klausa OR BETWEEN ke query.
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
     * Tambahkan klausa NOT BETWEEN ke query.
     *
     * @param string $column
     * @param mixed  $min
     * @param mixed  $max
     *
     * @return Query
     */
    public function where_not_between($column, $min, $max, $connector = 'AND')
    {
        return $this->where_between($column, $min, $max, $connector, true);
    }

    /**
     * Tambahkan klausa OR NOT BETWEEN ke query.
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
     * Tambahkan klausa NESTED WHERE ke query.
     *
     * @param \Closure $callback
     * @param string  $connector
     *
     * @return Query
     */
    public function where_nested(\Closure $callback, $connector = 'AND')
    {
        $query = new Query($this->connection, $this->grammar, $this->from);

        call_user_func($callback, $query);

        if (null !== $query->wheres) {
            $type = 'where_nested';
            $this->wheres[] = compact('type', 'query', 'connector');
        }

        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    /**
     * Tambahkan klausa WHERE DINAMIS ke query.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return Query
     */
    private function dynamic_where($method, array $parameters)
    {
        $method = substr((string) $method, 6);
        $segments = (array) preg_split('/(_and_|_or_)/i', $method, -1, PREG_SPLIT_DELIM_CAPTURE);
        $connector = 'AND';
        $index = 0;

        foreach ($segments as $segment) {
            if ('_and_' !== $segment && '_or_' !== $segment) {
                $this->where($segment, '=', $parameters[$index], $connector);
                ++$index;
            } else {
                $connector = trim(strtoupper($segment), '_');
            }
        }

        return $this;
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
     */
    public function having($column, $operator, $value)
    {
        $this->havings[] = compact('column', 'operator', 'value');
        $this->bindings[] = $value;

        return $this;
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
     * Tambahkan klausa OFFSET ke query.
     *
     * @param int $amount
     *
     * @return Query
     */
    public function skip($amount)
    {
        $this->offset = $amount;
        return $this;
    }

    /**
     * Tambahkan klausa LIMIT ke query.
     *
     * @param int $amount
     *
     * @return Query
     */
    public function take($amount)
    {
        $this->limit = $amount;
        return $this;
    }

    /**
     * Set klausa LIMIT dan OFFSET ke halaman tertentu (untuk paginasi).
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
     * Return hasil pertama berdasarkan primary key.
     *
     * @param int   $id
     * @param array $columns
     *
     * @return \stdClass|null
     */
    public function find($id, array $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Return hasil pertama berdasarkan primary key,
     * atau redirect ke 404 jika tidak ada yang cocok.
     *
     * @param int   $id
     * @param array $columns
     *
     * @return \stdClass|null
     */
    public function find_or_fail($id, array $columns = ['*'])
    {
        $results = $this->find($id, $columns);
        return (null === $results) ? abort(404) : $results;
    }

    /**
     * Jalankan query sebagai statement SELECT dan return sebuah kolom.
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
     * Return hasil pertama.
     *
     * @param array $columns
     *
     * @return \stdClass|null
     */
    public function first($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $results = $this->take(1)->get($columns);

        return (count($results) > 0) ? $results[0] : null;
    }

    /**
     * Return hasil pertama atau redirect ke 404 jika tidak ada yang cocok.
     *
     * @param array $columns
     *
     * @return \stdClass|\System\Response
     */
    public function first_or_fail($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $results = $this->first($columns);

        return (null === $results) ? abort(404) : $results;
    }

    /**
     * Mereturn value milik kolom tertentu dalam bentuk array.
     *
     * @param string $column
     * @param string $key
     *
     * @return array
     */
    public function lists($column, $key = null)
    {
        $columns = is_null($key) ? [$column] : [$column, $key];
        $results = $this->get($columns);

        $values = array_map(function ($row) use ($column) {
            return $row->{$column};
        }, $results);

        if (!is_null($key) && count($results)) {
            return array_combine(array_map(function ($row) use ($key) {
                return $row->{$key};
            }, $results), $values);
        }

        return $values;
    }

    /**
     * Jalankan query sebagai statement SELECT.
     *
     * @param array $columns
     *
     * @return array
     */
    public function get($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        if (is_null($this->selects)) {
            $this->select($columns);
        }

        $sql = $this->grammar->select($this);
        $results = $this->connection->query($sql, $this->bindings);

        if ($this->offset > 0 && $this->grammar instanceof SQLServer) {
            array_walk($results, function ($result) {
                unset($result->rownum);
            });
        }

        $this->selects = null;
        return $results;
    }

    /**
     * Mereturn nilai agregasi.
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
     * Mereturn hasil query sebagai instance Paginator.
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
     * Insert array data ke tabel.
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
     * Insert array data ke tabel dan return key-nya.
     *
     * @param array  $values
     * @param string $column
     *
     * @return mixed
     */
    public function insert_get_id(array $values, $column = 'id')
    {
        $sql = $this->grammar->insert_get_id($this, $values, $column);
        $result = $this->connection->query($sql, array_values($values));

        if (isset($values[$column])) {
            return $values[$column];
        } elseif ($this->grammar instanceof Postgres) {
            $row = (array) $result[0];
            return (int) $row[$column];
        }

        return (int) $this->connection->pdo()->lastInsertId();
    }

    /**
     * Tambah nilai suatu kolom sebanyak value yang diberikan.
     *
     * @param string $column
     * @param int    $amount
     *
     * @return int
     */
    public function increment($column, $amount = 1)
    {
        return $this->adjust($column, $amount, ' + ');
    }

    /**
     * Kurangi nilai suatu kolom sebanyak value yang diberikan.
     *
     * @param string $column
     * @param int    $amount
     *
     * @return int
     */
    public function decrement($column, $amount = 1)
    {
        return $this->adjust($column, $amount, ' - ');
    }

    /**
     * Tambah atau kurangi nilai suatu kolom sebanyak value yang diberikan.
     *
     * @param string $column
     * @param int    $amount
     * @param string $operator
     *
     * @return int
     */
    protected function adjust($column, $amount, $operator)
    {
        $wrapped = $this->grammar->wrap($column);
        $value = Database::raw($wrapped . $operator . $amount);

        return $this->update([$column => $value]);
    }

    /**
     * Update tabel di database.
     *
     * @param array $values
     *
     * @return int
     */
    public function update(array $values)
    {
        $bindings = array_merge(array_values($values), $this->bindings);
        $sql = $this->grammar->update($this, $values);

        return $this->connection->query($sql, $bindings);
    }

    /**
     * Jalankan query sebagai statement DELETE.
     * Oper ID untuk menghapus row spesifik.
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->where('id', '=', $id);
        }

        $sql = $this->grammar->delete($this);
        return $this->connection->query($sql, $this->bindings);
    }

    /**
     * Ambil representasi SQL dari kueri.
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

                    $str = $binding->format('Y-m-d H:i:s') . "'";
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
     * Magic method untuk menangani pemanggilan method dinamis.
     * Seperti fungsi agregasi dan where.
     */
    public function __call($method, array $parameters)
    {
        $method = (string) $method;

        if (0 === strpos($method, 'where_')) {
            return $this->dynamic_where($method, $parameters, $this);
        }

        if (in_array($method, ['count', 'min', 'max', 'avg', 'sum'])) {
            $parameters[0] = (0 === count($parameters)) ? '*' : $parameters[0];
            return $this->aggregate(strtoupper($method), (array) $parameters[0]);
        }

        throw new \Exception(sprintf('Method is not defined: %s', $method));
    }
}
