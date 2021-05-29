<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct script access.');

use System\Database\Facile\Model;

class BelongsTo extends Relationship
{
    /**
     * Ambil hasil mass-assignment milik relasi.
     *
     * @return Model
     */
    public function results()
    {
        return parent::first();
    }

    /**
     * Update model induk relasi.
     *
     * @param Model|array $attributes
     *
     * @return int
     */
    public function update($attributes)
    {
        $attributes = ($attributes instanceof Model) ? $attributes->get_dirty() : $attributes;
        return $this->model->update($this->foreign_value(), $attributes);
    }

    /**
     * Set constraint yang sesuai pada tabel relasi.
     */
    protected function constrain()
    {
        $this->table->where($this->model->key(), '=', $this->foreign_value());
    }

    /**
     * Mulai relasi terhadap beberapa model induk.
     *
     * @param array  $parents
     * @param string $relationship
     */
    public function initialize(&$parents, $relationship)
    {
        foreach ($parents as &$parent) {
            $parent->relationships[$relationship] = null;
        }
    }

    /**
     * Set constraint yang sesuai pada tabel relasi yang di eagerload.
     *
     * @param array $results
     */
    public function eagerly_constrain($results)
    {
        $keys = [];

        foreach ($results as $result) {
            if (! is_null($key = $result->{$this->foreign_key()})) {
                $keys[] = $key;
            }
        }

        if (0 === count($keys)) {
            $keys = [0];
        }

        $this->table->where_in($this->model->key(), array_unique($keys));
    }

    /**
     * Cocokkan model anak yang di eagerload dengan model induknya.
     *
     * @param array $children
     * @param array $parents
     */
    public function match($relationship, &$children, $parents)
    {
        $foreign = $this->foreign_key();
        $dictionary = [];

        foreach ($parents as $parent) {
            $dictionary[$parent->get_key()] = $parent;
        }

        foreach ($children as $child) {
            if (array_key_exists($child->{$foreign}, $dictionary)) {
                $child->relationships[$relationship] = $dictionary[$child->{$foreign}];
            }
        }
    }

    /**
     * Ambil value foreign key milik base model.
     *
     * @return mixed
     */
    public function foreign_value()
    {
        return $this->base->get_attribute($this->foreign);
    }

    /**
     * Bind objek ke relasi belongs-to menggunakan id-nya.
     *
     * @return Facile
     */
    public function bind($id)
    {
        $this->base->fill([$this->foreign => $id])->save();
        return $this->base;
    }
}
