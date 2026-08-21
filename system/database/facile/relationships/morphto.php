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
     * @param Model  $model
     * @param string $associated
     * @param string $type
     * @param string $id
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
            return null;
        }

        $type = $owner->{$this->type};
        $id = $owner->{$this->id};

        if (is_null($type) || is_null($id) || !class_exists($type)) {
            return null;
        }

        return (new $type())->find($id);
    }

    /**
     * Eager load the relationship for a whole result set.
     *
     * @param array  $results
     * @param string $relationship
     *
     * @return array
     */
    public function eager_load(array &$results, $relationship)
    {
        $types = [];

        foreach ($results as $result) {
            $type = $result->{$this->type};
            $id = $result->{$this->id};

            if (!is_null($type) && !is_null($id)) {
                $types[$type][] = $id;
            }
        }

        $loaded = [];

        foreach ($types as $type => $ids) {
            if (!class_exists($type)) {
                continue;
            }

            $class = $type;
            $instance = new $class();
            $models = $instance->query()->where_in($instance->key(), array_unique($ids))->get();

            foreach ($models as $model) {
                $loaded[$type . '_' . $model->get_key()] = $model;
            }
        }

        foreach ($results as $result) {
            $type = $result->{$this->type};
            $id = $result->{$this->id};

            if (!is_null($type) && !is_null($id)) {
                $key = $type . '_' . $id;
                $result->relationships[$relationship] = isset($loaded[$key]) ? $loaded[$key] : null;
            } else {
                $result->relationships[$relationship] = null;
            }
        }

        return $results;
    }
}
