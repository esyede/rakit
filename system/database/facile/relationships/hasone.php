<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

class HasOne extends HasOneOrMany
{
    /**
     * Get the single result of the relationship.
     *
     * @return Model
     */
    public function results()
    {
        return parent::first();
    }

    /**
     * Initialize the eager loading process for the relationship.
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
            $dictionary[$children->{$foreign}] = $children;
        }

        foreach ($parents as $parent) {
            if (array_key_exists($key = $parent->get_key(), $dictionary)) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }
}
