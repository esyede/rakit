<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Event;
use System\Carbon;
use System\Validator;
use System\Database\Exceptions\ModelNotFoundException;

abstract class Model
{
    /**
     * Berisi selruh atribut milik model.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Berisi selruh atribut milik model dalam kondisi asli.
     *
     * @var array
     */
    public $original = [];

    /**
     * Berisi list relasi model.
     *
     * @var array
     */
    public $relationships = [];

    /**
     * Penanda bahwa model ada di database.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Berisi list relasi yang harus di eagerload.
     *
     * @var array
     */
    public $with = [];

    /**
     * Nama kolom primary key milik si model.
     *
     * @var string
     */
    public static $key = 'id';

    /**
     * Berisi list atribut model yang boleh di mass-assigment.
     *
     * @var array
     */
    public static $fillable;

    /**
     * Berisi list atribut model yang tidak boleh di mass-assigment.
     *
     * @var array
     */
    public static $guarded = [];

    /**
     * Penanda bahwa model ini menggunakan soft deletes.
     *
     * @var bool
     */
    public static $soft_delete = false;

    /**
     * Berisi list atribut yang harus disembunyikan ketika memanggil method to_array().
     *
     * @var array
     */
    public static $hidden = [];

    /**
     * Penanda bahwa model ini memiliki kolom timestamp created / updated at.
     *
     * @var bool
     */
    public static $timestamps = true;

    /**
     * Berisi nama tabel yang sedang digunakan.
     *
     * @var string
     */
    public static $table;

    /**
     * Berisi nama koneksi database yang sedang digunakan.
     *
     * @var string
     */
    public static $connection;

    /**
     * Berisi nama sequence yang sedang digunakan.
     *
     * @var string
     */
    public static $sequence;

    /**
     * Jumlah item yang harus ditampilkan per halaman (untuk paginasi).
     *
     * @var int
     */
    public static $perpage = 20;

    /**
     * Berisi array nama field dan rules untuk kebutuhan validasi data model.
     * Anda bisa melakukan validasi data inputan menggunakan method ini.
     *
     * @var array
     */
    public static $rules = [];

    /**
     * Berisi array pesan error validasi.
     *
     * @var array
     */
    public static $messages = [];

    /**
     * Berisi object kelas Validator setelah user memanggil method is_valid().
     * Object ini bisa diakses secara publik sehingga dapat digunakan untuk
     * redirect dengan pesan error.
     *
     * @var bool|\System\Validator
     */
    public $validation = false;

    /**
     * Buat instance model baru.
     *
     * @param array $attributes
     * @param bool  $exists
     */
    public function __construct(array $attributes = [], $exists = false)
    {
        $this->exists = $exists;
        $this->fill($attributes);

        if ($exists) {
            $this->original = $this->attributes;
        }
    }

    /**
     * Validasi model terhadap inputan user.
     *
     * <code>
     *
     *      // Definisi di model:
     *
     *      class User extends Facile
     *      {
     *          public static $fillable = ['name', 'address'];
     *
     *          public static $rules = [
     *              'name' => 'required|alpha|min:2|max:100',
     *              'address' => 'required|min:3|max:255',
     *              'password' => 'required|min:8|max:255',
     *          ];
     *      }
     *
     *
     *      // Pemanggilan dari dalam kontroler:
     *
     *      $user = new User(Input::all());
     *      $user->name = 'Budi Purnomo';
     *      $user->address = 'Jln. Semangka No. 23';
     *
     *      if (! $user->is_valid()) {
     *          return Redirect::back()->with_input()->with_errors($user->validation);
     *      }
     *
     *      $user->save();
     *
     * </code>
     *
     * @return bool
     */
    public function is_valid()
    {
        if (empty(static::$rules)) {
            return true;
        }

        $this->validation = Validator::make($this->attributes, static::$rules, static::$messages);
        return $this->validation->passes();
    }

    /**
     * Lakukan mass-assignment ke model saat ini.
     *
     * @param array $attributes
     * @param bool  $raw
     *
     * @return Model
     */
    public function fill(array $attributes, $raw = false)
    {
        foreach ($attributes as $key => $value) {
            if ($raw) {
                $this->set_attribute($key, $value);
                continue;
            }

            if (is_array(static::$guarded) && in_array($key, static::$guarded)) {
                continue;
            }

            if (is_array(static::$fillable)) {
                if (in_array($key, static::$fillable)) {
                    $this->{$key} = $value;
                }
            } else {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Lakukan mass-assignment ke model saat ini.
     * Seluruh mutator dan accessor akan diabaikan.
     *
     * @param array $attributes
     *
     * @return Model
     */
    public function fill_raw(array $attributes)
    {
        return $this->fill($attributes, true);
    }

    /**
     * Set atribut pada model.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function set_attribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get atribut dari model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get_attribute($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * Ambil nama koneksi database yang sedang digunakan.
     *
     * @return string
     */
    public function connection()
    {
        return static::$connection;
    }

    /**
     * Ambil nama tabel yang sedang digunakan.
     *
     * @return string
     */
    public function table()
    {
        if (static::$table) {
            return static::$table;
        }

        $class = get_called_class();
        // Hapus namespace jika ada
        $class = (false !== strpos($class, '\\')) ? basename(str_replace('\\', '/', $class)) : $class;

        return strtolower(Str::plural($class));
    }

    /**
     * Ambil nama kolom primary key.
     *
     * @return string
     */
    public function key()
    {
        return static::$key;
    }

    /**
     * Ambil jumlah item per halaman untuk paginasi.
     *
     * @return int
     */
    public function perpage()
    {
        return static::$perpage;
    }

    /**
     * Ambil nilai primary key.
     *
     * @return mixed
     */
    public function get_key()
    {
        return isset($this->attributes[static::$key]) ? $this->attributes[static::$key] : null;
    }

    /**
     * Set nilai primary key.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function set_key($value)
    {
        $this->attributes[static::$key] = $value;
    }

    /**
     * Ambil atribut yang telah berubah.
     *
     * @return array
     */
    public function get_dirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Check apakah model memiliki atribut yang berubah.
     *
     * @return bool
     */
    public function dirty()
    {
        return count($this->get_dirty()) > 0;
    }

    /**
     * Sinkronkan atribut original dengan atribut saat ini.
     *
     * @return $this
     */
    public function sync()
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Check apakah atribut tertentu telah berubah.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function changed($attribute)
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            return false;
        }

        if (!array_key_exists($attribute, $this->original)) {
            return false;
        }

        return $this->attributes[$attribute] !== $this->original[$attribute];
    }

    /**
     * Hapus atribut dari model.
     *
     * @param string $key
     *
     * @return void
     */
    public function purge($key)
    {
        unset($this->attributes[$key], $this->original[$key]);
    }

    /**
     * Convert model dan relasinya menjadi array.
     *
     * @return array
     */
    public function to_array()
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (is_array(static::$hidden) && in_array($key, static::$hidden)) {
                continue;
            }

            $attributes[$key] = $value;
        }

        foreach ($this->relationships as $key => $value) {
            if (is_array(static::$hidden) && in_array($key, static::$hidden)) {
                continue;
            }

            if (is_array($value)) {
                $attributes[$key] = array_map(function ($item) {
                    return ($item instanceof Model) ? $item->to_array() : $item;
                }, $value);
            } elseif ($value instanceof Model) {
                $attributes[$key] = $value->to_array();
            } else {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Ambil nama tabel yang sedang digunakan (alias untuk table()).
     *
     * @return string
     */
    public function get_table()
    {
        return $this->table();
    }

    /**
     * Ambil nama koneksi database yang sedang digunakan (alias untuk connection()).
     *
     * @return string
     */
    public function get_connection_name()
    {
        return $this->connection();
    }

    /**
     * Check apakah model menggunakan timestamps.
     *
     * @return bool
     */
    public function timestamps()
    {
        return static::$timestamps;
    }

    /**
     * Set list atribut yang boleh diisi data.
     *
     * @param array $attributes
     */
    public static function fillable(array $attributes = [])
    {
        if (empty($attributes)) {
            return static::$fillable;
        }

        static::$fillable = $attributes;
    }

    /**
     * Buat model baru dan simpan ke database.
     * Jika model berhasil disimpan, data model akan direturn. FALSE jika sebaliknya.
     *
     * @param array $attributes
     *
     * @return Model|false
     */
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        return $model->save() ? $model : false;
    }

    /**
     * Update model di database.
     *
     * @param mixed $id
     * @param array $attributes
     *
     * @return int
     */
    public static function update($id, array $attributes)
    {
        $model = new static([], true);
        $model->fill($attributes);

        if (static::$timestamps) {
            $model->updated_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        return $model->query()->where($model->key(), '=', $id)->update($model->attributes);
    }

    /**
     * Ambil seluruh model di database.
     *
     * @return array
     */
    public static function all()
    {
        return (new static())->query()->get();
    }

    /**
     * Cari model berdasarkan primary key-nya.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return Model|null
     */
    public static function find($id, array $columns = ['*'])
    {
        return (new static())->query()->find($id, $columns);
    }

    /**
     * Cari model berdasarkan primary key-nya atau throw exception jika tidak ditemukan.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @return Model
     */
    public static function find_or_fail($id, array $columns = ['*'])
    {
        $result = static::find($id, $columns);

        if (is_null($result)) {
            throw new ModelNotFoundException(get_called_class() . ' with id ' . $id . ' not found.');
        }

        return $result;
    }

    /**
     * Ambil model pertama yang cocok dengan query.
     *
     * @param array $columns
     *
     * @return Model|null
     */
    public static function first(array $columns = ['*'])
    {
        return (new static())->query()->first($columns);
    }

    /**
     * Ambil model pertama yang cocok dengan query atau throw exception jika tidak ditemukan.
     *
     * @param array $columns
     *
     * @return Model
     */
    public static function first_or_fail(array $columns = ['*'])
    {
        $result = static::first($columns);

        if (is_null($result)) {
            throw new ModelNotFoundException(get_called_class() . ' not found.');
        }

        return $result;
    }

    /**
     * Buat query builder dengan klausa WHERE.
     *
     * @param string|array $column
     * @param mixed        $operator
     * @param mixed        $value
     * @param string       $boolean
     *
     * @return \System\Database\Facile\Query
     */
    public static function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        return (new static())->query()->where($column, $operator, $value, $boolean);
    }

    /**
     * Set list relasi yang harus di eagerload.
     *
     * @param array $with
     *
     * @return Model
     */
    public function _with($with)
    {
        $this->with = is_array($with) ? $with : func_get_args();
        return $this;
    }

    /**
     * Ambil query untuk relasi one-to-one (satu-ke-satu).
     *
     * @param string $model
     * @param string $foreign
     *
     * @return Relationship
     */
    public function has_one($model, $foreign = null)
    {
        return new Relationships\HasOne($this, $model, $foreign);
    }

    /**
     * Ambil query untuk relasi one-to-many (satu-ke-banyak).
     *
     * @param string $model
     * @param string $foreign
     *
     * @return Relationship
     */
    public function has_many($model, $foreign = null)
    {
        return new Relationships\HasMany($this, $model, $foreign);
    }

    /**
     * Ambil query untuk relasi has-many-through.
     *
     * @param string $model
     * @param string $through
     * @param string $first_key
     * @param string $second_key
     * @param string $local_key
     * @param string $second_local_key
     *
     * @return Relationships\HasManyThrough
     */
    public function has_many_through(
        $model,
        $through,
        $first_key = null,
        $second_key = null,
        $local_key = null,
        $second_local_key = null
    ) {
        return new Relationships\HasManyThrough(
            $this,
            $model,
            $through,
            $first_key,
            $second_key,
            $local_key,
            $second_local_key
        );
    }

    /**
     * Ambil query untuk relasi one-to-one (inverse).
     *
     * @param string $model
     * @param string $foreign
     *
     * @return Relationship
     */
    public function belongs_to($model, $foreign = null)
    {
        $foreign = is_null($foreign) ? 'belongs_to_id' : $foreign;
        return new Relationships\BelongsTo($this, $model, $foreign);
    }

    /**
     * Ambil query untuk relasi one-to-many.
     *
     * @param string $model
     * @param string $table
     * @param string $foreign
     * @param string $other
     *
     * @return Relationships\BelongsToMany
     */
    public function belongs_to_many($model, $table = null, $foreign = null, $other = null)
    {
        return new Relationships\BelongsToMany($this, $model, $table, $foreign, $other);
    }

    /**
     * Ambil query untuk relasi polymorphic one-to-one.
     *
     * @param string $model
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $foreign
     *
     * @return Relationships\MorphOne
     */
    public function morph_one($model, $name, $type = null, $id = null, $foreign = null)
    {
        $type = is_null($type) ? $name . '_type' : $type;
        $id = is_null($id) ? $name . '_id' : $id;
        return new Relationships\MorphOne($this, $model, $type, $id, $foreign);
    }

    /**
     * Ambil query untuk relasi polymorphic one-to-many.
     *
     * @param string $model
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $foreign
     *
     * @return Relationships\MorphMany
     */
    public function morph_many($model, $name, $type = null, $id = null, $foreign = null)
    {
        $type = is_null($type) ? $name . '_type' : $type;
        $id = is_null($id) ? $name . '_id' : $id;
        return new Relationships\MorphMany($this, $model, $type, $id, $foreign);
    }

    /**
     * Ambil query untuk relasi polymorphic belongs-to (inverse).
     *
     * @param string $name
     * @param string $type
     * @param string $id
     *
     * @return Relationships\MorphTo
     */
    public function morph_to($name, $type = null, $id = null)
    {
        $type = is_null($type) ? $name . '_type' : $type;
        $id = is_null($id) ? $name . '_id' : $id;
        $model = $this->get_attribute($type);
        return new Relationships\MorphTo($this, $model, $type, $id);
    }

    /**
     * Ambil query untuk relasi polymorphic many-to-many.
     *
     * @param string $model
     * @param string $name
     * @param string $table
     * @param string $foreign
     * @param string $other
     *
     * @return Relationships\MorphToMany
     */
    public function morph_to_many($model, $name, $table = null, $foreign = null, $other = null)
    {
        return new Relationships\MorphToMany($this, $model, $name, $table, $foreign, $other);
    }

    /**
     * Simpan model dan seluruh relasinya ke database.
     *
     * @return bool
     */
    public function push()
    {
        $this->save();

        foreach ($this->relationships as $name => $models) {
            $models = is_array($models) ? $models : [$models];

            foreach ($models as $model) {
                $model->push();
            }
        }
    }

    /**
     * Simpan model ke database.
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->dirty()) {
            return true;
        }

        if (static::$timestamps) {
            if (!$this->exists) {
                $this->created_at = Carbon::now()->format('Y-m-d H:i:s');
            }

            $this->updated_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        Event::fire(['facile.saving', 'facile.saving: ' . get_class($this)], [$this]);

        if ($this->exists) {
            $query = $this->query()->where(static::$key, '=', $this->get_key());
            $result = (1 === $query->update($this->get_dirty()));

            if ($result) {
                Event::fire(['facile.updated', 'facile.updated: ' . get_class($this)], [$this]);
            }
        } else {
            $id = $this->query()->insert_get_id($this->attributes, $this->key());
            $this->set_key($id);
            $key = $this->get_key();
            $result = !is_null($key) && !empty($key);
            $this->exists = $result;

            if ($result) {
                Event::fire(['facile.created', 'facile.created: ' . get_class($this)], [$this]);
            }
        }

        $this->original = $this->attributes;

        if ($result) {
            Event::fire(['facile.saved', 'facile.saved: ' . get_class($this)], [$this]);
        }

        return $result;
    }

    /**
     * Hapus model dari database.
     *
     * @return int
     */
    public function delete()
    {
        if ($this->exists) {
            Event::fire(['facile.deleting', 'facile.deleting: ' . get_class($this)], [$this]);

            if (static::$soft_delete) { // Soft delete
                $this->deleted_at = Carbon::now()->format('Y-m-d H:i:s');
                $result = $this->query()
                    ->where(static::$key, '=', $this->get_key())
                    ->update(['deleted_at' => $this->deleted_at]);
                $this->exists = false;
                Event::fire(['facile.deleted', 'facile.deleted: ' . get_class($this)], [$this]);
            } else { // Hard delete
                $result = $this->query()->where(static::$key, '=', $this->get_key())->delete();
                Event::fire(['facile.deleted', 'facile.deleted: ' . get_class($this)], [$this]);
            }

            return $result;
        }
    }

    /**
     * Restore soft deleted model.
     *
     * @return bool
     */
    public function restore()
    {
        if (static::$soft_delete && !$this->exists && !is_null($this->deleted_at)) {
            $result = $this->query()->where(static::$key, '=', $this->get_key())->update(['deleted_at' => null]);

            if ($result) {
                $this->deleted_at = null;
                $this->exists = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Force delete model (bypass soft delete).
     *
     * @return int
     */
    public function force_delete()
    {
        if ($this->exists || !is_null($this->deleted_at)) {
            Event::fire(['facile.deleting', 'facile.deleting: ' . get_class($this)], [$this]);
            $result = $this->query()->where(static::$key, '=', $this->get_key())->delete();
            Event::fire(['facile.deleted', 'facile.deleted: ' . get_class($this)], [$this]);

            return $result;
        }
    }

    /**
     * Check if model is soft deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return static::$soft_delete && !is_null($this->deleted_at);
    }

    /**
     * Reload model from database.
     *
     * @return Model
     */
    public function fresh()
    {
        return $this->exists ? static::find($this->get_key()) : null;
    }

    /**
     * Reload model from database (alias for fresh()).
     *
     * @return Model
     */
    public function reload()
    {
        return $this->fresh();
    }

    /**
     * Get query builder with trashed records included.
     *
     * @return Query
     */
    public static function with_trashed()
    {
        return (new static())->query();
    }

    /**
     * Get query builder with only trashed records.
     *
     * @return Query
     */
    public static function only_trashed()
    {
        return (new static())->query()->where_not_null('deleted_at');
    }

    /**
     * Ambil instance Facile query builder baru.
     *
     * @return \System\Database\Facile\Query
     */
    public function query()
    {
        return new \System\Database\Facile\Query($this);
    }

    /**
     * Apply global scopes to query.
     *
     * @param Query $query
     *
     * @return Query
     */
    protected function apply_scopes($query)
    {
        // Apply segala global scopes disini jika ada
        return $query;
    }

    /**
     * Ambil instance query builder baru.
     *
     * @return Query
     */
    protected function _query()
    {
        $query = (new Query($this))->connection()->table($this->table());

        // Auto-apply soft delete filter
        if (static::$soft_delete) {
            $query->where_null('deleted_at');
        }

        // Apply global scopes
        return $this->apply_scopes($query);
    }

    /**
     * Handle static method calls.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([(new static())->query(), $method], $parameters);
    }

    /**
     * Handle dynamic property access for getting attributes.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get_attribute($key);
    }

    /**
     * Handle dynamic property access for setting attributes.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set_attribute($key, $value);
    }
}
