<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Database;
use System\Database\Exceptions\ModelNotFoundException;

class Query
{
    /**
     * Berisi intsance model yang sedang dioperasikan.
     *
     * @var Model
     */
    public $model;

    /**
     * Berisi query builder untuk instnce query.
     *
     * @var Query
     */
    public $table;

    /**
     * Berisi list relasi yng harus di eagerload.
     *
     * @var array
     */
    public $with = [];

    /**
     * List method yang harus direturn dari query builder.
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
        'order_by',
        'where_in',
        'where_not_in',
        'or_where_in',
        'or_where_not_in',
        'to_sql',
        'debug',
        'exists',
        'doesnt_exist',
    ];

    /**
     * Buat instance quer baru untuk model.
     *
     * @param Model $model
     */
    public function __construct($model)
    {
        $this->model = ($model instanceof Model) ? $model : new $model();
        $this->table = $this->table();
    }

    /**
     * Cari model berdasarkan primary key-nya.
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
     * Ambil model pertama yang cocok dengan query.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $results = $this->hydrate($this->model, $this->table->take(1)->get($columns));

        return (count($results) > 0) ? head($results) : null;
    }

    /**
     * Cari model berdasarkan primary key-nya atau throw exception jika tidak ditemukan.
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
     * Ambil model pertama yang cocok dengan query atau throw exception jika tidak ditemukan.
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
     * Ambil seluruh model yang cocok dengan query.
     *
     * @param array $columns
     *
     * @return array
     */
    public function get($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        return $this->hydrate($this->model, $this->table->get($columns));
    }

    /**
     * Ambil generator untuk iterate model satu per satu (memory efficient).
     * Menggunakan generator (PHP 5.5+) untuk efisiensi memori.
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
     * Ambil array model berpaginasi hasil query.
     *
     * @param int   $perpage
     * @param array $columns
     *
     * @return Paginator
     */
    public function paginate($perpage = null, array $columns = ['*'])
    {
        $perpage = $perpage ?: $this->model->perpage();
        $paginator = $this->table->paginate($perpage, $columns);
        $paginator->results = $this->hydrate($this->model, $paginator->results);

        return $paginator;
    }

    /**
     * Lakukan mass-assignment pada model.
     *
     * @param Model $model
     * @param array $results
     *
     * @return array
     */
    public function hydrate($model, array $results)
    {
        $model = get_class($model);
        $models = [];

        foreach ($results as $result) {
            $model = new $model([], true);
            $model->fill_raw((array) $result);
            $models[] = $model;
        }

        if (count($results) > 0) {
            $with = $this->model_with();

            foreach ($with as $relationship => $constraints) {
                if (!Str::contains($relationship, '.')) {
                    $this->load($models, $relationship, $constraints);
                }
            }
        }

        if ($this instanceof Relationships\BelongsToMany) {
            $this->hydrate_pivot($models);
        }

        return $models;
    }

    /**
     * Lakukan mass-assignment ke relasi yang di eagerload pada model.
     *
     * @param array      $results
     * @param string     $relationship
     * @param array|null $constraints
     */
    protected function load(array &$results, $relationship, $constraints)
    {
        $query = $this->model->{$relationship}();
        $query->model->with = $this->nested_with($relationship);
        $query->table->reset_where();
        $query->eagerly_constrain($results);

        if (!is_null($constraints)) {
            $query->table->where_nested($constraints);
        }

        $query->initialize($results, $relationship);
        $query->match($relationship, $results, $query->get());
    }

    /**
     * Kumpulkan nested eagerload miik relasi yang diberikan.
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
     * Ambil list relasi yang di eagerload pada model.
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
     * Ambil query builder milik model.
     *
     * @return Query
     */
    protected function table()
    {
        return $this->connection()->table($this->model->table());
    }

    /**
     * Ambil koneksi database milik model.
     *
     * @return Connection
     */
    public function connection()
    {
        return Database::connection($this->model->connection());
    }

    /**
     * Tangani pemanggilan method query secara dinamis.
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
