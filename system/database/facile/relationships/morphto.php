<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

class MorphTo extends Relationship
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
     * Constructor.
     *
     * @param \System\Database\Facile\Model $model
     * @param string|null                   $associated
     * @param string                        $type
     * @param string|null                   $id
     */
    public function __construct($model, $associated, $type, $id)
    {
        $this->type = $type;
        $this->id = $id;

        parent::__construct($model, $associated, null);
    }

    /**
     * Set the appropriate constraint on the relational query.
     */
    protected function constrain()
    {
        // MorphTo does not need constraints because it is resolved dynamically
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
        $owner = (count($results) > 0) ? head($results) : $this->base;

        if (is_null($owner)) {
            return;
        }

        $type = $owner->{$this->type};
        $id = $owner->{$this->id};
        $class = is_null($id) ? null : \System\Database\Facile\Model::morph_resolve($type);

        if (is_null($class)) {
            return;
        }

        return (new $class())->find($id);
    }

    /**
     * Eager load the relationship for a whole result set.
     *
     * @param array         $results
     * @param string        $relationship
     * @param \Closure|null $constraints
     * @param array         $nested
     *
     * @return array
     */
    public function eager_load(array &$results, $relationship, $constraints = null, array $nested = [])
    {
        $types = [];

        foreach ($results as $result) {
            $type = $result->{$this->type};
            $id = $result->{$this->id};

            if (! is_null($type) && ! is_null($id)) {
                $types[$type][] = $id;
            }
        }

        $loaded = [];

        foreach ($types as $type => $ids) {
            $class = \System\Database\Facile\Model::morph_resolve($type);

            if (is_null($class)) {
                continue;
            }

            $instance = new $class();
            $query = $instance->query();
            $query->model->with = $nested;

            if (! is_null($constraints)) {
                $query->table->where_nested($constraints);
            }

            $models = static::constrain_keys($query, $instance->key(), array_unique($ids))->get();

            foreach ($models as $model) {
                $loaded[$type.'_'.$model->get_key()] = $model;
            }
        }

        foreach ($results as $result) {
            $type = $result->{$this->type};
            $id = $result->{$this->id};

            if (! is_null($type) && ! is_null($id)) {
                $key = $type.'_'.$id;
                $result->relationships[$relationship] = isset($loaded[$key]) ? $loaded[$key] : null;
            } else {
                $result->relationships[$relationship] = null;
            }
        }

        return $results;
    }
}
