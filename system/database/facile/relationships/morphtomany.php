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
        $this->other = $other ?: static::foreign($associated);

        if (is_null($table)) {
            $table = $this->get_default_table_name();
        }

        $this->pivot_table = $table;

        parent::__construct($model, $associated, null);
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
        return (count($results) === 0) ? [] : $this->get();
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
     * Get the results of the eager load of the relationship.
     *
     * @param array $results
     *
     * @return array
     */
    public function eager_load(array $results)
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

            $result->relationships[$this->relationship_name()] = $related;
        }

        return $results;
    }

    /**
     * Get the name of the relationship.
     *
     * @return string
     */
    protected function relationship_name()
    {
        return $this->type;
    }
}
