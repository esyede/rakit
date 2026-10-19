<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Database\Facile\Model;

class HasOneOrMany extends Relationship
{
    /**
     * Insert a record for the relationship; a Model instance is saved directly.
     *
     * @param Model|array $attributes
     *
     * @return Model|false
     */
    public function insert($attributes)
    {
        $model = ($attributes instanceof Model) ? $attributes : $this->fresh_model($attributes);
        $this->set_foreign_key($model);

        return $model->save() ? $model : false;
    }

    /**
     * Set the foreign key on a given model, bypassing fillable / guarded.
     *
     * @param Model $model
     */
    protected function set_foreign_key(Model $model)
    {
        $model->set_attribute($this->foreign_key(), $this->base->get_key());
    }

    /**
     * Save the given model(s) for the relationship, setting the foreign key.
     * Each model is inserted or updated according to its own exists flag.
     *
     * @param mixed $models
     *
     * @return bool
     */
    public function save($models)
    {
        $models = is_array($models) ? $models : [$models];
        $class = get_class($this->model);
        $saved = true;

        foreach ($models as $attributes) {
            $model = ($attributes instanceof $class) ? $attributes : $this->fresh_model($attributes);
            $this->set_foreign_key($model);
            $saved = $model->save() && $saved;
        }

        return $saved;
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
        static::constrain_keys($this->table, $this->foreign_key(), $this->keys($results));
    }

    /**
     * Correlate the relational query with the parent table.
     *
     * @param string $parent_table
     *
     * @return \System\Database\Facile\Query
     */
    public function correlate($parent_table)
    {
        $this->reset_constraints();
        $this->table->where_column(
            $this->model->table() . '.' . $this->foreign_key(),
            '=',
            $parent_table . '.' . $this->base->key()
        );

        return $this->table;
    }
}
