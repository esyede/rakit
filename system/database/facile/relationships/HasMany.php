<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct script access.');

class HasMany extends HasOneOrMany
{
    /**
     * Ambil hasil mass-assignment milik relasi.
     *
     * @return array
     */
    public function results()
    {
        return parent::get();
    }

    /**
     * Simpan tabel relasi dengan array model.
     *
     * @param mixed $models
     *
     * @return bool
     */
    public function save($models)
    {
        $models = is_array($models) ? $models : [$models];
        $current = $this->table->lists($this->model->key());

        foreach ($models as $attributes) {
            $class = get_class($this->model);
            $model = ($attributes instanceof $class) ? $attributes : $this->fresh_model($attributes);

            $foreign = $this->foreign_key();
            $model->{$foreign} = $this->base->get_key();

            $id = $model->get_key();
            $model->exists = (! is_null($id) && in_array($id, $current));

            $model->original = [];
            $model->save();
        }

        return true;
    }

    /**
     * Mulai relasi terhadap beberapa model induk.
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
     * Cocokkan model anak yang di eagerload dengan model induknya.
     *
     * @param array $parents
     * @param array $childrens
     */
    public function match($relationship, array &$parents, array $childrens)
    {
        $foreign = $this->foreign_key();
        $dictionary = [];

        foreach ($childrens as $children) {
            $dictionary[$children->{$foreign}][] = $child;
        }

        foreach ($parents as $parent) {
            if (array_key_exists($key = $parent->get_key(), $dictionary)) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }
}
