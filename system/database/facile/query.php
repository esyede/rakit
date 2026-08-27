<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Database;
use System\Collection;
use System\Database\Expression;
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
     * List of query builder methods that should be passed thru directly.
     * It means the result of these methods will be returned directly instead of
     * being wrapped in the model's query builder.
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
        'update',
        'increment',
        'delete',
        'decrement',
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

        // EXISTS is cheaper than counting, so use it whenever the wanted
        // condition boils down to "owns at least one" or "owns none at all".
        if ('>=' === $operator && 1 === $count) {
            $this->table->where_exists($sub, $connector);
            return $this;
        }

        if (('<' === $operator && 1 === $count) || ('=' === $operator && 0 === $count) || ('<=' === $operator && 0 === $count)) {
            $this->table->where_exists($sub, $connector, true);
            return $this;
        }

        $sub->select([new Expression('COUNT(*)')]);
        $sql = '(' . $sub->grammar->select($sub) . ') ' . $operator . ' ' . $count;
        $this->table->raw_where($sql, $sub->bindings, $connector);

        return $this;
    }

    /**
     * Constrain the query to models that own the given relationship,
     * with extra constraints on the relationship itself.
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
     * Constrain the query to models that do not own the given relationship,
     * with extra constraints on the relationship itself.
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
     * Select the number of related records as a '<relationship>_count' column.
     *
     * @param array|string $relationships
     *
     * @return Query
     */
    public function with_count($relationships)
    {
        $relationships = is_array($relationships) ? $relationships : func_get_args();

        if (is_null($this->table->selects)) {
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
            $sub->select([new Expression('COUNT(*)')]);

            $column = Str::snake($relationship) . '_count';
            $sql = '(' . $sub->grammar->select($sub) . ') AS ' . $this->table->grammar->wrap($column);

            $this->table->selects[] = new Expression($sql);
            $this->table->bindings = array_merge($this->table->bindings, $sub->bindings);
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
        if (! method_exists($this->model, $relationship)) {
            throw new \Exception(sprintf(
                'Undefined relationship on %s: %s',
                get_class($this->model),
                $relationship
            ));
        }

        $relation = $this->model->{$relationship}();

        if (! ($relation instanceof Relationships\Relationship)) {
            throw new \Exception(sprintf(
                'Method %s::%s() is not a relationship.',
                get_class($this->model),
                $relationship
            ));
        }

        $sub = $relation->correlate($this->model->table());
        $sub->select([new Expression('1')]);

        if (! is_null($callback)) {
            call_user_func($callback, $relation);
        }

        return $sub;
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
     * Run the given callback over the models, one chunk at a time.
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
            $results = $this->hydrate(
                $this->model,
                $this->table->copy()->for_page($page, $count)->get()
            );

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
     * Run the given callback over every single model.
     * Returning FALSE from the callback stops the iteration.
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
     * Do a mass-assignment of the given results to model instances.
     *
     * @param Model            $model
     * @param array|\Traversable $results
     *
     * @return \System\Collection
     */
    public function hydrate($model, $results)
    {
        $model = get_class($model);
        $models = [];

        foreach ($results as $result) {
            $model = new $model([], true);
            $model->fill_raw((array) $result);
            $model->sync();
            $models[] = $model;
        }

        if (count($results) > 0) {
            $with = $this->model_with();

            foreach ($with as $relationship => $constraints) {
                if (! Str::contains($relationship, '.')) {
                    $this->load($models, $relationship, $constraints);
                }
            }
        }

        if ($this instanceof Relationships\BelongsToMany) {
            $this->hydrate_pivot($models);
        }

        return new Collection($models);
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
        $query = $this->model->{$relationship}();

        if (method_exists($query, 'eager_load')) {
            $query->eager_load($results, $relationship);
            return;
        }

        $query->model->with = $this->nested_with($relationship);
        $query->table->reset_where();
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

            $with[$relationship] = $constraints;
        }

        return $with;
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
        $results = call_user_func_array([$this->table, $method], $parameters);
        return in_array($method, $this->passthru) ? $results : $this;
    }
}
