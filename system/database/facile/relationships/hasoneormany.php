<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Database\Facile\Model;

class HasOneOrMany extends Relationship
{
    /**
     * Insert record baru ke relasi.
     * Jika model berhasil disimpan, data model akan direturn. FALSE jika sebaliknya.
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
     * Update record relasi.
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
     * Set constraint yang sesuai pada tabel relasi.
     */
    protected function constrain()
    {
        $this->table->where($this->foreign_key(), '=', $this->base->get_key());
    }

    /**
     * Set constraint yang sesuai pada tabel relasi untuk eagerload.
     *
     * @param array $results
     */
    public function eagerly_constrain(array $results)
    {
        $this->table->where_in($this->foreign_key(), $this->keys($results));
    }
}
