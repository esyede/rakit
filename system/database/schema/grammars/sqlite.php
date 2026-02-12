<?php

namespace System\Database\Schema\Grammars;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Magic;
use System\Database\Schema\Table;

class SQLite extends Grammar
{
    /**
     * Create the sql syntax for creating a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function create(Table $table, Magic $command)
    {
        $columns = implode(', ', $this->columns($table));
        $sql = 'CREATE TABLE ' . $this->wrap($table) . ' (' . $columns;
        $primary = Arr::first($table->commands, function ($key, $value) {
            return 'primary' === $value->type;
        });

        if (!is_null($primary)) {
            $columns = $this->columnize($primary->columns);
            $sql .= ', PRIMARY KEY (' . $columns . ')';
        }

        return $sql .= ')';
    }

    /**
     * Create the sql syntax for modifying a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function add(Table $table, Magic $command)
    {
        $columns = array_map(function ($column) {
            return 'ADD COLUMN ' . $column;
        }, $this->columns($table));

        $sql = [];

        foreach ($columns as $column) {
            $sql[] = 'ALTER TABLE ' . $this->wrap($table) . ' ' . $column;
        }

        return $sql;
    }

    /**
     * Create the sql syntax for column definitions.
     *
     * @param Table $table
     *
     * @return array
     */
    protected function columns(Table $table)
    {
        $columns = [];

        foreach ($table->columns as $column) {
            $sql = $this->wrap($column) . ' ' . $this->type($column);
            $sql .= $this->unsigned($table, $column);
            $sql .= $this->collate($table, $column);
            $sql .= $this->nullable($table, $column);
            $sql .= $this->defaults($table, $column);
            $sql .= $this->incrementer($table, $column);
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Create the sql syntax for nullable column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function nullable(Table $table, Magic $column)
    {
        return (isset($column->nullable) && $column->nullable) ? ' NULL' : ' NOT NULL';
    }

    /**
     * Create the sql syntax for defaults column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function defaults(Table $table, Magic $column)
    {
        if (isset($column->defaults) && null !== $column->defaults) {
            return ' DEFAULT ' . $this->wrap($this->default_value($column->defaults));
        }
    }

    /**
     * Create the sql syntax for incrementer column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function incrementer(Table $table, Magic $column)
    {
        $integers = ['integer', 'biginteger', 'mediuminteger', 'tinyinteger', 'smallinteger'];

        if (in_array($column->type, $integers) && $column->increment) {
            return ' PRIMARY KEY AUTOINCREMENT';
        }
    }

    /**
     * Create the sql syntax for unsigned column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function unsigned(Table $table, Magic $column)
    {
        // SQLite does not unsigned, skip
        return '';
    }

    /**
     * Create the sql syntax for comment column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function comment(Table $table, Magic $column)
    {
        // SQLite does not support column comments, skip
        return '';
    }

    /**
     * Create the sql syntax for collate column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function collate(Table $table, Magic $column)
    {
        $strings = ['string', 'text', 'json', 'jsonb', 'enum', 'set'];

        if (in_array($column->type, $strings) && isset($column->collate) && $column->collate) {
            return ' COLLATE ' . $column->collate;
        }
    }

    /**
     * Create the sql syntax for creating unique index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function unique(Table $table, Magic $command)
    {
        return $this->key($table, $command, true);
    }

    /**
     * Create the sql syntax for creating fulltext index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function fulltext(Table $table, Magic $command)
    {
        $columns = $this->columnize($command->columns);
        return 'CREATE VIRTUAL TABLE ' . $this->wrap($table) . ' USING fts4(' . $columns . ')';
    }

    /**
     * Create the sql syntax for creating index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function index(Table $table, Magic $command)
    {
        return $this->key($table, $command);
    }

    /**
     * Create the sql syntax for creating key.
     *
     * @param Table $table
     * @param Magic $command
     * @param bool  $unique
     *
     * @return string
     */
    protected function key(Table $table, Magic $command, $unique = false)
    {
        return ($unique ? 'CREATE UNIQUE' : 'CREATE') . ' INDEX ' . $command->name
            . ' ON ' . $this->wrap($table) . ' (' . $this->columnize($command->columns) . ')';
    }

    /**
     * Create the sql syntax for renaming a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function rename(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' RENAME TO ' . $this->wrap($command->name);
    }

    /**
     * Create the sql syntax for drop unique key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_unique(Table $table, Magic $command)
    {
        return $this->drop_key($table, $command);
    }

    /**
     * Create the sql syntax for drop index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_index(Table $table, Magic $command)
    {
        return $this->drop_key($table, $command);
    }

    /**
     * Create the sql syntax for drop key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    protected function drop_key(Table $table, Magic $command)
    {
        return 'DROP INDEX ' . $this->wrap($command->name);
    }

    /**
     * Create the sql syntax for creating spatial index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function spatial(Table $table, Magic $command)
    {
        // SQLite spatial index will be using R-Tree module
        return 'CREATE VIRTUAL TABLE ' . $this->wrap($table)
            . ' USING rtree(' . $this->columnize($command->columns) . ')';
    }

    /**
     * Create the sql syntax for renaming a column.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function rename_column(Table $table, Magic $command)
    {
        throw new \Exception('Rename column is not supported in SQLite. Recreate the table instead.');
    }

    /**
     * Create the sql syntax for drop column if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_column_if_exists(Table $table, Magic $command)
    {
        throw new \Exception('Drop column IF EXISTS is not supported in SQLite.');
    }

    /**
     * Create the sql syntax for drop index if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_index_if_exists(Table $table, Magic $command)
    {
        return 'DROP INDEX IF EXISTS ' . $this->wrap($command->name);
    }

    /**
     * Create the sql syntax for drop index if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_unique_if_exists(Table $table, Magic $command)
    {
        return 'DROP INDEX IF EXISTS ' . $this->wrap($command->name);
    }

    /**
     * Create the sql syntax for drop fulltext if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_fulltext_if_exists(Table $table, Magic $command)
    {
        return 'DROP TABLE IF EXISTS ' . $this->wrap($command->name);
    }

    /**
     * Create the sql syntax for drop foreign key if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_foreign_if_exists(Table $table, Magic $command)
    {
        throw new \Exception('Drop foreign key IF EXISTS is not supported in SQLite.');
    }

    /**
     * Create a definition for string type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_string(Magic $column)
    {
        return 'VARCHAR';
    }

    /**
     * Create a definition for integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_integer(Magic $column)
    {
        return 'INTEGER';
    }

    /**
     * Create a definition for big integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_biginteger(Magic $column)
    {
        return 'INTEGER';
    }

    /**
     * Create a definition for float type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_float(Magic $column)
    {
        return 'FLOAT';
    }

    /**
     * Create a definition for decimal type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_decimal(Magic $column)
    {
        return 'FLOAT';
    }

    /**
     * Create a definition for enum type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_enum(Magic $column)
    {
        $allowed = implode(', ', array_map(function ($item) {
            return "'" . $item . "'";
        }, $column->allowed));

        return sprintf('VARCHAR CHECK ("%s" IN (%s))', $column->name, $allowed);
    }

    /**
     * Create a definition for boolean type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_boolean(Magic $column)
    {
        return 'INTEGER';
    }

    /**
     * Create a definition for date type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_date(Magic $column)
    {
        return 'DATETIME';
    }

    /**
     * Create a definition for datetime type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_timestamp(Magic $column)
    {
        return 'DATETIME';
    }

    /**
     * Create a definition for text type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_text(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for longtext type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_longtext(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for blob type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_blob(Magic $column)
    {
        return 'BLOB';
    }

    /**
     * Create a definition for double type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_double(Magic $column)
    {
        return 'REAL';
    }

    /**
     * Create a definition for medium integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_mediuminteger(Magic $column)
    {
        return 'INTEGER';
    }

    /**
     * Create a definition for medium integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_tinyinteger(Magic $column)
    {
        return 'INTEGER';
    }

    /**
     * Create a definition for small integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_smallinteger(Magic $column)
    {
        return 'INTEGER';
    }

    /**
     * Create a definition for json type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_json(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for jsonb type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_jsonb(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for uuid type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_uuid(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for ip address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_ipaddress(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for mac address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_macaddress(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for geometry type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_geometry(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for point type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_point(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for linestring type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_linestring(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for polygon type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_polygon(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for geometrycollection type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_geometrycollection(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for multipoint type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multipoint(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for multilinestring type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multilinestring(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for multipolygon type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multipolygon(Magic $column)
    {
        return 'TEXT';
    }

    /**
     * Create a definition for set type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_set(Magic $column)
    {
        $allowed = implode(', ', array_map(function ($item) {
            return "'" . $item . "'";
        }, $column->allowed));

        return sprintf('TEXT CHECK ("%s" IN (%s))', $column->name, $allowed);
    }
}
