<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Str;
use System\Event;
use System\Database;
use System\Validator;

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
    public function __construct($attributes = [], $exists = false)
    {
        $this->exists = $exists;
        $this->fill($attributes);
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

            if (is_array(static::$fillable)) {
                if (in_array($key, static::$fillable)) {
                    $this->{$key} = $value;
                }
            } else {
                $this->{$key} = $value;
            }
        }

        if (0 === count($this->original)) {
            $this->original = $this->attributes;
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
     * Set list atribut yang boleh diisi data.
     *
     * @param array $attributes
     */
    public static function fillable($attributes = null)
    {
        if (is_null($attributes)) {
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
    public static function create($attributes)
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
    public static function update($id, $attributes)
    {
        $model = new static([], true);
        $model->fill($attributes);

        if (static::$timestamps) {
            $model->timestamp();
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
     * Set list relasi yang harus di eagerload.
     *
     * @param array $with
     *
     * @return Model
     */
    public function _with($with)
    {
        $this->with = (array) $with;
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
     * Ambil query untuk relasi one-to-one (inverse).
     *
     * @param string $model
     * @param string $foreign
     *
     * @return Relationship
     */
    public function belongs_to($model, $foreign = null)
    {
        if (is_null($foreign)) {
            list($unused, $caller) = debug_backtrace(false);
            $foreign = $caller['function'].'_id';
            unset($unused, $caller);
        }

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
     * @return Relationships\HasManyAndBelongsTo
     */
    public function has_many_and_belongs_to($model, $table = null, $foreign = null, $other = null)
    {
        return new Relationships\HasManyAndBelongsTo($this, $model, $table, $foreign, $other);
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
        if (! $this->dirty()) {
            return true;
        }

        if (static::$timestamps) {
            $this->timestamp();
        }

        Event::fire(['facile.saving', 'facile.saving: '.get_class($this)], [$this]);

        if ($this->exists) {
            $query = $this->query()->where(static::$key, '=', $this->get_key());
            $result = (1 === $query->update($this->get_dirty()));

            if ($result) {
                Event::fire(['facile.updated', 'facile.updated: '.get_class($this)], [$this]);
            }
        } else {
            $id = $this->query()->insert_get_id($this->attributes, $this->key());
            $this->set_key($id);
            $this->exists = $result = is_numeric($this->get_key());

            if ($result) {
                Event::fire(['facile.created', 'facile.created: '.get_class($this)], [$this]);
            }
        }

        $this->original = $this->attributes;

        if ($result) {
            Event::fire(['facile.saved', 'facile.saved: '.get_class($this)], [$this]);
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
            Event::fire(['facile.deleting', 'facile.deleting: '.get_class($this)], [$this]);

            $result = $this->query()->where(static::$key, '=', $this->get_key())->delete();

            Event::fire(['facile.deleted', 'facile.deleted: '.get_class($this)], [$this]);

            return $result;
        }
    }

    /**
     * Update timestamp milik model.
     */
    public function timestamp()
    {
        $this->updated_at = new \DateTime();

        if (! $this->exists) {
            $this->created_at = $this->updated_at;
        }
    }

    /**
     * Update timestamp milik model dan langsung simpan (tanpa mengubah kolom lain).
     */
    public function touch()
    {
        $this->timestamp();
        $this->save();
    }

    /**
     * Ambil instance query builder baru.
     *
     * @return Query
     */
    protected function _query()
    {
        return new Query($this);
    }

    /**
     * Timpa atribut asli dengan yang baru.
     *
     * @return bool
     */
    final public function sync()
    {
        $this->original = $this->attributes;
        return true;
    }

    /**
     * Cek apakah ada perubahan yang dilakukan pada atribut.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function changed($attribute)
    {
        return Arr::get($this->attributes, $attribute) !== Arr::get($this->original, $attribute);
    }

    /**
     * Cek apakah sudah ada perubahan model dari kondisi aslinya (atau istilahnya 'dirty').
     * Model yang belum disimpan ke database akan selalu dianggap 'dirty'.
     *
     * @return bool
     */
    public function dirty()
    {
        return (! $this->exists || count($this->get_dirty()) > 0);
    }

    /**
     * Ambil nama tabel yang digunakan.
     *
     * @return string
     */
    public function table()
    {
        return static::$table ? static::$table : strtolower(Str::plural(class_basename($this)));
    }

    /**
     * Ambil atribut - atribut 'dirty' milik model.
     *
     * @return array
     */
    public function get_dirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Ambil value dari kolom primary key milik model.
     *
     * @return int
     */
    public function get_key()
    {
        return Arr::get($this->attributes, static::$key);
    }

    /**
     * Set value kolom primary key milik model.
     *
     * @param int $value
     */
    public function set_key($value)
    {
        return $this->set_attribute(static::$key, $value);
    }

    /**
     * Ambil value atribut milik model.
     *
     * @param string $key
     */
    public function get_attribute($key)
    {
        return Arr::get($this->attributes, $key);
    }

    /**
     * Set value atribut milik model.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set_attribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Hapus atribut dari model.
     *
     * @param string $key
     */
    final public function purge($key)
    {
        unset($this->original[$key], $this->attributes[$key]);
    }

    /**
     * Ambil atribut dan relasi model dalam bentuk array.
     *
     * @return array
     */
    public function to_array()
    {
        $attributes = [];
        $keys = array_keys($this->attributes);

        foreach ($keys as $attribute) {
            if (! in_array($attribute, static::$hidden)) {
                $attributes[$attribute] = $this->{$attribute};
            }
        }

        foreach ($this->relationships as $name => $models) {
            if (in_array($name, static::$hidden)) {
                continue;
            }

            if ($models instanceof Model) {
                $attributes[$name] = $models->to_array();
            } elseif (is_array($models)) {
                $attributes[$name] = [];

                foreach ($models as $id => $model) {
                    $attributes[$name][$id] = $model->to_array();
                }
            } elseif (is_null($models)) {
                $attributes[$name] = $models;
            }
        }

        return $attributes;
    }

    /**
     * Tangani pemanggilan dinamis getter atribut dan relasi.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        } elseif (array_key_exists($key, $this->attributes)) {
            return $this->{'get_'.$key}();
        } elseif (method_exists($this, $key)) {
            return $this->relationships[$key] = $this->{$key}()->results();
        }

        return $this->{'get_'.$key}();
    }

    /**
     * Tangani pemanggilan dinamis setter atribut dan relasi.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->{'set_'.$key}($value);
    }

    /**
     * Cek apakah atribut yang diberikn ada di dalam model.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        $sources = ['attributes', 'relationships'];

        foreach ($sources as $source) {
            if (array_key_exists($key, $this->{$source})) {
                return (! empty($this->{$source}[$key]));
            }
        }

        return false;
    }

    /**
     * Hapus sebuah atribut dari model.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relationships[$key]);
    }

    /**
     * Tangani pemanggilan method dinamis pada model.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $methods = ['key', 'table', 'connection', 'sequence', 'perpage', 'timestamps'];

        if (in_array($method, $methods)) {
            return static::${$method};
        }

        $underscored = ['with', 'query'];

        if (in_array($method, $underscored)) {
            return call_user_func_array([$this, '_'.$method], $parameters);
        }

        if (Str::starts_with($method, 'get_')) {
            return $this->get_attribute(substr($method, 4));
        } elseif (Str::starts_with($method, 'set_')) {
            $this->set_attribute(substr($method, 4), $parameters[0]);
        } else {
            return call_user_func_array([$this->query(), $method], $parameters);
        }
    }

    /**
     * Tangani secara dinamis pemanggilan method statis pada model.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $model = get_called_class();
        $model = new $model();

        return call_user_func_array([$model, $method], $parameters);
    }
}
