<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Database\Facile\Model;

class HasOneOrMany extends Relationship
{
    /**
     * Insert a new record for the relationship.
     * If a Model instance is passed, it will be saved directly.
     *
     * @param Model|array $attributes
     *
     * @return Model|false
     */
    public function insert($attributes)
    {
        if ($attributes instanceof Model) {
            $attributes->set_attribute($this->foreign_key(), $this->base->get_key());
            return $attributes->save() ? $attributes : false;
        }

        $attributes[$this->foreign_key()] = $this->base->get_key();
        return $this->model->create($attributes);
    }

    /**
     * Update the records of the relationship.
     *
     * @param array $attributes
     *
     * @return bool
     */
    public function update(array $attributes)
    {
        if ($this->model->timestamps()) {
            $attributes['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
        }

        return $this->table->update($attributes);
    }

    /**
     * Set the appropriate constraint on the relational query.
     */
    protected function constrain()
    {
        $this->table->where($this->foreign_key(), '=', $this->base->get_key());
    }

    /**
     * Set the constraints for an eager load of the relationship.
     *
     * @param array $results
     */
    public function eagerly_constrain(array $results)
    {
        $this->table->where_in($this->foreign_key(), $this->keys($results));
    }

    /**
     * Correlate the relational query with the parent table.
     *
     * @param string $parent_table
     *
     * @return \System\Database\Query
     */
    public function correlate($parent_table)
    {
        $this->table->reset_where();
        $this->table->where_column(
            $this->model->table() . '.' . $this->foreign_key(),
            '=',
            $parent_table . '.' . $this->base->key()
        );

        return $this->table;
    }
}
