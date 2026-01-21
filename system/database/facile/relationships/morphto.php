<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

class MorphTo extends Relationship
{
    /**
     * Berisi morph type.
     *
     * @var string
     */
    protected $type;

    /**
     * Berisi morph id.
     *
     * @var string
     */
    protected $id;

    /**
     * Buat instance morph to relationship baru.
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
     * Set constraint untuk query.
     */
    protected function constrain()
    {
        // MorphTo tidak butuh constraint karena sudah di resolve secara dinamis
    }

    /**
     * Ambil hasil relasi.
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
     * Ambil eager loaded results.
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
     * Ambil nama relasi.
     *
     * @return string
     */
    protected function relationship_name()
    {
        return $this->type;
    }
}
