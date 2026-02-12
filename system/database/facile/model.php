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
     * Contains all attributes of the model.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Contains original attributes of the model for change tracking.
     *
     * @var array
     */
    public $original = [];

    /**
     * Contains loaded relationships of the model.
     *
     * @var array
     */
    public $relationships = [];

    /**
     * Determine whether the model exists in the database.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Contains list of relationships to eager load.
     *
     * @var array
     */
    public $with = [];

    /**
     * Model's primary key column name.
     *
     * @var string
     */
    public static $key = 'id';

    /**
     * Contains list of attributes that are mass-assignable.
     *
     * @var array
     */
    public static $fillable;

    /**
     * Contains list of attributes that are not mass-assignable.
     *
     * @var array
     */
    public static $guarded = [];

    /**
     * Determine whether the model uses soft deletes.
     *
     * @var bool
     */
    public static $soft_delete = false;

    /**
     * Contains list of attributes that should be hidden when the model is converted to an array or JSON.
     *
     * @var array
     */
    public static $hidden = [];

    /**
     * Determine whether the model uses timestamps for created_at and updated_at columns.
     *
     * @var bool
     */
    public static $timestamps = true;

    /**
     * Contains the table name that is associated with the model.
     *
     * @var string
     */
    public static $table;

    /**
     * Contains the database connection name that the model uses.
     *
     * @var string
     */
    public static $connection;

    /**
     * Contains the sequence name for databases that use sequences (e.g., PostgreSQL).
     *
     * @var string
     */
    public static $sequence;

    /**
     * Number of items per page for pagination.
     *
     * @var int
     */
    public static $perpage = 20;

    /**
     * Contains global scopes that are applied to all queries for the model.
     *
     * @var array
     */
    protected static $global_scopes = [];

    /**
     * Contains array of validation rules for the model.
     *
     * @var array
     */
    public static $rules = [];

    /**
     * Contains custom validation messages for the model.
     *
     * @var array
     */
    public static $messages = [];

    /**
     * Contains the validation instance after running is_valid().
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
     * Validate model's attributes using the defined rules.
     *
     * <code>
     *
     *      class User extends Facile
     *      {
     *          public static $rules = [
     *              'name' => 'required|alpha|min:2|max:100',
     *              'address' => 'required|min:3|max:255',
     *              'password' => 'required|min:8|max:255',
     *          ];
     *      }
     *
     *
     *      // Calling the is_valid() method from the controller:
     *
     *      $user = new User(Input::all());
     *      $user->name = 'Budi Purnomo';
     *      $user->address = 'Jln. Semangka No. 23';
     *
     *      if (!$user->is_valid()) {
     *          return back()->with_input()->with_errors($user->validation);
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
     * Do mass-assignment to the current model.
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
     * Do mass-assignment to the current model without applying fillable/guarded rules.
     * All mutators and accessors will be ignored too.
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
     * Set a model's attribute value.
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
     * Get a model's attribute value.
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
     * Get the name of the database connection being used.
     *
     * @return string
     */
    public function connection()
    {
        return static::$connection;
    }

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public function table()
    {
        if (static::$table) {
            return static::$table;
        }

        $class = get_called_class();
        // Remove the namespace if present
        $class = (false !== strpos($class, '\\')) ? basename(str_replace('\\', '/', $class)) : $class;

        return strtolower(Str::plural($class));
    }

    /**
     * Get the primary key column name.
     *
     * @return string
     */
    public function key()
    {
        return static::$key;
    }

    /**
     * Get the number of items per page for pagination.
     *
     * @return int
     */
    public function perpage()
    {
        return static::$perpage;
    }

    /**
     * Get the value of primary key.
     *
     * @return mixed
     */
    public function get_key()
    {
        return isset($this->attributes[static::$key]) ? $this->attributes[static::$key] : null;
    }

    /**
     * Set the value of primary key.
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
     * Get the attributes that have been changed since last sync.
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
     * Check if the model has any changed attributes.
     *
     * @return bool
     */
    public function dirty()
    {
        return count($this->get_dirty()) > 0;
    }

    /**
     * Sync the original attributes with the current attributes.
     *
     * @return $this
     */
    public function sync()
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Check if a specific attribute has been changed.
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
     * Unset an attribute from the model.
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
     * Convert the model and its relationships into an array.
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
     * Get the table name associated with the model (alias for table()).
     *
     * @return string
     */
    public function get_table()
    {
        return $this->table();
    }

    /**
     * Get the name of database connection being used (alias for connection()).
     *
     * @return string
     */
    public function get_connection_name()
    {
        return $this->connection();
    }

    /**
     * Check if the model uses timestamps.
     *
     * @return bool
     */
    public function timestamps()
    {
        return static::$timestamps;
    }

    /**
     * Set the attributes that are mass-assignable.
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
     * Create a new model and save it to the database.
     * If the model is successfully saved, the model instance will be returned. FALSE otherwise.
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
     * Update the model in the database with the given attributes.
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
     * Get all models from the database.
     *
     * @return array
     */
    public static function all()
    {
        return (new static())->query()->get();
    }

    /**
     * Find a model by its primary key.
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
     * Find a model by its primary key or throw exception if not found.
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
     * Get the first model that matches the query.
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
     * Get the first model that matches the query or throw exception if not found.
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
     * Make a basic where clause on the model's query.
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
     * Set the list of relationships to eager load on the model.
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
     * Get the query for a one-to-one relationship.
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
     * Get the query for a one-to-many relationship.
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
     * Get the query for a has-many-through relationship.
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
     * Get the query for a belongs-to relationship.
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
     * Get the query for a belongs-to-many relationship.
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
     * Get the query for a polymorphic one-to-one relationship.
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
     * Get the query for a polymorphic one-to-many relationship.
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
     * Get the query for a polymorphic morph-to relationship.
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
     * Get the query for a polymorphic many-to-many relationship.
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
     * Save the model and all of its relationships to the database.
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
     * Save the model to the database.
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
     * Delete the model from the database.
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
     * Restore a soft-deleted model.
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
     * Force delete a model from the database (bypass soft delete).
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
     * Get a new query builder for the model.
     *
     * @return \System\Database\Facile\Query
     */
    public function query()
    {
        return new \System\Database\Facile\Query($this);
    }

    /**
     * Apply all global scopes to the query.
     *
     * @param Query $query
     *
     * @return Query
     */
    protected function apply_scopes($query)
    {
        foreach (static::$global_scopes as $scope) {
            if ($scope instanceof \Closure) {
                $scope($query);
            } elseif (is_object($scope) && method_exists($scope, 'apply')) {
                $scope->apply($query, $this);
            }
        }

        return $query;
    }

    /**
     * Add a global scope to the model.
     *
     * @param string|\Closure|object $scope
     * @param \Closure|object        $implementation
     *
     * @return $this
     */
    public static function add_global_scope($scope, $implementation = null)
    {
        if (is_string($scope) && !is_null($implementation)) {
            static::$global_scopes[$scope] = $implementation;
        } elseif ($scope instanceof \Closure) {
            static::$global_scopes[spl_object_hash($scope)] = $scope;
        } elseif (is_object($scope)) {
            static::$global_scopes[get_class($scope)] = $scope;
        }
    }

    /**
     * Removw a global scope from the model.
     *
     * @param string $scope
     *
     * @return $this
     */
    public static function remove_global_scope($scope)
    {
        unset(static::$global_scopes[$scope]);
    }

    /**
     * Get all global scopes applied to the model.
     *
     * @return array
     */
    public static function get_global_scopes()
    {
        return static::$global_scopes;
    }

    /**
     * Get the base query for the model, applying soft delete filter and global scopes.
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
     * Handle static method calls into the model.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        $instance = new static();
        $query = $instance->query();
        $scope = 'scope_' . $method;

        if (method_exists($instance, $scope)) {
            return call_user_func_array([$instance, $scope], array_merge([$query], $parameters));
        }

        return call_user_func_array([$query, $method], $parameters);
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
