<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct script access.');

class Pivot extends Model
{
    /**
     * Berisi nama tabel pivot.
     *
     * @var string
     */
    protected $pivot_table;

    /**
     * Berisi koneksi database yang sedang digunakan.
     *
     * @var System\Database\Connection
     */
    protected $pivot_connection;

    /**
     * Penanda bahwa model ini memiliki kolom timestamp created / updated at.
     *
     * @var bool
     */
    public static $timestamps = true;

    /**
     * Buat instance pivot baru.
     *
     * @param string $table
     * @param string $connection
     */
    public function __construct($table, $connection = null)
    {
        $this->pivot_table = $table;
        $this->pivot_connection = $connection;

        parent::__construct([], true);
    }

    /**
     * Ambil nama tabel pivot.
     *
     * @return string
     */
    public function table()
    {
        return $this->pivot_table;
    }

    /**
     * Ambil koneksi database yang sedang digunakan.
     *
     * @return string
     */
    public function connection()
    {
        return $this->pivot_connection;
    }
}
