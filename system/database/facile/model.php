<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Hook;
use System\Carbon;
use System\Validator;
use System\Exceptions\ModelNotFoundException;

abstract class Model implements \JsonSerializable
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
     * Attributes hidden for this instance only.
     *
     * @var array
     */
    public $instance_hidden = [];

    /**
     * Attributes made visible for this instance only.
     *
     * @var array
     */
    public $instance_visible = [];

    /**
     * Accessors appended for this instance only.
     *
     * @var array
     */
    public $instance_appends = [];

    /**
     * Attributes that were changed by the last save().
     *
     * @var array
     */
    public $changes = [];

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
     * Whitelist of attributes that should be included in the array form of the model.
     * When it is not empty, it takes precedence over the $hidden list.
     *
     * @var array
     */
    public static $visible = [];

    /**
     * List of accessor backed attributes that should be appended
     * to the array form of the model.
     *
     * @var array
     */
    public static $appends = [];

    /**
     * List of attributes that should be cast to a native type.
     * Supported: int, integer, real, float, double, decimal:<digits>, string,
     * bool, boolean, object, array, json, collection, date, datetime, timestamp.
     *
     * @var array
     */
    public static $casts = [];

    /**
     * Format used when a date-ish attribute is converted into a string.
     *
     * @var string
     */
    public static $date_format = 'Y-m-d H:i:s';

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
     * Contains global scopes that are applied to all queries for the model,
     * keyed by model class name.
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
     * Create a new model instance.
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
        $cast = $this->cast_type($key);

        if (in_array($cast, ['array', 'json', 'object', 'collection']) && ! is_string($value) && ! is_null($value)) {
            $value = json_encode(($value instanceof \System\Collection) ? $value->to_array() : $value);
        } elseif (in_array($cast, ['date', 'datetime']) && $value instanceof Carbon) {
            $value = $value->format(('date' === $cast) ? 'Y-m-d' : static::$date_format);
        } elseif ('timestamp' === $cast && $value instanceof Carbon) {
            $value = $value->getTimestamp();
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Get the cast type declared for the given attribute.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function cast_type($key)
    {
        if (! is_array(static::$casts) || ! array_key_exists($key, static::$casts)) {
            return null;
        }

        $cast = trim(strtolower((string) static::$casts[$key]));

        return (false === strpos($cast, ':')) ? $cast : substr($cast, 0, strpos($cast, ':'));
    }

    /**
     * Check whether the given attribute has a cast declared.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has_cast($key)
    {
        return ! is_null($this->cast_type($key));
    }

    /**
     * Cast the given attribute value into its declared native type.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function cast_attribute($key, $value)
    {
        $cast = $this->cast_type($key);

        if (is_null($cast) || is_null($value)) {
            return $value;
        }

        switch ($cast) {
            case 'int':
            case 'integer':
                return (int) $value;

            case 'real':
            case 'float':
            case 'double':
                return (float) $value;

            case 'decimal':
                $digits = explode(':', (string) static::$casts[$key]);
                return number_format((float) $value, isset($digits[1]) ? (int) $digits[1] : 2, '.', '');

            case 'string':
                return (string) $value;

            case 'bool':
            case 'boolean':
                return (bool) $value;

            case 'object':
                return is_string($value) ? json_decode($value) : $value;

            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;

            case 'collection':
                $items = is_string($value) ? json_decode($value, true) : $value;
                return new \System\Collection(is_array($items) ? $items : []);

            case 'date':
            case 'datetime':
                return ($value instanceof Carbon) ? $value : Carbon::parse($value);

            case 'timestamp':
                return ($value instanceof Carbon) ? $value->getTimestamp() : (int) $value;
        }

        return $value;
    }

    /**
     * Get a model's raw attribute value.
     *
     * The lookup order is: loaded relationship, table attribute, then
     * relationship that has not been loaded yet. The accessor method is not
     * applied here, so an accessor may call this method safely.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get_attribute($key)
    {
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->cast_attribute($key, $this->attributes[$key]);
        }

        return $this->load_relationship($key);
    }

    /**
     * Load a relationship of the model on demand.
     * NULL is returned when the given name is not a relationship.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function load_relationship($key)
    {
        if (! $this->has_own_method($key)) {
            return;
        }

        $method = new \ReflectionMethod($this, $key);

        if (0 !== $method->getNumberOfRequiredParameters()) {
            return;
        }

        $relationship = $this->{$key}();

        if (! ($relationship instanceof Relationships\Relationship)) {
            return;
        }

        $this->relationships[$key] = $relationship->results();

        return $this->relationships[$key];
    }

    /**
     * Check if a method is defined by the model itself instead of by this
     * base class. It keeps framework methods such as key() and get_table()
     * from being treated as a relationship or an accessor.
     *
     * @param string $method
     *
     * @return bool
     */
    protected function has_own_method($method)
    {
        return method_exists($this, $method) && ! method_exists(__CLASS__, $method);
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
            if (! array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
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
        if (! array_key_exists($attribute, $this->attributes)) {
            return false;
        }

        if (! array_key_exists($attribute, $this->original)) {
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
        $visible = array_merge((array) static::$visible, $this->instance_visible);
        $hidden = array_merge(array_diff((array) static::$hidden, $this->instance_visible), $this->instance_hidden);

        foreach (array_keys($this->attributes) as $key) {
            if (! $this->is_serializable($key, $visible, $hidden)) {
                continue;
            }

            $attributes[$key] = $this->serialize_value($this->{$key});
        }

        foreach (array_merge((array) static::$appends, $this->instance_appends) as $key) {
            if (! $this->is_serializable($key, $visible, $hidden)) {
                continue;
            }

            $attributes[$key] = $this->serialize_value($this->{$key});
        }

        foreach ($this->relationships as $key => $value) {
            if (! $this->is_serializable($key, $visible, $hidden)) {
                continue;
            }

            $attributes[$key] = $this->serialize_value($value);
        }

        return $attributes;
    }

    /**
     * Check whether the given key should end up in the array form of the model.
     *
     * @param string $key
     * @param array  $visible
     * @param array  $hidden
     *
     * @return bool
     */
    protected function is_serializable($key, array $visible, array $hidden)
    {
        if (count($visible) > 0) {
            return in_array($key, $visible);
        }

        return ! in_array($key, $hidden);
    }

    /**
     * Convert a single value into something that can be serialized.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function serialize_value($value)
    {
        if ($value instanceof Carbon) {
            return $value->format(static::$date_format);
        }

        if ($value instanceof Model) {
            return $value->to_array();
        }

        if ($value instanceof \System\Collection) {
            return $value->to_array();
        }

        if (is_array($value)) {
            $items = [];

            foreach ($value as $index => $item) {
                $items[$index] = $this->serialize_value($item);
            }

            return $items;
        }

        return $value;
    }

    /**
     * Hide the given attributes for this instance only.
     *
     * @param array|string $attributes
     *
     * @return $this
     */
    public function make_hidden($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->instance_hidden = array_merge($this->instance_hidden, $attributes);
        $this->instance_visible = array_diff($this->instance_visible, $attributes);

        return $this;
    }

    /**
     * Make the given attributes visible for this instance only.
     *
     * @param array|string $attributes
     *
     * @return $this
     */
    public function make_visible($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->instance_visible = array_merge($this->instance_visible, $attributes);
        $this->instance_hidden = array_diff($this->instance_hidden, $attributes);

        return $this;
    }

    /**
     * Append the given accessors to the array form of this instance only.
     *
     * @param array|string $attributes
     *
     * @return $this
     */
    public function append($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->instance_appends = array_merge($this->instance_appends, $attributes);

        return $this;
    }

    /**
     * Get a new query builder for the model.
     *
     * @return \System\Database\Facile\Query
     */
    public function new_query()
    {
        return $this->query();
    }

    /**
     * Get the name of the primary key column.
     *
     * @return string
     */
    public function get_key_name()
    {
        return static::$key;
    }

    /**
     * Get a model matching the attributes, or instantiate a new one.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return static
     */
    public static function first_or_new(array $attributes = [], array $values = [])
    {
        $model = static::match_attributes($attributes);

        return is_null($model) ? new static(array_merge($attributes, $values)) : $model;
    }

    /**
     * Get a model matching the attributes, or create it.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return static
     */
    public static function first_or_create(array $attributes = [], array $values = [])
    {
        $model = static::match_attributes($attributes);

        if (! is_null($model)) {
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * Update a model matching the attributes, or create it.
     *
     * @param array $attributes
     * @param array $values
     *
     * @return static
     */
    public static function update_or_create(array $attributes, array $values = [])
    {
        $model = static::match_attributes($attributes);

        if (is_null($model)) {
            return static::create(array_merge($attributes, $values));
        }

        $model->fill($values)->save();

        return $model;
    }

    /**
     * Get the first model matching every given attribute.
     *
     * @param array $attributes
     *
     * @return static|null
     */
    protected static function match_attributes(array $attributes)
    {
        $query = (new static())->query();

        foreach ($attributes as $column => $value) {
            $query->where($column, '=', $value);
        }

        return $query->first();
    }

    /**
     * Get every model whose primary key is in the given list.
     *
     * @param array $ids
     * @param array $columns
     *
     * @return \System\Collection
     */
    public static function find_many($ids, array $columns = ['*'])
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = (isset($ids[0]) && is_array($ids[0])) ? $ids[0] : $ids;

        if (0 === count($ids)) {
            return new \System\Collection();
        }

        return (new static())->query()->where_in(static::$key, $ids)->get($columns);
    }

    /**
     * Delete the models matching the given primary keys.
     *
     * @param array|mixed $ids
     *
     * @return int
     */
    public static function destroy($ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = (isset($ids[0]) && is_array($ids[0])) ? $ids[0] : $ids;
        $deleted = 0;

        foreach (static::find_many($ids) as $model) {
            if ($model->delete()) {
                ++$deleted;
            }
        }

        return $deleted;
    }

    /**
     * Add a WHERE clause on the primary key.
     *
     * @param mixed $id
     *
     * @return \System\Database\Facile\Query
     */
    public static function where_key($id)
    {
        $query = (new static())->query();

        return is_array($id)
            ? $query->where_in(static::$key, $id)
            : $query->where(static::$key, '=', $id);
    }

    /**
     * Update the timestamps of the model without changing anything else.
     *
     * @return bool
     */
    public function touch()
    {
        if (! $this->exists || ! static::$timestamps) {
            return false;
        }

        $this->updated_at = Carbon::now()->format(static::$date_format);

        return $this->save();
    }

    /**
     * Reload the attributes and relationships of this very instance from the database.
     *
     * @return $this|null
     */
    public function refresh()
    {
        if (! $this->exists) {
            return $this;
        }

        $fresh = $this->fresh();

        if (is_null($fresh)) {
            return null;
        }

        $this->attributes = $fresh->attributes;
        $this->relationships = [];
        $this->changes = [];
        $this->sync();

        return $this;
    }

    /**
     * Make an unsaved copy of the model, without its primary key and timestamps.
     *
     * @param array $except
     *
     * @return static
     */
    public function replicate(array $except = [])
    {
        $except = array_merge($except, [static::$key, 'created_at', 'updated_at', 'deleted_at']);
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (! in_array($key, $except)) {
                $attributes[$key] = $value;
            }
        }

        $clone = new static();
        $clone->fill_raw($attributes);

        return $clone;
    }

    /**
     * Check whether the given model is the very same record as this one.
     *
     * @param mixed $model
     *
     * @return bool
     */
    public function is($model)
    {
        return ($model instanceof Model)
            && get_class($model) === get_class($this)
            && $model->table() === $this->table()
            && ! is_null($model->get_key())
            && $model->get_key() === $this->get_key();
    }

    /**
     * Check whether the given model is a different record than this one.
     *
     * @param mixed $model
     *
     * @return bool
     */
    public function is_not($model)
    {
        return ! $this->is($model);
    }

    /**
     * Check what the last save() actually changed.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function was_changed($attribute = null)
    {
        if (is_null($attribute)) {
            return count($this->changes) > 0;
        }

        return array_key_exists($attribute, $this->changes);
    }

    /**
     * Get the attribute values as they were when the model was last synced.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get_original($attribute = null, $default = null)
    {
        if (is_null($attribute)) {
            return $this->original;
        }

        return array_key_exists($attribute, $this->original) ? $this->original[$attribute] : $default;
    }

    /**
     * Increment a column of this very record.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return bool
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        return $this->step($column, abs($amount), $extra);
    }

    /**
     * Decrement a column of this very record.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return bool
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->step($column, -abs($amount), $extra);
    }

    /**
     * Move a column of this very record by the given amount.
     *
     * @param string $column
     * @param int    $amount
     * @param array  $extra
     *
     * @return bool
     */
    protected function step($column, $amount, array $extra = [])
    {
        if (! $this->exists) {
            return false;
        }

        $query = $this->query()->where(static::$key, '=', $this->get_key());

        if ($amount < 0) {
            $query->decrement($column, abs($amount), $extra);
        } else {
            $query->increment($column, $amount, $extra);
        }

        $this->attributes[$column] = $this->attributes[$column] + $amount;

        foreach ($extra as $key => $value) {
            $this->attributes[$key] = $value;
        }

        $this->sync();

        return true;
    }

    /**
     * Convert the model into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->to_array();
    }

    /**
     * Convert the model and its relationships into JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function to_json($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
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
     * Start a query with the given relationships eager loaded.
     *
     * @param array|string $with
     *
     * @return Query
     */
    public static function with($with)
    {
        $model = new static();
        $model->with = is_array($with) ? $with : func_get_args();

        return $model->query();
    }

    /**
     * Get the query for a one-to-one relationship.
     *
     * @param string $model
     * @param string $foreign
     *
     * @return \System\Database\Facile\Relationships\HasOne
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
     * @return \System\Database\Facile\Relationships\HasMany
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
     * @return \System\Database\Facile\Relationships\BelongsTo
     */
    public function belongs_to($model, $foreign = null)
    {
        if (is_null($foreign)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : null;
            $foreign = is_null($caller) ? Str::lower(class_basename($model)) . '_id' : $caller . '_id';
        }

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
        $type = $name . '_type';
        $id = $foreign ?: ($name . '_id');

        return new Relationships\MorphToMany($this, $model, $type, $id, $table, $other);
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
        if (! $this->dirty()) {
            return true;
        }

        if (static::$timestamps) {
            if (! $this->exists) {
                $this->created_at = Carbon::now()->format('Y-m-d H:i:s');
            }

            $this->updated_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        Hook::fire(['facile.saving', 'facile.saving: ' . get_class($this)], [$this]);

        if ($this->exists) {
            $dirty = $this->get_dirty();
            $query = $this->query()->where(static::$key, '=', $this->get_key());

            // The number of affected rows is not a reliable indicator of success. MySQL
            // reports zero affected row when the new values are identical to the old
            // ones, even though nothing actually went wrong.
            $query->update($dirty);
            $result = true;

            if ($result) {
                Hook::fire(['facile.updated', 'facile.updated: ' . get_class($this)], [$this]);
            }
        } else {
            $id = $this->query()->insert_get_id($this->attributes, $this->key());
            $this->set_key($id);
            $key = $this->get_key();
            $result = ! is_null($key) && ! empty($key);
            $this->exists = $result;

            if ($result) {
                Hook::fire(['facile.created', 'facile.created: ' . get_class($this)], [$this]);
            }
        }

        $this->changes = $this->get_dirty();
        $this->original = $this->attributes;

        if ($result) {
            Hook::fire(['facile.saved', 'facile.saved: ' . get_class($this)], [$this]);
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
            Hook::fire(['facile.deleting', 'facile.deleting: ' . get_class($this)], [$this]);

            if (static::$soft_delete) { // Soft delete
                $this->deleted_at = Carbon::now()->format('Y-m-d H:i:s');
                $this->query()
                    ->where(static::$key, '=', $this->get_key())
                    ->update(['deleted_at' => $this->deleted_at]);
                $this->exists = false;
                Hook::fire(['facile.deleted', 'facile.deleted: ' . get_class($this)], [$this]);
            } else { // Hard delete
                $this->query()->where(static::$key, '=', $this->get_key())->delete();
                $this->exists = false;
                Hook::fire(['facile.deleted', 'facile.deleted: ' . get_class($this)], [$this]);
            }

            return true;
        }

        return false;
    }

    /**
     * Restore a soft-deleted model.
     *
     * @return bool
     */
    public function restore()
    {
        if (static::$soft_delete && ! $this->exists && ! is_null($this->deleted_at)) {
            $result = $this->query(true)->where(static::$key, '=', $this->get_key())->update(['deleted_at' => null]);

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
        if ($this->exists || ! is_null($this->deleted_at)) {
            Hook::fire(['facile.deleting', 'facile.deleting: ' . get_class($this)], [$this]);
            $result = $this->query(true)->where(static::$key, '=', $this->get_key())->delete();
            Hook::fire(['facile.deleted', 'facile.deleted: ' . get_class($this)], [$this]);

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
        return static::$soft_delete && ! is_null($this->deleted_at);
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
        return (new static())->query(true);
    }

    /**
     * Get query builder with only trashed records.
     *
     * @return Query
     */
    public static function only_trashed()
    {
        return (new static())->query(true)->where_not_null('deleted_at');
    }

    /**
     * Get a new query builder for the model.
     *
     * @param bool $with_trashed
     *
     * @return \System\Database\Facile\Query
     */
    public function query($with_trashed = false)
    {
        return new \System\Database\Facile\Query($this, $with_trashed);
    }

    /**
     * Apply all global scopes to the query.
     *
     * @param Query $query
     *
     * @return Query
     */
    public function apply_scopes($query)
    {
        foreach (static::get_global_scopes() as $scope) {
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
        $owner = get_called_class();

        if (! isset(static::$global_scopes[$owner])) {
            static::$global_scopes[$owner] = [];
        }

        if (is_string($scope) && ! is_null($implementation)) {
            static::$global_scopes[$owner][$scope] = $implementation;
        } elseif ($scope instanceof \Closure) {
            static::$global_scopes[$owner][spl_object_hash($scope)] = $scope;
        } elseif (is_object($scope)) {
            static::$global_scopes[$owner][get_class($scope)] = $scope;
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
        unset(static::$global_scopes[get_called_class()][$scope]);
    }

    /**
     * Get all global scopes applied to the model.
     *
     * @return array
     */
    public static function get_global_scopes()
    {
        $owner = get_called_class();
        return isset(static::$global_scopes[$owner]) ? static::$global_scopes[$owner] : [];
    }

    /**
     * Get the base query builder for the model, with the soft delete filter and
     * the model's global scopes already applied.
     *
     * @param bool $with_trashed
     *
     * @return \System\Database\Query
     */
    public function _query($with_trashed = false)
    {
        $query = \System\Database::connection($this->connection())->table($this->table());

        if (! $with_trashed && static::$soft_delete) {
            $query->where_null('deleted_at');
        }

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
     * The accessor method (get_<name>) is used when the model defines one.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $accessor = 'get_' . $key;

        if ($this->has_own_method($accessor)) {
            return $this->{$accessor}($this->get_attribute($key));
        }

        return $this->get_attribute($key);
    }

    /**
     * Handle dynamic property access for setting attributes.
     * The mutator method (set_<name>) is used when the model defines one.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $mutator = 'set_' . $key;

        if ($this->has_own_method($mutator)) {
            $this->{$mutator}($value);
            return;
        }

        $this->set_attribute($key, $value);
    }

    /**
     * Handle isset() and empty() on attributes and relationships.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        if (array_key_exists($key, $this->attributes) || array_key_exists($key, $this->relationships)) {
            return ! is_null($this->get_attribute($key));
        }

        if ($this->has_own_method('get_' . $key) || $this->has_own_method($key)) {
            return ! is_null($this->get_attribute($key));
        }

        return false;
    }

    /**
     * Handle unset() on attributes and relationships.
     *
     * @param string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relationships[$key]);
    }
}
