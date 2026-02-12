<?php

namespace System\Database\Facile;

defined('DS') or exit('No direct access.');

class Pivot extends Model
{
    /**
     * Contains the name of the pivot table.
     *
     * @var string
     */
    protected $pivot_table;

    /**
     * Contains the database connection used by the pivot model.
     *
     * @var System\Database\Connection
     */
    protected $pivot_connection;

    /**
     * Determine wheter the pivot model should maintain timestamps.
     *
     * @var bool
     */
    public static $timestamps = true;

    /**
     * Constructor.
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
     * Get the name of the pivot table.
     *
     * @return string
     */
    public function table()
    {
        return $this->pivot_table;
    }

    /**
     * Get the database connection used by the pivot model.
     *
     * @return string
     */
    public function connection()
    {
        return $this->pivot_connection;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function sync()
    {
        $this->original = $this->attributes;
        return $this;
    }
}
