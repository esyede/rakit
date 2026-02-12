<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Database\Facile\Model;
use System\Database\Facile\Pivot;

class BelongsToMany extends Relationship
{
    /**
     * Contains the name of the joining table.
     *
     * @var string
     */
    protected $joining;

    /**
     * Contains the name of the other foreign key.
     *
     * @var string
     */
    protected $other;

    /**
     * List of pivot columns to retrieve.
     *
     * @var array
     */
    public $with = ['id'];

    /**
     * Constructor.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $table
     * @param string $foreign
     * @param string $other
     */
    public function __construct($model, $associated, $table, $foreign, $other)
    {
        $this->other = $other;
        $this->joining = $table ?: $this->joining($model, $associated);

        if (Pivot::$timestamps) {
            $this->with[] = 'created_at';
            $this->with[] = 'updated_at';
        }

        parent::__construct($model, $associated, $foreign);
    }

    /**
     * Set the joining table name.
     * By default, the naming follows the snake_case pattern.
     *
     * @return string
     */
    protected function joining($model, $associated)
    {
        $models = [class_basename($model), class_basename($associated)];
        sort($models);

        return strtolower($models[0] . '_' . $models[1]);
    }

    /**
     * Get the results of mass-assignment against the relationship.
     *
     * @return array
     */
    public function results()
    {
        return parent::get();
    }

    /**
     * Insert a new record into the joining table.
     *
     * @param Model|int $id
     * @param array     $attributes
     *
     * @return bool
     */
    public function attach($id, array $attributes = [])
    {
        $id = ($id instanceof Model) ? $id->get_key() : $id;
        $joining = array_merge($this->join_record($id), $attributes);

        return $this->insert_joining($joining);
    }

    /**
     * Delete a record from the joining table.
     *
     * @param array|Model|int $ids
     *
     * @return bool
     */
    public function detach($ids)
    {
        if ($ids instanceof Model) {
            $ids = [$ids->get_key()];
        } elseif (!is_array($ids)) {
            $ids = [$ids];
        }

        return $this->pivot()->where_in($this->other_key(), $ids)->delete();
    }

    /**
     * Sync the joining table with a list of IDs.
     *
     * @param array $ids
     *
     * @return bool
     */
    public function sync($ids)
    {
        $ids = is_array($ids) ? $ids : func_get_args();
        $current = $this->pivot()->lists($this->other_key());

        foreach ($ids as $id) {
            if (!in_array($id, $current)) {
                $this->attach($id);
            }
        }

        $detach = array_diff($current, $ids);

        if (count($detach) > 0) {
            $this->detach($detach);
        }
    }

    /**
     * Insert a new record into the related table and the joining table.
     *
     * @param Model|array $attributes
     * @param array       $joining
     *
     * @return bool
     */
    public function insert($attributes, array $joining = [])
    {
        $attributes = ($attributes instanceof Model) ? $attributes->attributes : $attributes;
        $model = $this->model->create($attributes);

        if ($model instanceof Model) {
            $joining = array_merge($this->join_record($model->get_key()), $joining);
            $result = $this->insert_joining($joining);
        }

        return ($model instanceof Model) && $result;
    }

    /**
     * Delete all records from the joining table.
     *
     * @return int
     */
    public function delete()
    {
        return $this->pivot()->delete();
    }

    /**
     * Generate an array representation of a joining record.
     *
     * @param int $id
     *
     * @return array
     */
    protected function join_record($id)
    {
        return [
            $this->foreign_key() => $this->base->get_key(),
            $this->other_key() => $id,
        ];
    }

    /**
     * Insert a new record into the joining table.
     *
     * @param array $attributes
     */
    protected function insert_joining(array $attributes)
    {
        if (Pivot::$timestamps) {
            $attributes['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $attributes['updated_at'] = $attributes['created_at'];
        }

        return $this->joining_table()->insert($attributes);
    }

    /**
     * Get the query builder for the joining table.
     *
     * @return Query
     */
    protected function joining_table()
    {
        return $this->connection()->table($this->joining);
    }

    /**
     * Set the constraints for a relational query.
     */
    protected function constrain()
    {
        $other = $this->other_key();
        $foreign = $this->foreign_key();

        $this->set_select($foreign, $other)->set_join($other)->set_where($foreign);
    }

    /**
     * Set the SELECT clause on the related query builder.
     *
     * @param string $foreign
     * @param string $other
     */
    protected function set_select($foreign, $other)
    {
        $columns = [$this->model->table() . '.*'];
        $this->with = array_merge($this->with, [$foreign, $other]);

        foreach ($this->with as $column) {
            $columns[] = $this->joining . '.' . $column . ' AS pivot_' . $column;
        }

        $this->table->select($columns);

        return $this;
    }

    /**
     * Set the JOIN clause on the related query builder.
     *
     * @param string $other
     */
    protected function set_join($other)
    {
        $this->table->join($this->joining, $this->associated_key(), '=', $this->joining . '.' . $other);
        return $this;
    }

    /**
     * Set the WHERE clause on the related query builder.
     *
     * @param string $foreign
     */
    protected function set_where($foreign)
    {
        $this->table->where($this->joining . '.' . $foreign, '=', $this->base->get_key());
        return $this;
    }

    /**
     * Start the eager loading process for the relationship.
     *
     * @param array  $parents
     * @param string $relationship
     */
    public function initialize(array &$parents, $relationship)
    {
        foreach ($parents as &$parent) {
            $parent->relationships[$relationship] = [];
        }
    }

    /**
     * Set the constraints for an eager load of the relationship.
     *
     * @param array $results
     */
    public function eagerly_constrain(array $results)
    {
        $this->table->where_in($this->joining . '.' . $this->foreign_key(), $this->keys($results));
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $parents
     * @param array $childrens
     */
    public function match($relationship, array &$parents, array $childrens)
    {
        $foreign = $this->foreign_key();
        $dictionary = [];

        foreach ($childrens as $children) {
            $dictionary[$children->pivot->{$foreign}][] = $children;
        }

        foreach ($parents as $parent) {
            if (array_key_exists($key = $parent->get_key(), $dictionary)) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }

    /**
     * Hydrate the pivot models on the results.
     *
     * @param array $results
     */
    protected function hydrate_pivot(array &$results)
    {
        foreach ($results as &$result) {
            $pivot = new Pivot($this->joining, $this->model->connection());

            foreach ($result->attributes as $key => $value) {
                if ('pivot_' === substr((string) $key, 0, 6)) {
                    $pivot->{substr((string) $key, 6)} = $value;
                    $result->purge($key);
                }
            }

            $result->relationships['pivot'] = $pivot;
            $pivot->sync();
            $result->sync();
        }
    }

    /**
     * Set the pivot columns to retrieve.
     *
     * @param array $column
     *
     * @return Relationship
     */
    public function with($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->with = array_unique(array_merge($this->with, $columns));
        $this->set_select($this->foreign_key(), $this->other_key());

        return $this;
    }

    /**
     * Get a new query builder for the pivot table.
     *
     * @return HasMany
     */
    public function pivot()
    {
        $pivot = new Pivot($this->joining, $this->model->connection());
        return new HasMany($this->base, $pivot, $this->foreign_key());
    }

    /**
     * Get foreign key name for the other model.
     *
     * @return string
     */
    protected function other_key()
    {
        return Relationship::foreign($this->model, $this->other);
    }

    /**
     * Get the associated key for the related model.
     *
     * @return string
     */
    protected function associated_key()
    {
        return $this->model->table() . '.' . $this->model->key();
    }
}
