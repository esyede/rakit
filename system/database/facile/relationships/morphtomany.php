<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Database\Facile\Model;

class MorphToMany extends Relationship
{
    /**
     * Contains morph type.
     *
     * @var string
     */
    protected $type;

    /**
     * Contains the morph id.
     *
     * @var string
     */
    protected $id;

    /**
     * Contains the pivot table name.
     *
     * @var string
     */
    protected $pivot_table;

    /**
     * Contains the other key.
     *
     * @var string
     */
    protected $other;

    /**
     * Constructor.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $type
     * @param string $id
     * @param string $table
     * @param string $other
     */
    public function __construct($model, $associated, $type, $id, $table = null, $other = null)
    {
        $this->type = $type;
        $this->id = $id;

        parent::__construct($model, $associated, null);

        // Note: both of these read $this->base and $this->model, which only exist
        // once the parent constructor has run. Deriving them before that produced
        // a pivot table literally named '_'.
        $this->other = $other ?: static::foreign($this->model);
        $this->pivot_table = $table ?: $this->get_default_table_name();
    }

    /**
     * Get the default pivot table name.
     *
     * @return string
     */
    protected function get_default_table_name()
    {
        $models = [class_basename($this->base), class_basename($this->model)];
        sort($models);

        return strtolower($models[0] . '_' . $models[1]);
    }

    /**
     * Set the appropriate constraint on the relational query.
     */
    protected function constrain()
    {
        // MorphToMany uses pivot table, constraint is applied in results() method.
    }

    /**
     * Get the results of the relationship.
     *
     * @param array $results
     *
     * @return mixed
     */
    public function results(array $results = [])
    {
        // Note: lazy loading calls this without arguments. Returning an empty
        // array in that case (as it used to) made the relationship always look
        // empty when read as a property.
        return $this->get();
    }

    /**
     * Get the results of the relationship.
     *
     * @param array $columns
     *
     * @return array
     */
    public function get($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $pivot_query = $this->base->query()
            ->connection()
            ->table($this->pivot_table)
            ->where($this->type, '=', get_class($this->base))
            ->where($this->id, '=', $this->base->get_key())
            ->lists($this->other);

        if (empty($pivot_query)) {
            return [];
        }

        $query = $this->model->query()->where_in($this->model->key(), $pivot_query);

        return $query->get($columns);
    }

    /**
     * Eager load the relationship for a whole result set.
     *
     * Note: the pivot carries the type as well, so this is resolved here rather
     * than through initialize()/eagerly_constrain()/match().
     *
     * @param array  $results
     * @param string $relationship
     *
     * @return array
     */
    public function eager_load(array &$results, $relationship)
    {
        $keys = $this->keys($results);
        $pivot_records = $this->base->query()
            ->connection()
            ->table($this->pivot_table)
            ->where($this->type, '=', get_class($this->base))
            ->where_in($this->id, $keys)
            ->get([$this->id, $this->other]);

        $grouped = [];

        foreach ($pivot_records as $pivot) {
            $grouped[$pivot->{$this->id}][] = $pivot->{$this->other};
        }

        $related_ids = [];

        foreach ($grouped as $ids) {
            $related_ids = array_merge($related_ids, $ids);
        }

        $related_ids = array_unique($related_ids);
        $related_models = [];

        if (!empty($related_ids)) {
            $models = $this->model->query()->where_in($this->model->key(), $related_ids)->get();

            foreach ($models as $model) {
                $related_models[$model->get_key()] = $model;
            }
        }

        foreach ($results as $result) {
            $key = $result->get_key();
            $related = [];

            if (isset($grouped[$key])) {
                foreach ($grouped[$key] as $related_id) {
                    if (isset($related_models[$related_id])) {
                        $related[] = $related_models[$related_id];
                    }
                }
            }

            $result->relationships[$relationship] = $related;
        }

        return $results;
    }
}
