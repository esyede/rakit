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
        if (count($results) === 0) {
            return null;
        }

        $results = head($results);
        $type = $results->{$this->type};
        $id = $results->{$this->id};

        if (is_null($type) || is_null($id)) {
            return null;
        }

        return (new $type())->find($id);
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
                $result->relationships[$this->relationship_name()] = isset($loaded[$key]) ? $loaded[$key] : null;
            } else {
                $result->relationships[$this->relationship_name()] = null;
            }
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
