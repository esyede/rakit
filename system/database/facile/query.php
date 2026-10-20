<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Carbon;
use System\Database;
use System\Collection;
use System\Database\Expression;
use System\Database\Query\Lazy;
use System\Exceptions\ModelNotFoundException;

class Query
{
    /**
     * Contains the model instance.
     *
     * @var Model
     */
    public $model;

    /**
     * Contains the query builder instance.
     *
     * @var Query
     */
    public $table;

    /**
     * Contains a list of relationships that needs to be eager loaded.
     *
     * @var array
     */
    public $with = [];

    /**
     * Whether soft-deleted rows should be included in the query.
     *
     * @var bool
     */
    public $with_trashed = false;

    /**
     * Query builder methods whose result is returned as is, unwrapped.
     *
     * @var array
     */
    public $passthru = [
        'lists',
        'only',
        'get',
        'first',
        'find',
        'find_or_fail',
        'first_or_fail',
        'paginate',
        'count',
        'insert',
        'insert_get_id',
        'upsert',
        'min',
        'max',
        'avg',
        'sum',
        'to_sql',
        'debug',
        'exists',
        'doesnt_exist',
        'value',
        'pluck',
        'sole',
        'chunk',
        'each',
        'update_or_insert',
        'insert_or_ignore',
    ];

    /**
     * Constructor.
     *
     * @param Model $model
     * @param bool  $with_trashed
     */
    public function __construct($model, $with_trashed = false)
    {
        $this->model = ($model instanceof Model) ? $model : new $model();
        $this->with_trashed = (bool) $with_trashed;
        $this->table = $this->table();
    }

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, array $columns = ['*'])
    {
        $model = $this->model;
        $this->table->where($model::$key, '=', $id);

        return $this->first($columns);
    }

    /**
     * Get the first model that matches the query.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $results = $this->hydrate($this->model, $this->table->take(1)->get($columns));

        return (count($results) > 0) ? $results->first() : null;
    }

    /**
     * Find a model by its primary key or throw exception if not found.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return Model
     */
    public function find_or_fail($id, array $columns = ['*'])
    {
        $result = $this->find($id, $columns);

        if (is_null($result)) {
            throw new ModelNotFoundException(get_class($this->model) . ' with id ' . $id . ' not found.');
        }

        return $result;
    }

    /**
     * Get the first model that matches the query or throw exception if not found.
     *
     * @param array $columns
     *
     * @return Model
     */
    public function first_or_fail($columns = ['*'])
    {
        $result = $this->first($columns);

        if (is_null($result)) {
            throw new ModelNotFoundException(get_class($this->model) . ' not found.');
        }

        return $result;
    }

    /**
     * Get all models that match the query.
     *
     * @param array $columns
     *
     * @return \System\Collection
     */
    public function get($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        return $this->hydrate($this->model, $this->table->get($columns));
    }

    /**
     * Run the query once and stream its models one at a time. Each row is hydrated
     * as it is fetched, so eager loads set with with() are NOT applied; use lazy().
     *
     * @return \Generator|\System\Database\Query\Cursor
     */
    public function cursor()
    {
        $table = $this->table;

        if (is_null($table->selects)) {
            $table->select(['*']);
        }

        $class = get_class($this->model);

        return $table->connection->cursor($table->grammar->select($table), $table->bindings, function ($row) use ($class) {
            return $this->make($class, $row);
        });
    }

    /**
     * Iterate the models lazily, fetching (and eager loading) them in chunks with
     * LIMIT/OFFSET. Orders by the model key when the query has no order.
     *
     * @param int $chunk_size
     *
     * @return \Generator|\System\Database\Query\Lazy
     */
    public function lazy($chunk_size = 1000)
    {
        $chunk_size = Lazy::size($chunk_size);
        $table = $this->table->copy();

        if (empty($table->orderings)) {
            $table->order_by($this->qualified_key(), 'asc');
        }

        return Lazy::make(function ($page) use ($table, $chunk_size) {
            return $this->hydrate($this->model, $table->copy()->for_page($page + 1, $chunk_size)->get())->all();
        }, $chunk_size);
    }

    /**
     * Iterate the models lazily, fetching (and eager loading) them in chunks by ascending key.
     *
     * @param int         $chunk_size
     * @param string|null $column
     * @param string|null $alias
     *
     * @return \Generator|\System\Database\Query\Lazy
     */
    public function lazy_by_id($chunk_size = 1000, $column = null, $alias = null)
    {
        $chunk_size = Lazy::size($chunk_size);
        return Lazy::make($this->chunks_by_id($chunk_size, $column, $alias, false, 'lazy_by_id'), $chunk_size);
    }

    /**
     * Iterate the models lazily, fetching (and eager loading) them in chunks by descending key.
     *
     * @param int         $chunk_size
     * @param string|null $column
     * @param string|null $alias
     *
     * @return \Generator|\System\Database\Query\Lazy
     */
    public function lazy_by_id_desc($chunk_size = 1000, $column = null, $alias = null)
    {
        $chunk_size = Lazy::size($chunk_size);
        return Lazy::make($this->chunks_by_id($chunk_size, $column, $alias, true, 'lazy_by_id_desc'), $chunk_size);
    }

    /**
     * Run the callback over the models a chunk at a time, paging by ascending key;
     * FALSE stops the iteration.
     *
     * @param int         $count
     * @param callable    $callback
     * @param string|null $column
     * @param string|null $alias
     *
     * @return bool
     */
    public function chunk_by_id($count, $callback, $column = null, $alias = null)
    {
        $count = max(1, (int) $count);
        return Lazy::chunks($this->chunks_by_id($count, $column, $alias, false, 'chunk_by_id'), $count, $callback);
    }

    /**
     * Run the callback over the models a chunk at a time, paging by descending key;
     * FALSE stops the iteration.
     *
     * @param int         $count
     * @param callable    $callback
     * @param string|null $column
     * @param string|null $alias
     *
     * @return bool
     */
    public function chunk_by_id_desc($count, $callback, $column = null, $alias = null)
    {
        $count = max(1, (int) $count);
        return Lazy::chunks($this->chunks_by_id($count, $column, $alias, true, 'chunk_by_id_desc'), $count, $callback);
    }

    /**
     * Run the callback over every model, with (model, key), paging by ascending key;
     * FALSE stops the iteration.
     *
     * @param callable    $callback
     * @param int         $count
     * @param string|null $column
     * @param string|null $alias
     *
     * @return bool
     */
    public function each_by_id($callback, $count = 1000, $column = null, $alias = null)
    {
        $count = max(1, (int) $count);

        return $this->chunk_by_id($count, function ($models, $page) use ($callback, $count) {
            foreach ($models as $key => $model) {
                if (false === call_user_func($callback, $model, (($page - 1) * $count) + $key)) {
                    return false;
                }
            }
        }, $column, $alias);
    }

    /**
     * Get the chunk source that pages the query by key and hydrates every chunk.
     *
     * @param int         $count
     * @param string|null $column
     * @param string|null $alias
     * @param bool        $descending
     * @param string      $operation
     *
     * @return \Closure
     */
    protected function chunks_by_id($count, $column, $alias, $descending, $operation)
    {
        if (is_null($column)) {
            $column = $this->qualified_key();
            $alias = $alias ?: $this->model->key();
        }

        return $this->table->chunks_by_id($count, $column, $alias, $descending, $operation, function ($query) {
            return $this->hydrate($this->model, $query->get())->all();
        });
    }

    /**
     * Get the model key qualified with its table.
     *
     * @return string
     */
    protected function qualified_key()
    {
        return $this->model->table() . '.' . $this->model->key();
    }

    /**
     * Add a WHERE clause on the primary key of the model.
     *
     * @param mixed $id
     *
     * @return Query
     */
    public function where_key($id)
    {
        $key = $this->model->table() . '.' . $this->model->key();

        if (is_array($id)) {
            $this->table->where_in($key, $id);
        } else {
            $this->table->where($key, '=', $id);
        }

        return $this;
    }

    /**
     * Add a WHERE NOT clause on the primary key of the model.
     *
     * @param mixed $id
     *
     * @return Query
     */
    public function where_key_not($id)
    {
        $key = $this->model->table() . '.' . $this->model->key();

        if (is_array($id)) {
            $this->table->where_not_in($key, $id);
        } else {
            $this->table->where($key, '!=', $id);
        }

        return $this;
    }

    /**
     * Constrain the query to models that own the given relationship.
     *
     * @param string   $relationship
     * @param string   $operator
     * @param int      $count
     * @param \Closure $callback
     * @param string   $connector
     *
     * @return Query
     */
    public function has($relationship, $operator = '>=', $count = 1, $callback = null, $connector = 'AND')
    {
        $sub = $this->relationship_subquery($relationship, $callback);
        $operator = trim((string) $operator);
        $count = (int) $count;

        $allowed = ['=', '<', '>', '<=', '>=', '<>', '!='];

        if (!in_array($operator, $allowed, true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported SQL operator: %s', $operator));
        }

        if ('>=' === $operator && 1 === $count) {
            $this->table->where_exists($sub, $connector);
            return $this;
        }

        if (
            ('<' === $operator && 1 === $count)
            || ('=' === $operator && 0 === $count)
            || ('<=' === $operator && 0 === $count)
        ) {
            $this->table->where_exists($sub, $connector, true);
            return $this;
        }

        $sub->select([new Expression('COUNT(*)')]);
        $sql = '(' . $sub->grammar->select($sub) . ') ' . $operator . ' ' . $count;
        $this->table->raw_where($sql, $sub->get_bindings(), $connector);

        return $this;
    }

    /**
     * Constrain to models owning the relationship, with extra constraints on it.
     *
     * @param string   $relationship
     * @param \Closure $callback
     * @param string   $operator
     * @param int      $count
     *
     * @return Query
     */
    public function where_has($relationship, $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relationship, $operator, $count, $callback);
    }

    /**
     * Constrain the query to models that own the given relationship (OR).
     *
     * @param string   $relationship
     * @param string   $operator
     * @param int      $count
     * @param \Closure $callback
     *
     * @return Query
     */
    public function or_has($relationship, $operator = '>=', $count = 1, $callback = null)
    {
        return $this->has($relationship, $operator, $count, $callback, 'OR');
    }

    /**
     * Constrain the query to models that do not own the given relationship.
     *
     * @param string   $relationship
     * @param \Closure $callback
     *
     * @return Query
     */
    public function doesnt_have($relationship, $callback = null)
    {
        return $this->has($relationship, '<', 1, $callback);
    }

    /**
     * Constrain to models not owning the relationship, with extra constraints on it.
     *
     * @param string   $relationship
     * @param \Closure $callback
     *
     * @return Query
     */
    public function where_doesnt_have($relationship, $callback = null)
    {
        return $this->doesnt_have($relationship, $callback);
    }

    /**
     * Shorthand for where_has() with a callback holding a single where().
     *
     * @param string          $relationship
     * @param string|\Closure $column
     * @param string          $operator
     * @param mixed           $value
     *
     * @return Query
     */
    public function where_relation($relationship, $column, $operator = null, $value = null)
    {
        return $this->has($relationship, '>=', 1, $this->relation_condition($column, $operator, $value));
    }

    /**
     * Constrain the query to models that own a related model matching the condition (OR).
     *
     * @param string          $relationship
     * @param string|\Closure $column
     * @param string          $operator
     * @param mixed           $value
     *
     * @return Query
     */
    public function or_where_relation($relationship, $column, $operator = null, $value = null)
    {
        return $this->has($relationship, '>=', 1, $this->relation_condition($column, $operator, $value), 'OR');
    }

    /**
     * Make the callback that puts a single where() on a relationship query.
     *
     * @param string|\Closure $column
     * @param string          $operator
     * @param mixed           $value
     *
     * @return \Closure
     */
    protected function relation_condition($column, $operator, $value)
    {
        return function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        };
    }

    /**
     * Constrain to models belonging to the given parent(s). Without a relationship
     * name, it is guessed from the parent class: BlogAuthor -> blog_author().
     *
     * @param Model|Collection|array $related
     * @param string                 $relationship
     * @param string                 $connector
     *
     * @return Query
     */
    public function where_belongs_to($related, $relationship = null, $connector = 'AND')
    {
        $models = ($related instanceof Model) ? [$related] : $related;
        $models = ($models instanceof Collection) ? $models->all() : $models;

        if (! is_array($models) || empty($models) || ! (reset($models) instanceof Model)) {
            throw new \InvalidArgumentException(
                'where_belongs_to() expects a model, or a non-empty collection or array of models.'
            );
        }

        $relationship = is_null($relationship) ? Str::snake(class_basename(reset($models))) : $relationship;
        $relation = $this->resolve_relationship($relationship);

        if (! ($relation instanceof Relationships\BelongsTo)) {
            throw new \Exception(sprintf(
                'Method %s::%s() is not a belongs_to relationship.',
                get_class($this->model),
                $relationship
            ));
        }

        $class = get_class($relation->model);
        $keys = [];

        foreach ($models as $model) {
            if (! ($model instanceof $class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Relationship %s::%s() expects %s models, %s given.',
                    get_class($this->model),
                    $relationship,
                    $class,
                    is_object($model) ? get_class($model) : gettype($model)
                ));
            }

            if (! is_null($key = $model->get_key())) {
                $keys[] = $key;
            }
        }

        $column = $this->model->table() . '.' . $relation->foreign_key();
        $keys = array_values(array_unique($keys));

        if (1 === count($keys)) {
            $this->table->where($column, '=', $keys[0], $connector);
        } else {
            $this->table->where_in($column, $keys, $connector);
        }

        return $this;
    }

    /**
     * Constrain the query to models that belong to the given parent model(s) (OR).
     *
     * @param Model|Collection|array $related
     * @param string                 $relationship
     *
     * @return Query
     */
    public function or_where_belongs_to($related, $relationship = null)
    {
        return $this->where_belongs_to($related, $relationship, 'OR');
    }

    /**
     * Select the number of related records as a '<relationship>_count' column.
     *
     * @param array|string $relationships
     *
     * @return Query
     */
    public function with_count($relationships)
    {
        return $this->with_aggregate(is_array($relationships) ? $relationships : func_get_args(), 'COUNT', '*');
    }

    /**
     * Select the sum of a column of related records as '<relationship>_sum_<column>'.
     *
     * @param array|string $relationships
     * @param string       $column
     *
     * @return Query
     */
    public function with_sum($relationships, $column)
    {
        return $this->with_aggregate((array) $relationships, 'SUM', $column);
    }

    /**
     * Select the average of a column of related records as '<relationship>_avg_<column>'.
     *
     * @param array|string $relationships
     * @param string       $column
     *
     * @return Query
     */
    public function with_avg($relationships, $column)
    {
        return $this->with_aggregate((array) $relationships, 'AVG', $column);
    }

    /**
     * Select the lowest value of a column of related records as '<relationship>_min_<column>'.
     *
     * @param array|string $relationships
     * @param string       $column
     *
     * @return Query
     */
    public function with_min($relationships, $column)
    {
        return $this->with_aggregate((array) $relationships, 'MIN', $column);
    }

    /**
     * Select the highest value of a column of related records as '<relationship>_max_<column>'.
     *
     * @param array|string $relationships
     * @param string       $column
     *
     * @return Query
     */
    public function with_max($relationships, $column)
    {
        return $this->with_aggregate((array) $relationships, 'MAX', $column);
    }

    /**
     * Select whether related records exist, as a '<relationship>_exists' column (1 or 0).
     *
     * @param array|string $relationships
     *
     * @return Query
     */
    public function with_exists($relationships)
    {
        return $this->with_aggregate(is_array($relationships) ? $relationships : func_get_args(), 'EXISTS', null);
    }

    /**
     * Add a correlated aggregate of each relationship to the selected columns.
     *
     * @param array       $relationships names, or name => constraining closure
     * @param string      $function      COUNT, SUM, AVG, MIN, MAX or EXISTS
     * @param string|null $column
     *
     * @return Query
     */
    protected function with_aggregate(array $relationships, $function, $column)
    {
        if (!isset($this->table) || !$this->table instanceof \System\Database\Query) {
            $this->table = $this->table();
        }

        if (is_null($this->table->selects) || !is_array($this->table->selects)) {
            $this->table->select([$this->model->table() . '.*']);
        }

        foreach ($relationships as $key => $value) {
            $callback = null;
            $relationship = $value;

            if (is_string($key) && ($value instanceof \Closure)) {
                $relationship = $key;
                $callback = $value;
            }

            $sub = $this->relationship_subquery($relationship, $callback);

            if ('EXISTS' === $function) {
                $alias = Str::snake((string) $relationship).'_exists';
            } elseif ('COUNT' === $function) {
                $sub->select([new Expression('COUNT(*)')]);
                $alias = Str::snake((string) $relationship).'_count';
            } else {
                $related = $this->resolve_relationship($relationship)->model->table();
                $target = (false === strpos((string) $column, '.')) ? $related.'.'.$column : $column;
                $sub->select([new Expression($function.'('.$sub->grammar->wrap($target).')')]);
                $alias = Str::snake((string) $relationship).'_'.strtolower($function).'_'.Str::snake(str_replace('.', '_', (string) $column));
            }
            $grammar = isset($this->table->grammar)
                ? $this->table->grammar
                : (isset($sub->grammar) ? $sub->grammar : null);

            if (!$grammar || !method_exists($grammar, 'select') || !method_exists($grammar, 'wrap')) {
                throw new \Exception('Query grammar not available for with_count() and the other aggregates.');
            }

            $sub_sql = $sub->grammar->select($sub);
            $wrapped = $grammar->wrap($alias);
            $sql = ('EXISTS' === $function)
                ? 'CASE WHEN EXISTS (' . $sub_sql . ') THEN 1 ELSE 0 END AS ' . $wrapped
                : '(' . $sub_sql . ') AS ' . $wrapped;

            if (!is_array($this->table->selects)) {
                $this->table->selects = [];
            }

            $this->table->selects[] = new Expression($sql);
            $this->table->add_binding($sub->get_bindings(), 'select');
        }

        return $this;
    }

    /**
     * Build the correlated subquery of the given relationship.
     *
     * @param string   $relationship
     * @param \Closure $callback
     *
     * @return \System\Database\Query
     */
    protected function relationship_subquery($relationship, $callback = null)
    {
        $relation = $this->resolve_relationship($relationship);
        $sub = $relation->correlate($this->model->table());
        $sub->select([new Expression('1')]);

        if (! is_null($callback)) {
            call_user_func($callback, $relation);
        }

        return $sub;
    }

    /**
     * Get the relationship instance defined by the given method of the model.
     *
     * @param string $relationship
     *
     * @return Relationships\Relationship
     */
    protected function resolve_relationship($relationship)
    {
        $relation = $this->model->{$this->relationship_method($relationship)}();

        if (! ($relation instanceof Relationships\Relationship)) {
            throw new \Exception(sprintf(
                'Method %s::%s() is not a relationship.',
                get_class($this->model),
                $relationship
            ));
        }

        return $relation;
    }

    /**
     * Check that a name may be called as a relationship before calling it. Names
     * often come from the request (with(Input::get('include'))), so only a public,
     * argument-less method the model itself declares qualifies: never save(),
     * delete() or any other method of the base Model.
     *
     * @param string $relationship
     *
     * @return string
     */
    protected function relationship_method($relationship)
    {
        $model = get_class($this->model);

        if (
            ! is_string($relationship)
            || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $relationship)
            || ! method_exists($this->model, $relationship)
        ) {
            throw new \Exception(sprintf('Undefined relationship on %s: %s', $model, $relationship));
        }

        $method = new \ReflectionMethod($this->model, $relationship);

        if (
            ! $method->isPublic()
            || $method->isStatic()
            || 0 !== $method->getNumberOfRequiredParameters()
            || method_exists('System\\Database\\Facile\\Model', $relationship)
        ) {
            throw new \Exception(sprintf('Method %s::%s() is not a relationship.', $model, $relationship));
        }

        return $relationship;
    }

    /**
     * Get exactly one model, and complain when there is none or more than one.
     *
     * @param array $columns
     *
     * @return Model
     */
    public function sole($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $results = $this->hydrate($this->model, $this->table->take(2)->get($columns));

        if (0 === count($results)) {
            throw new ModelNotFoundException(get_class($this->model) . ' not found.');
        }

        if (count($results) > 1) {
            throw new \Exception(sprintf('More than one %s found.', get_class($this->model)));
        }

        return $results->first();
    }

    /**
     * Run the callback over the models a chunk at a time; FALSE stops the iteration.
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
        $table = $this->table->copy();

        if (empty($table->orderings)) {
            $table->order_by($this->qualified_key(), 'asc');
        }

        do {
            $results = $this->hydrate($this->model, $table->copy()->for_page($page, $count)->get());

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
     * Run the callback over every model; FALSE stops the iteration.
     *
     * @param callable $callback
     * @param int      $count
     *
     * @return bool
     */
    public function each($callback, $count = 1000)
    {
        return $this->chunk($count, function ($models) use ($callback) {
            foreach ($models as $key => $model) {
                if (false === call_user_func($callback, $model, $key)) {
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
     * Get the paginated results of the query.
     *
     * @param int    $perpage
     * @param array  $columns
     * @param string $page_name
     * @param int    $page
     *
     * @return \System\Paginator
     */
    public function paginate($perpage = null, array $columns = ['*'], $page_name = 'page', $page = null)
    {
        $perpage = $perpage ?: $this->model->perpage();
        $paginator = $this->table->paginate($perpage, $columns, $page_name, $page);
        $paginator->results = $this->hydrate($this->model, $paginator->results);

        return $paginator;
    }

    /**
     * Paginate without counting the total, see Database\Query::simple_paginate().
     *
     * @param int|null $perpage
     * @param array    $columns
     * @param string   $page_name
     * @param int|null $page
     *
     * @return \System\Paginator
     */
    public function simple_paginate($perpage = null, array $columns = ['*'], $page_name = 'page', $page = null)
    {
        $perpage = $perpage ?: $this->model->perpage();
        $paginator = $this->table->simple_paginate($perpage, $columns, $page_name, $page);
        $paginator->results = $this->hydrate($this->model, $paginator->results);

        return $paginator;
    }

    /**
     * Paginate by position, see Database\Query::cursor_paginate(). Ordered by the
     * primary key when the query has no ORDER BY.
     *
     * @param int|null    $perpage
     * @param array       $columns
     * @param string      $cursor_name
     * @param string|null $cursor
     *
     * @return \System\Paginator\Cursor
     */
    public function cursor_paginate($perpage = null, array $columns = ['*'], $cursor_name = 'cursor', $cursor = null)
    {
        if (empty($this->table->orderings)) {
            $this->table->order_by($this->model->key());
        }

        $perpage = $perpage ?: $this->model->perpage();
        $paginator = $this->table->cursor_paginate($perpage, $columns, $cursor_name, $cursor);
        $paginator->results = $this->hydrate($this->model, $paginator->results);

        return $paginator;
    }

    /**
     * Eager load relationships onto models already fetched, as with() would have.
     *
     * @param array|\System\Collection $models
     * @param array|string              $relationships
     */
    public function eager_load_into($models, $relationships)
    {
        $models = ($models instanceof Collection) ? $models->all() : array_values((array) $models);

        if (0 === count($models)) {
            return;
        }

        $this->model->with = (array) $relationships;

        foreach ($this->model_with() as $relationship => $constraints) {
            if (! Str::contains($relationship, '.')) {
                $this->load($models, $relationship, $constraints);
            }
        }
    }

    /**
     * Do a mass-assignment of the given results to model instances.
     *
     * @param Model|array|\Traversable $results
     *
     * @return \System\Collection
     */
    public function hydrate($model, $results)
    {
        $model = get_class($model);
        $models = [];

        foreach ($results as $result) {
            $models[] = $this->make($model, $result);
        }

        if (count($models) > 1) {
            foreach ($models as $instance) {
                $instance->fetched_with_others = true;
            }
        }

        if (count($results) > 0) {
            $with = $this->model_with();

            foreach ($with as $relationship => $constraints) {
                if (! Str::contains($relationship, '.')) {
                    $this->load($models, $relationship, $constraints);
                }
            }
        }

        return new Collection($models);
    }

    /**
     * Make an existing model of the given class from a database row, without eager loads.
     *
     * @param string $class
     * @param mixed  $row
     *
     * @return Model
     */
    protected function make($class, $row)
    {
        $model = new $class([], true);
        $model->fill_raw((array) $row);
        $model->sync();
        $model->fire_retrieved();

        if ($this instanceof Relationships\BelongsToMany) {
            $models = [$model];
            $this->hydrate_pivot($models);
        }

        return $model;
    }

    /**
     * Do a mass-assignment to the relationships that are eager loaded on the model.
     *
     * @param array      $results
     * @param string     $relationship
     * @param array|null $constraints
     */
    protected function load(array &$results, $relationship, $constraints)
    {
        $query = $this->resolve_relationship($relationship);

        if (method_exists($query, 'eager_load')) {
            $query->eager_load($results, $relationship, $constraints, $this->nested_with($relationship));
            return;
        }

        $query->model->with = $this->nested_with($relationship);
        $query->reset_constraints();
        $query->eagerly_constrain($results);

        if (! is_null($constraints)) {
            $query->table->where_nested($constraints);
        }

        $query->initialize($results, $relationship);
        $query->match($relationship, $results, $query->get()->all());
    }

    /**
     * Get the list of nested relationships for a given relationship.
     *
     * @param string $relationship
     *
     * @return array
     */
    protected function nested_with($relationship)
    {
        $nested = [];
        $with = $this->model_with();

        foreach ($with as $eagerload => $constraints) {
            if (Str::starts_with($eagerload, $relationship . '.')) {
                $key = substr((string) $eagerload, strlen((string) $relationship . '.'));
                $nested[$key] = $constraints;
            }
        }

        return $nested;
    }

    /**
     * Get the list of relationships that needs to be eager loaded on the model.
     *
     * @return array
     */
    protected function model_with()
    {
        $with = [];

        foreach ($this->model->with as $relationship => $constraints) {
            if (is_numeric($relationship)) {
                list($relationship, $constraints) = [$constraints, null];
            }

            $segments = explode('.', (string) $relationship);

            for ($i = 1, $count = count($segments); $i < $count; ++$i) {
                $parent = implode('.', array_slice($segments, 0, $i));

                if (! array_key_exists($parent, $with)) {
                    $with[$parent] = null;
                }
            }

            $with[$relationship] = $constraints;
        }

        return $with;
    }

    /**
     * Update the matching records, stamping updated_at when the model uses timestamps.
     * Bulk updates fire no model events.
     *
     * @param array $values
     *
     * @return int
     */
    public function update($values)
    {
        return $this->table->update($this->stamp($values));
    }

    /**
     * Increment a column, stamping updated_at when the model uses timestamps.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->table->increment($column, $amount, $this->stamp($extra));
    }

    /**
     * Decrement a column, stamping updated_at when the model uses timestamps.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->table->decrement($column, $amount, $this->stamp($extra));
    }

    /**
     * Add updated_at to the values when the model uses timestamps and it is missing.
     *
     * @param array $values
     *
     * @return array
     */
    protected function stamp(array $values)
    {
        $model = $this->model;

        if ($model && $model->timestamps()) {
            $column = $this->qualify('updated_at');

            if (! array_key_exists('updated_at', $values) && ! array_key_exists($column, $values)) {
                $values[$column] = Carbon::now()->format($model::$date_format);
            }
        }

        return $values;
    }

    /**
     * Qualify a column with the model table when the query has joins, so it is not ambiguous.
     *
     * @param string $column
     *
     * @return string
     */
    protected function qualify($column)
    {
        return empty($this->table->joins) ? $column : $this->model->table().'.'.$column;
    }

    /**
     * Delete the matching records; soft deletes when the model uses soft delete.
     * Bulk deletes fire no model events.
     *
     * @return int
     */
    public function delete()
    {
        $model = $this->model;

        if ($model && $model->soft_deleting()) {
            return $this->update([$this->qualify('deleted_at') => Carbon::now()->format($model::$date_format)]);
        }

        return $this->table->delete();
    }

    /**
     * Permanently delete the matching records, bypassing soft delete.
     *
     * @return int
     */
    public function force_delete()
    {
        return $this->table->delete();
    }

    /**
     * Get the query builder for the model's table.
     *
     * @return Query
     */
    protected function table()
    {
        return $this->model->_query($this->with_trashed);
    }

    /**
     * Get the database connection used by the model.
     *
     * @return \System\Database\Connection
     */
    public function connection()
    {
        return Database::connection($this->model->connection());
    }

    /**
     * Handle dynamic method calls into the query builder.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if ($this->model && method_exists($this->model, 'scope_'.$method)) {
            call_user_func_array([$this->model, 'scope_'.$method], array_merge([$this], $parameters));
            return $this;
        }

        $results = call_user_func_array([$this->table, $method], $parameters);
        return in_array($method, $this->passthru) ? $results : $this;
    }
}
