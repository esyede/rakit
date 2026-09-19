<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Database\Facile\Model;
use System\Database\Facile\Query;

abstract class Relationship extends Query
{
    /**
     * Contains the base model.
     *
     * @var Model
     */
    protected $base;

    /**
     * Contains the foreign key.
     *
     * @var string
     */
    protected $foreign;

    /**
     * Constructor.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $foreign
     */
    public function __construct($model, $associated, $foreign)
    {
        $this->foreign = $foreign;
        $this->model = (! is_null($associated) && ! empty($associated))
            ? (($associated instanceof Model) ? $associated : new $associated())
            : null;

        $this->base = ($model instanceof Model) ? $model : new $model();

        if ($this->model) {
            $this->table = $this->table();
        }

        $this->constrain();
    }

    /**
     * Drop the constraint tying this relation to one parent, keeping the related
     * model's own scopes, which a plain reset_where() would discard.
     */
    public function reset_constraints()
    {
        $this->table->reset_where();

        if ($this->model && $this->model->soft_deleting()) {
            $this->table->where_null($this->model->table().'.deleted_at');
        }
    }

    /**
     * Get foreign key name for the relationship.
     *
     * @param mixed  $model
     * @param string $foreign
     *
     * @return string
     */
    public static function foreign($model, $foreign = null)
    {
        if (! is_null($foreign)) {
            return $foreign;
        }

        $model = is_object($model) ? class_basename($model) : $model;
        return strtolower(basename((string) $model).'_id');
    }

    /**
     * Generate a fresh instance of the related model.
     *
     * @param array $attributes
     *
     * @return Model
     */
    protected function fresh_model(array $attributes = [])
    {
        $class = get_class($this->model);
        return new $class($attributes);
    }

    /**
     * Get the foreign key name for the relationship.
     *
     * @return string
     */
    public function foreign_key()
    {
        return static::foreign($this->base, $this->foreign);
    }

    /**
     * Get the column the eager loaded children are matched on.
     *
     * @return string
     */
    protected function eager_key()
    {
        return $this->foreign_key();
    }

    /**
     * Get all unique keys from the results.
     *
     * @param Model|array $results
     *
     * @return array
     */
    public function keys(array $results)
    {
        $keys = [];

        foreach ($results as $result) {
            $keys[] = $result->get_key();
        }

        return array_unique($keys);
    }

    /**
     * Constrain the query to the given keys. All-integer keys are inlined, so eager
     * loading does not hit the driver's bound parameter limit. Only for a column of the
     * same type: polymorphic id columns are often strings, so morphs use where_in().
     *
     * @param \System\Database\Query|Query $query
     * @param string                       $column
     * @param array                        $keys
     *
     * @return \System\Database\Query|Query
     */
    protected static function constrain_keys($query, $column, array $keys)
    {
        $keys = array_values($keys);

        foreach ($keys as $key) {
            if (! is_int($key)) {
                return $query->where_in($column, $keys);
            }
        }

        return $query->where_integer_in_raw($column, $keys);
    }

    /**
     * Set the relationships to eager load.
     *
     * @param array $with
     *
     * @return Relationship
     */
    public function with($with)
    {
        $this->model->with = is_array($with) ? $with : func_get_args();
        return $this;
    }

    /**
     * Correlate the relational query with the parent table instead of one parent key,
     * which is what lets has() and where_has() build a subquery.
     *
     * @param string $parent_table
     *
     * @return \System\Database\Query
     */
    public function correlate($parent_table)
    {
        throw new \Exception(sprintf(
            'has() and where_has() do not support this relationship yet: %s',
            get_class($this)
        ));
    }
}
