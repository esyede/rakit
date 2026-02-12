<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Database;
use System\Database\Facile\Model;

class HasManyThrough extends Relationship
{
    /**
     * Contains the through model.
     *
     * @var Model
     */
    protected $through;

    /**
     * Contains foreign key on the through model.
     *
     * @var string
     */
    protected $first_key;

    /**
     * Contains foreign key on the associated model.
     *
     * @var string
     */
    protected $second_key;

    /**
     * Contains local key on the base model.
     *
     * @var string
     */
    protected $local_key;

    /**
     * Contains local key on the through model.
     *
     * @var string
     */
    protected $second_local_key;

    /**
     * Constructoor.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $through
     * @param string $first_key
     * @param string $second_key
     * @param string $local_key
     * @param string $second_local_key
     */
    public function __construct(
        $model,
        $associated,
        $through,
        $first_key = null,
        $second_key = null,
        $local_key = null,
        $second_local_key = null
    ) {
        $this->through = is_string($through) ? new $through() : $through;
        $this->model = is_string($associated) ? new $associated() : $associated;
        $this->base = is_string($model) ? new $model() : $model;

        $this->first_key = $first_key ?: static::foreign($this->base);
        $this->second_key = $second_key ?: $this->get_through_key();
        $this->local_key = $local_key ?: $this->base->key();
        $this->second_local_key = $second_local_key ?: $this->through->key();

        $this->foreign = $this->second_key;
        $this->table = $this->table();
        $this->constrain();
    }

    /**
     *  Set the appropriate constraints on the relationship query.
     */
    protected function constrain()
    {
        $through_table = $this->through->table();
        $through_key = $through_table . '.' . $this->second_local_key;
        $foreign_key = $through_table . '.' . $this->first_key;

        $this->table
            ->join($through_table, $through_key, '=', $this->model->table() . '.' . $this->second_key)
            ->where($foreign_key, '=', $this->base->{$this->local_key});

        $this->table->select([$this->model->table() . '.*']);
    }

    /**
     * Set the constraints for an eager load of the relationship.
     *
     * @param array $results
     */
    public function eagerly_constrain(array $results)
    {
        $through_table = $this->through->table();
        $through_key = $through_table . '.' . $this->second_local_key;
        $foreign_key = $through_table . '.' . $this->first_key;

        $this->table
            ->join($through_table, $through_key, '=', $this->model->table() . '.' . $this->second_key)
            ->where_in($foreign_key, $this->keys($results));

        $this->table->select([
            $this->model->table() . '.*',
            $through_table . '.' . $this->first_key . ' as rakit_through_key'
        ]);
    }

    /**
     * Initialize the relationship on a set of models.
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
     * Match the eagerly loaded results to their parents.
     *
     * @param string $relationship
     * @param array  $parents
     * @param array  $children
     */
    public function match($relationship, array &$parents, array $children)
    {
        $dictionary = [];

        foreach ($children as $child) {
            $key = $child->get_attribute('rakit_through_key')
                ?: $child->get_attribute($this->first_key);

            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }

            $dictionary[$key][] = $child;
        }

        foreach ($parents as &$parent) {
            $key = $parent->{$this->local_key};

            if (isset($dictionary[$key])) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }

    /**
     * Get the results of the relationship.
     *
     * @return array
     */
    public function results()
    {
        return parent::get();
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \System\Database\Query
     */
    protected function table()
    {
        return $this->connection()->table($this->model->table());
    }

    /**
     * Get the database connection instance.
     *
     * @return \System\Database\Connection
     */
    public function connection()
    {
        return Database::connection($this->model->connection());
    }

    /**
     * Get the through key name.
     * It usess the singular form of the through table name.
     *
     * @return string
     */
    protected function get_through_key()
    {
        return Str::singular($this->through->table()) . '_id';
    }
}
