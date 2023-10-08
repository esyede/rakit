<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Database\Facile\Model;
use System\Database\Facile\Query;

abstract class Relationship extends Query
{
    /**
     * Berisi base model untuk relasi.
     *
     * @var Model
     */
    protected $base;

    /**
     * Berisi foreign key.
     *
     * @var string
     */
    protected $foreign;

    /**
     * Buat instance relasi has one or many baru.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $foreign
     */
    public function __construct($model, $associated, $foreign)
    {
        $this->foreign = $foreign;
        $this->model = ($associated instanceof Model) ? $associated : new $associated();
        $this->base = ($model instanceof Model) ? $model : new $model();
        $this->table = $this->table();

        $this->constrain();
    }

    /**
     * Ambil nama foreign key milik model.
     *
     * @param string $model
     * @param string $foreign
     *
     * @return string
     */
    public static function foreign($model, $foreign = null)
    {
        if (!is_null($foreign)) {
            return $foreign;
        }

        $model = is_object($model) ? class_basename($model) : $model;
        return strtolower(basename((string) $model) . '_id');
    }

    /**
     * Ambil instance baru kelas model yang berelasi.
     *
     * @param array $attributes
     *
     * @return Model
     */
    protected function fresh_model(array $attributes = [])
    {
        $class = get_class($this->model);
        return new $class($attributes);
    }

    /**
     * Ambil foreign key untuk relasi.
     *
     * @return string
     */
    public function foreign_key()
    {
        return static::foreign($this->base, $this->foreign);
    }

    /**
     * Kumpulkan seluruh primary key dari sebuuh result set.
     *
     * @param Model|array $results
     *
     * @return array
     */
    public function keys(array $results)
    {
        $keys = [];

        foreach ($results as $result) {
            $keys[] = $result->get_key();
        }

        return array_unique($keys);
    }

    /**
     * Set daftar relasi yang harus di eagerload.
     *
     * @param array $with
     *
     * @return Relationship
     */
    public function with($with)
    {
        $this->model->with = is_array($with) ? $with : func_get_args();
        return $this;
    }
}
