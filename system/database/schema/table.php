<?php

namespace System\Database\Schema;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Magic;
use System\Config;

class Table
{
    /**
     * Contains the name of the table.
     *
     * @var string
     */
    public $name;

    /**
     * Contains the database connection that should be used by the table.
     *
     * @var string
     */
    public $connection;

    /**
     * Contains the storage engine that should be used by the table.
     *
     * @var string
     */
    public $engine;

    /**
     * Contains the charset that should be used by the table.
     *
     * @var string
     */
    public $charset;

    /**
     * Contains the collation that should be used by the table.
     *
     * @var string
     */
    public $collation;

    /**
     * Contains the list of columns that should be created/modified.
     *
     * @var array
     */
    public $columns = [];

    /**
     * Contains the list of commands that should be run for the table.
     *
     * @var array
     */
    public $commands = [];

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->charset = Config::get('database.connections.mysql.charset');
    }

    /**
     * Create the database table.
     *
     * @return Magic
     */
    public function create()
    {
        return $this->command('create');
    }

    /**
     * Create primary key pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function primary($columns, $name = null)
    {
        return $this->key('primary', $columns, $name);
    }

    /**
     * Set the charset for the table.
     *
     * @param string $charset
     */
    public function charset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Set the collation for the table.
     *
     * @param string $collation
     */
    public function collate($collation)
    {
        $this->collation = $collation;
    }

    /**
     * Create a unique index on the table.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function unique($columns, $name = null)
    {
        return $this->key('unique', $columns, $name);
    }

    /**
     * Create a full-text index on the table.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function fulltext($columns, $name = null)
    {
        return $this->key('fulltext', $columns, $name);
    }

    /**
     * Create a standard index pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function index($columns, $name = null)
    {
        return $this->key('index', $columns, $name);
    }

    /**
     * Create a foreign key constraint on the table.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function foreign($columns, $name = null)
    {
        return $this->key('foreign', $columns, $name);
    }

    /**
     * Create a key on the table.
     *
     * @param string       $type
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function key($type, $columns, $name)
    {
        $columns = is_array($columns) ? $columns : [$columns];

        if (is_null($name)) {
            $name = str_replace(['-', '.'], '_', $this->name);
            $name = $name . '_' . implode('_', $columns) . '_' . $type;
        }

        return $this->command($type, compact('name', 'columns'));
    }

    /**
     * Rename the database table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function rename($name)
    {
        return $this->command('rename', compact('name'));
    }

    /**
     * Delete the database table.
     *
     * @return Magic
     */
    public function drop()
    {
        return $this->command('drop');
    }

    /**
     * Delete a column from the table.
     *
     * @param string|array $columns
     */
    public function drop_column($columns)
    {
        $columns = is_array($columns) ? $columns : [$columns];
        return $this->command('drop_column', compact('columns'));
    }

    /**
     * Delete primary key from the table.
     *
     * @param string $name
     */
    public function drop_primary($name = null)
    {
        return $this->drop_key('drop_primary', $name);
    }

    /**
     * Delete unique index from the table.
     *
     * @param string $name
     */
    public function drop_unique($name)
    {
        return $this->drop_key('drop_unique', $name);
    }

    /**
     * Delete full-text index from the table.
     *
     * @param string $name
     */
    public function drop_fulltext($name)
    {
        return $this->drop_key('drop_fulltext', $name);
    }

    /**
     * Delete index from the table.
     *
     * @param string $name
     */
    public function drop_index($name)
    {
        return $this->drop_key('drop_index', $name);
    }

    /**
     * Delet foreign key constraint from the table.
     *
     * @param string $name
     */
    public function drop_foreign($name)
    {
        return $this->drop_key('drop_foreign', $name);
    }

    /**
     * Drop key from the table.
     *
     * @param string $type
     * @param string $name
     *
     * @return Magic
     */
    protected function drop_key($type, $name)
    {
        return $this->command($type, compact('name'));
    }

    /**
     * Add auto-incrementing integer column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function increments($name)
    {
        return $this->integer($name, true);
    }

    /**
     * ADD A varchar column to the table.
     *
     * @param string $name
     * @param int    $length
     *
     * @return Magic
     */
    public function string($name, $length = 200)
    {
        return $this->column('string', compact('name', 'length'));
    }

    /**
     * Add integer column to the table.
     *
     * @param string $name
     * @param bool   $increment
     *
     * @return Magic
     */
    public function integer($name, $increment = false)
    {
        return $this->column('integer', compact('name', 'increment'));
    }

    /**
     * Add big integer column to the table.
     *
     * @param string $name
     * @param bool   $increment
     *
     * @return Magic
     */
    public function biginteger($name, $increment = false)
    {
        return $this->column('biginteger', compact('name', 'increment'));
    }

    /**
     * Add float column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function float($name)
    {
        return $this->column('float', compact('name'));
    }

    /**
     * Add enum column to the table.
     *
     * @param string $name
     * @param array  $allowed
     *
     * @return Magic
     */
    public function enum($name, array $allowed)
    {
        return $this->column('enum', compact('name', 'allowed'));
    }

    /**
     * Add decimal column to the table.
     *
     * @param string $name
     * @param int    $precision
     * @param int    $scale
     *
     * @return Magic
     */
    public function decimal($name, $precision, $scale)
    {
        return $this->column('decimal', compact('name', 'precision', 'scale'));
    }

    /**
     * Add boolean column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function boolean($name)
    {
        return $this->column('boolean', compact('name'));
    }

    /**
     * Add add created_at and updated_at timestamp columns to the table.
     */
    public function timestamps()
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Add date column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function date($name)
    {
        return $this->column('date', compact('name'));
    }

    /**
     * Add datetime column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function timestamp($name)
    {
        return $this->column('timestamp', compact('name'));
    }

    /**
     * Add text column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function text($name)
    {
        return $this->column('text', compact('name'));
    }

    /**
     * Add longtext column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function longtext($name)
    {
        return $this->column('longtext', compact('name'));
    }

    /**
     * Add blob column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function blob($name)
    {
        return $this->column('blob', compact('name'));
    }

    /**
     * Add blob column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function double($name)
    {
        return $this->column('double', compact('name'));
    }

    /**
     * Add medium integer column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function mediuminteger($name)
    {
        return $this->column('mediuminteger', compact('name'));
    }

    /**
     * Add tiny integer column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function tinyinteger($name)
    {
        return $this->column('tinyinteger', compact('name'));
    }

    /**
     * Add small integer column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function smallinteger($name)
    {
        return $this->column('smallinteger', compact('name'));
    }

    /**
     * Add json column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function json($name)
    {
        return $this->column('json', compact('name'));
    }

    /**
     * Add json column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function jsonb($name)
    {
        return $this->column('jsonb', compact('name'));
    }

    /**
     * Add uuid column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function uuid($name)
    {
        return $this->column('uuid', compact('name'));
    }

    /**
     * Add ip address column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function ipaddress($name)
    {
        return $this->column('ipaddress', compact('name'));
    }

    /**
     * Add mac address column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function macaddress($name)
    {
        return $this->column('macaddress', compact('name'));
    }

    /**
     * Add geometry column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function geometry($name)
    {
        return $this->column('geometry', compact('name'));
    }

    /**
     * Add point column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function point($name)
    {
        return $this->column('point', compact('name'));
    }

    /**
     * Add linestring column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function linestring($name)
    {
        return $this->column('linestring', compact('name'));
    }

    /**
     * Add polygon column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function polygon($name)
    {
        return $this->column('polygon', compact('name'));
    }

    /**
     * Add geometrycollection column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function geometrycollection($name)
    {
        return $this->column('geometrycollection', compact('name'));
    }

    /**
     * Add geometrycollection column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function multipoint($name)
    {
        return $this->column('multipoint', compact('name'));
    }

    /**
     * Add multilinestring column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function multilinestring($name)
    {
        return $this->column('multilinestring', compact('name'));
    }

    /**
     * Add multipolygon column to the table.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function multipolygon($name)
    {
        return $this->column('multipolygon', compact('name'));
    }

    /**
     * Add set column to the table.
     *
     * @param string $name
     * @param array  $allowed
     *
     * @return Magic
     */
    public function set($name, array $allowed)
    {
        return $this->column('set', compact('name', 'allowed'));
    }

    /**
     * Set the column as nullable.
     *
     * @return $this
     */
    public function nullable()
    {
        $column = end($this->columns);

        if ($column) {
            $column->nullable = true;
        }

        return $this;
    }

    /**
     * Set the default value for the column.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function defaults($value)
    {
        $column = end($this->columns);

        if ($column) {
            $column->defaults = $value;
        }

        return $this;
    }

    /**
     * Set the column as unsigned.
     *
     * @return $this
     */
    public function unsigned()
    {
        $column = end($this->columns);

        if ($column) {
            $column->unsigned = true;
        }

        return $this;
    }

    /**
     * Set a comment for the column.
     *
     * @param string $comment
     *
     * @return $this
     */
    public function comment($comment)
    {
        $column = end($this->columns);

        if ($column) {
            $column->comment = $comment;
        }

        return $this;
    }

    /**
     * Set a column to be added after another column.
     *
     * @param string $column
     *
     * @return $this
     */
    public function after($column)
    {
        $current = end($this->columns);

        if ($current) {
            $current->after = $column;
        }

        return $this;
    }

    /**
     * Set a column to be the first column.
     *
     * @return $this
     */
    public function first()
    {
        $column = end($this->columns);

        if ($column) {
            $column->first = true;
        }

        return $this;
    }

    /**
     * Mark the column as changed.
     *
     * @return $this
     */
    public function change()
    {
        $column = end($this->columns);

        if ($column) {
            $column->change = true;
        }

        return $this;
    }

    /**
     * Set the referenced columns for foreign key.
     *
     * @param string|array $columns
     *
     * @return $this
     */
    public function references($columns)
    {
        $command = end($this->commands);

        if ($command && $command->type === 'foreign') {
            $command->references = $columns;
        }

        return $this;
    }

    /**
     * Set on table for foreign key.
     *
     * @param string $table
     *
     * @return $this
     */
    public function on($table)
    {
        $command = end($this->commands);

        if ($command && $command->type === 'foreign') {
            $command->on = $table;
        }

        return $this;
    }

    /**
     * Set on delete action for foreign key.
     *
     * @param string $action
     *
     * @return $this
     */
    public function on_delete($action)
    {
        $command = end($this->commands);

        if ($command && $command->type === 'foreign') {
            $command->on_delete = $action;
        }

        return $this;
    }

    /**
     * Set on update action for foreign key.
     *
     * @param string $action
     *
     * @return $this
     */
    public function on_update($action)
    {
        $command = end($this->commands);

        if ($command && $command->type === 'foreign') {
            $command->on_update = $action;
        }

        return $this;
    }

    /**
     * Rename clomun on the table.
     *
     * @param string $from
     * @param string $to
     *
     * @return Magic
     */
    public function rename_column($from, $to)
    {
        return $this->command('rename_column', compact('from', 'to'));
    }

    /**
     * Make a spatial index on the table.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function spatial_index($columns, $name = null)
    {
        return $this->key('spatial', $columns, $name);
    }

    /**
     * Set the storage engine for the table.
     *
     * @param string $engine
     *
     * @return $this
     */
    public function engine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Make soft delete column on the table.
     */
    public function soft_deletes()
    {
        $this->timestamp('deleted_at')->nullable();
    }

    /**
     * Delete column if exists.
     *
     * @param string|array $columns
     */
    public function drop_column_if_exists($columns)
    {
        $columns = is_array($columns) ? $columns : array($columns);
        return $this->command('drop_column_if_exists', compact('columns'));
    }

    /**
     * Delet index if exists.
     *
     * @param string $name
     */
    public function drop_index_if_exists($name)
    {
        return $this->command('drop_index_if_exists', compact('name'));
    }

    /**
     * Delete unique index if exists.
     *
     * @param string $name
     */
    public function drop_unique_if_exists($name)
    {
        return $this->command('drop_unique_if_exists', compact('name'));
    }

    /**
     * Delete full-text index if exists.
     *
     * @param string $name
     */
    public function drop_fulltext_if_exists($name)
    {
        return $this->command('drop_fulltext_if_exists', compact('name'));
    }

    /**
     * Delet foreign key constraint if exists.
     *
     * @param string $name
     */
    public function drop_foreign_if_exists($name)
    {
        return $this->command('drop_foreign_if_exists', compact('name'));
    }

    /**
     * Set the database connection that should be used by the table.
     *
     * @param string $connection
     */
    public function connection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Check if the table is being created.
     *
     * @return bool
     */
    public function creating()
    {
        return !is_null(Arr::first($this->commands, function ($key, $value) {
            return 'create' === $value->type;
        }));
    }

    /**
     * Create a new command instance.
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return Magic
     */
    protected function command($type, array $parameters = [])
    {
        return $this->commands[] = new Magic(array_merge(compact('type'), $parameters));
    }

    /**
     * Create a new column instance.
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return Magic
     */
    protected function column($type, array $parameters = [])
    {
        return $this->columns[] = new Magic(array_merge(compact('type'), $parameters));
    }
}
