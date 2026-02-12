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
        $this->model = (!is_null($associated) && !empty($associated))
            ? (($associated instanceof Model) ? $associated : new $associated())
            : null;

        $this->base = ($model instanceof Model) ? $model : new $model();

        if ($this->model) {
            $this->table = $this->table();
        }

        $this->constrain();
    }

    /**
     * Get foreign key name for the relationship.
     *
     * @param string $model
     * @param string $foreign
     *
     * @return string
     */
    public static function foreign($model, $foreign = null)
    {
        if (!is_null($foreign)) {
            return $foreign;
        }

        $model = is_object($model) ? class_basename($model) : $model;
        return strtolower(basename((string) $model) . '_id');
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
}
