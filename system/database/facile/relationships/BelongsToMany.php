<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\Database\Facile\Model;
use System\Database\Facile\Pivot;

class BelongsToMany extends Relationship
{
    /**
     * Berisi nama tabel perantara yang sedang di join.
     *
     * @var string
     */
    protected $joining;

    /**
     * Berisi primary key milik tabel gabungan yang berelasi.
     *
     * @var string
     */
    protected $other;

    /**
     * List kolom di tabel gabungan yang harus daimbil.
     *
     * @var array
     */
    public $with = ['id'];

    /**
     * Buat instance relasi many-to-many baru.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $table
     * @param string $foreign
     * @param string $other
     */
    public function __construct($model, $associated, $table, $foreign, $other)
    {
        $this->other = $other;
        $this->joining = $table ? $table : $this->joining($model, $associated);

        if (Pivot::$timestamps) {
            $this->with[] = 'created_at';
            $this->with[] = 'updated_at';
        }

        parent::__construct($model, $associated, $foreign);
    }

    /**
     * Tentukan nama tabel gabungan untuk relasi.
     * Secara default, penamaannya mengikuti pola snake_case.
     *
     * @return string
     */
    protected function joining($model, $associated)
    {
        $models = [class_basename($model), class_basename($associated)];
        sort($models);

        return strtolower($models[0].'_'.$models[1]);
    }

    /**
     * Ambil properti hasil mass-assignment untuk relasi.
     *
     * @return array
     */
    public function results()
    {
        return parent::get();
    }

    /**
     * Insert record baru ke tabel gabungan.
     *
     * @param Model|int $id
     * @param array     $attributes
     *
     * @return bool
     */
    public function attach($id, $attributes = [])
    {
        $id = ($id instanceof Model) ? $id->get_key() : $id;
        $joining = array_merge($this->join_record($id), $attributes);

        return $this->insert_joining($joining);
    }

    /**
     * Hapus record dari tabel gabungan.
     *
     * @param array|Model|int $ids
     *
     * @return bool
     */
    public function detach($ids)
    {
        if ($ids instanceof Model) {
            $ids = [$ids->get_key()];
        } elseif (! is_array($ids)) {
            $ids = [$ids];
        }

        return $this->pivot()->where_in($this->other_key(), $ids)->delete();
    }

    /**
     * Sinkronkan tabel gabungan dengan array ID yang diberikan.
     *
     * @param array $ids
     *
     * @return bool
     */
    public function sync($ids)
    {
        $current = $this->pivot()->lists($this->other_key());
        $ids = (array) $ids;

        foreach ($ids as $id) {
            if (! in_array($id, $current)) {
                $this->attach($id);
            }
        }

        $detach = array_diff($current, $ids);

        if (count($detach) > 0) {
            $this->detach($detach);
        }
    }

    /**
     * Insert record baru ke relasi.
     *
     * @param Model|array $attributes
     * @param array       $joining
     *
     * @return bool
     */
    public function insert($attributes, $joining = [])
    {
        $attributes = ($attributes instanceof Model) ? $attributes->attributes : $attributes;
        $model = $this->model->create($attributes);

        if ($model instanceof Model) {
            $joining = array_merge($this->join_record($model->get_key()), $joining);
            $result = $this->insert_joining($joining);
        }

        return ($model instanceof Model) && $result;
    }

    /**
     * Hapus seluruh record milik tabel gabungan.
     *
     * @return int
     */
    public function delete()
    {
        return $this->pivot()->delete();
    }

    /**
     * Buat array yang mewakili record gabungan baru untuk relasi.
     *
     * @param int $id
     *
     * @return array
     */
    protected function join_record($id)
    {
        return [
            $this->foreign_key() => $this->base->get_key(),
            $this->other_key() => $id,
        ];
    }

    /**
     * Insert record baru ke tabel gabungan relasi.
     *
     * @param array $attributes
     */
    protected function insert_joining($attributes)
    {
        if (Pivot::$timestamps) {
            $attributes['created_at'] = new \DateTime();
            $attributes['updated_at'] = $attributes['created_at'];
        }

        return $this->joining_table()->insert($attributes);
    }

    /**
     * Ambil query builder untuk tabel gabungan relasi.
     *
     * @return Query
     */
    protected function joining_table()
    {
        return $this->connection()->table($this->joining);
    }

    /**
     * Set constraint yang sesuai pada tabel relasi.
     */
    protected function constrain()
    {
        $other = $this->other_key();
        $foreign = $this->foreign_key();

        $this->set_select($foreign, $other)->set_join($other)->set_where($foreign);
    }

    /**
     * Set klausa SELECT pada query builder relasi.
     *
     * @param string $foreign
     * @param string $other
     */
    protected function set_select($foreign, $other)
    {
        $columns = [$this->model->table().'.*'];
        $this->with = array_merge($this->with, [$foreign, $other]);

        foreach ($this->with as $column) {
            $columns[] = $this->joining.'.'.$column.' AS pivot_'.$column;
        }

        $this->table->select($columns);

        return $this;
    }

    /**
     * Set klausa JOIN pada query builder relasi.
     *
     * @param string $other
     */
    protected function set_join($other)
    {
        $this->table->join($this->joining, $this->associated_key(), '=', $this->joining.'.'.$other);
        return $this;
    }

    /**
     * Set klausa WHERE pada query builder relasi.
     *
     * @param string $foreign
     */
    protected function set_where($foreign)
    {
        $this->table->where($this->joining.'.'.$foreign, '=', $this->base->get_key());
        return $this;
    }

    /**
     * Mulai relasi pada beberapa model induk.
     *
     * @param array  $parents
     * @param string $relationship
     */
    public function initialize(&$parents, $relationship)
    {
        foreach ($parents as &$parent) {
            $parent->relationships[$relationship] = [];
        }
    }

    /**
     * Set constraint yang sesuai pada tabel relasi yang di eagerload.
     *
     * @param array $results
     */
    public function eagerly_constrain($results)
    {
        $this->table->where_in($this->joining.'.'.$this->foreign_key(), $this->keys($results));
    }

    /**
     * Cocokkan model anak yang di eagerload dengan model induknya.
     *
     * @param array $parents
     * @param array $children
     */
    public function match($relationship, &$parents, $children)
    {
        $foreign = $this->foreign_key();
        $dictionary = [];

        foreach ($children as $child) {
            $dictionary[$child->pivot->{$foreign}][] = $child;
        }

        foreach ($parents as $parent) {
            if (array_key_exists($key = $parent->get_key(), $dictionary)) {
                $parent->relationships[$relationship] = $dictionary[$key];
            }
        }
    }

    /**
     * Hidrasi model pivot pada array result.
     *
     * @param array $results
     */
    protected function hydrate_pivot(&$results)
    {
        foreach ($results as &$result) {
            $pivot = new Pivot($this->joining, $this->model->connection());

            foreach ($result->attributes as $key => $value) {
                if (Str::starts_with($key, 'pivot_')) {
                    $pivot->{substr($key, 6)} = $value;
                    $result->purge($key);
                }
            }

            $result->relationships['pivot'] = $pivot;
            $pivot->sync();
            $result->sync();
        }
    }

    /**
     * Set list kolom tabel gabungan yang harus diambil.
     *
     * @param array $column
     *
     * @return Relationship
     */
    public function with($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $this->with = array_unique(array_merge($this->with, $columns));
        $this->set_select($this->foreign_key(), $this->other_key());

        return $this;
    }

    /**
     * Ambil instance relasi milik tabel pivot.
     *
     * @return HasMany
     */
    public function pivot()
    {
        $pivot = new Pivot($this->joining, $this->model->connection());
        return new HasMany($this->base, $pivot, $this->foreign_key());
    }

    /**
     * Ambil foreign key untuk relasi saat ini.
     *
     * @return string
     */
    protected function other_key()
    {
        return Relationship::foreign($this->model, $this->other);
    }

    /**
     * Ambil primary key tabel yang berelasi.
     *
     * @return string
     */
    protected function associated_key()
    {
        return $this->model->table().'.'.$this->model->key();
    }
}
