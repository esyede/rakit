<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct script access.');

class HasOne extends HasOneOrMany
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
     * Mulai relasi terhadap beberapa model induk.
     *
     * @param array  $parents
     * @param string $relationship
     */
    public function initialize(array &$parents, $relationship)
    {
        foreach ($parents as &$parent) {
            $parent->relationships[$relationship] = null;
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

        foreach ($childrens as $childen) {
            $dictionary[$childen->{$foreign}] = $child;
        }

        foreach ($parents as $parent) {
            if (array_key_exists($key = $parent->get_key(), $dictionary)) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }
}
