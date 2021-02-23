<?php

namespace System\Database\Query;

defined('DS') or exit('No direct script access.');

class Join
{
    /**
     * Berisi tipe operasi join yang sedang dilakukan.
     *
     * @var string
     */
    public $type;

    /**
     * Berisi tabel tempat klausa join.
     *
     *
     * @var string
     */
    public $table;

    /**
     * Berisi klausa ON untuk join.
     *
     * @var array
     */
    public $clauses = [];

    /**
     * Buat instance baru query join.
     *
     * @param string $type
     * @param string $table
     */
    public function __construct($type, $table)
    {
        $this->type = $type;
        $this->table = $table;
    }

    /**
     * Tambahkan klausa ON ke query join.
     *
     * @param string $column1
     * @param string $operator
     * @param string $column2
     * @param string $connector
     *
     * @return Join
     */
    public function on($column1, $operator, $column2, $connector = 'AND')
    {
        $this->clauses[] = compact('column1', 'operator', 'column2', 'connector');

        return $this;
    }

    /**
     * Tambahkan klausa OR ON ke query join.
     *
     * @param string $column1
     * @param string $operator
     * @param string $column2
     *
     * @return Join
     */
    public function or_on($column1, $operator, $column2)
    {
        return $this->on($column1, $operator, $column2, 'OR');
    }
}
