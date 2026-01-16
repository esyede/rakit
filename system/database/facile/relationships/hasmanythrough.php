<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Database;
use System\Database\Facile\Model;

class HasManyThrough extends Relationship
{
    /**
     * Berisi model perantara.
     *
     * @var Model
     */
    protected $through;

    /**
     * Berisi foreign key pada model perantara.
     *
     * @var string
     */
    protected $first_key;

    /**
     * Berisi foreign key pada model tujuan.
     *
     * @var string
     */
    protected $second_key;

    /**
     * Berisi local key pada model induk.
     *
     * @var string
     */
    protected $local_key;

    /**
     * Berisi local key pada model perantara.
     *
     * @var string
     */
    protected $second_local_key;

    /**
     * Buat instance relasi has many through baru.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $through
     * @param string $first_key
     * @param string $second_key
     * @param string $local_key
     * @param string $second_local_key
     */
    public function __construct(
        $model,
        $associated,
        $through,
        $first_key = null,
        $second_key = null,
        $local_key = null,
        $second_local_key = null
    ) {
        $this->through = is_string($through) ? new $through() : $through;
        $this->model = is_string($associated) ? new $associated() : $associated;
        $this->base = is_string($model) ? new $model() : $model;

        $this->first_key = $first_key ?: static::foreign($this->base);
        $this->second_key = $second_key ?: $this->get_through_key();
        $this->local_key = $local_key ?: $this->base->key();
        $this->second_local_key = $second_local_key ?: $this->through->key();

        $this->foreign = $this->second_key;
        $this->table = $this->table();
        $this->constrain();
    }

    /**
     * Set constraint yang sesuai pada tabel relasi.
     */
    protected function constrain()
    {
        $through_table = $this->through->table();
        $through_key = $through_table . '.' . $this->second_local_key;
        $foreign_key = $through_table . '.' . $this->first_key;

        $this->table
            ->join($through_table, $through_key, '=', $this->model->table() . '.' . $this->second_key)
            ->where($foreign_key, '=', $this->base->{$this->local_key});

        $this->table->select([$this->model->table() . '.*']);
    }

    /**
     * Set constraint yang sesuai pada tabel relasi untuk eagerload.
     *
     * @param array $results
     */
    public function eagerly_constrain(array $results)
    {
        $through_table = $this->through->table();
        $through_key = $through_table . '.' . $this->second_local_key;
        $foreign_key = $through_table . '.' . $this->first_key;

        $this->table
            ->join($through_table, $through_key, '=', $this->model->table() . '.' . $this->second_key)
            ->where_in($foreign_key, $this->keys($results));

        $this->table->select([
            $this->model->table() . '.*',
            $through_table . '.' . $this->first_key . ' as rakit_through_key'
        ]);
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
     * @param string $relationship
     * @param array  $parents
     * @param array  $children
     */
    public function match($relationship, array &$parents, array $children)
    {
        $dictionary = [];

        foreach ($children as $child) {
            $key = $child->get_attribute('rakit_through_key')
                ?: $child->get_attribute($this->first_key);

            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }

            $dictionary[$key][] = $child;
        }

        foreach ($parents as &$parent) {
            $key = $parent->{$this->local_key};

            if (isset($dictionary[$key])) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }

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
     * Ambil query builder untuk model yang digunakan.
     *
     * @return \System\Database\Query
     */
    protected function table()
    {
        return $this->connection()->table($this->model->table());
    }

    /**
     * Ambil koneksi database untuk query.
     *
     * @return \System\Database\Connection
     */
    public function connection()
    {
        return Database::connection($this->model->connection());
    }

    /**
     * Ambil foreign key untuk through model.
     * Menggunakan singular table name dari through model.
     *
     * @return string
     */
    protected function get_through_key()
    {
        return Str::singular($this->through->table()) . '_id';
    }
}
