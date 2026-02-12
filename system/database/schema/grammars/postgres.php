<?php

namespace System\Database\Schema\Grammars;

defined('DS') or exit('No direct access.');

use System\Magic;
use System\Database\Schema\Table;

class Postgres extends Grammar
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
        return 'CREATE TABLE ' . $this->wrap($table) . ' (' . $columns . ')';
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
        $columns = implode(', ', array_map(function ($column) {
            return 'ADD COLUMN ' . $column;
        }, $this->columns($table)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
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
            $sql .= $this->incrementer($table, $column);
            $sql .= $this->nullable($table, $column);
            $sql .= $this->collate($table, $column);
            $sql .= $this->defaults($table, $column);
            $sql .= $this->comment($table, $column);
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Create the sql syntax for indicating that the column can be null.
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
     * Create the sql syntax for setting the default value of the column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function defaults(Table $table, Magic $column)
    {
        if (isset($column->defaults) && null !== $column->defaults) {
            return " DEFAULT '" . $this->default_value($column->defaults) . "'";
        }
    }

    /**
     * Create the sql syntax for auto-increment column definition.
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
            return ' PRIMARY KEY';
        }
    }

    /**
     * Create the sql syntax for indicating unsigned column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function unsigned(Table $table, Magic $column)
    {
        // PostgreSQL does not support unsigned, skip
        return '';
    }

    /**
     * Create the sql syntax for column collation.
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
            return ' COLLATE "' . $column->collate . '"';
        }
    }

    /**
     * Create the sql syntax for column comment.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function comment(Table $table, Magic $column)
    {
        if (isset($column->comment) && $column->comment) {
            return "; COMMENT ON COLUMN " . $this->wrap($table) . "." . $this->wrap($column)
                . " IS '" . addslashes($column->comment) . "'";
        }
    }

    /**
     * Create the sql syntax for creating a primary key column.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function primary(Table $table, Magic $command)
    {
        $columns = $this->columnize($command->columns);
        return 'ALTER TABLE ' . $this->wrap($table) . ' ADD PRIMARY KEY (' . $columns . ')';
    }

    /**
     * Create the sql syntax for creating a unique index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function unique(Table $table, Magic $command)
    {
        $table = $this->wrap($table);
        $columns = $this->columnize($command->columns);

        return 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $command->name
            . ' UNIQUE (' . $columns . ')';
    }

    /**
     * Create the sql syntax for creating a full-text index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function fulltext(Table $table, Magic $command)
    {
        $columns = $this->columnize($command->columns);
        return 'CREATE INDEX ' . $command->name . ' ON ' . $this->wrap($table)
            . ' USING gin(' . $columns . ')';
    }

    /**
     * Create the sql syntax for creating a standard index.
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
     * Create the sql syntax for creating a new index.
     *
     * @param Table $table
     * @param Magic $command
     * @param bool  $unique
     *
     * @return string
     */
    protected function key(Table $table, Magic $command, $unique = false)
    {
        return ($unique ? 'CREATE UNIQUE' : 'CREATE') . ' INDEX ' . $command->name . ' ON '
            . $this->wrap($table) . ' (' . $this->columnize($command->columns) . ')';
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
     * Create the sql syntax for dropping columns.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_column(Table $table, Magic $command)
    {
        $columns = implode(', ', array_map(function ($column) {
            return 'DROP COLUMN ' . $column;
        }, array_map([$this, 'wrap'], $command->columns)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
    }

    /**
     * Create the sql syntax for dropping a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_primary(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP CONSTRAINT ' . $table->name . '_pkey';
    }

    /**
     * Create the sql syntax for dropping a unique constraint.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_unique(Table $table, Magic $command)
    {
        return $this->drop_constraint($table, $command);
    }

    /**
     * Create the sql syntax for dropping a unique constraint.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_fulltext(Table $table, Magic $command)
    {
        return $this->drop_key($table, $command);
    }

    /**
     * Create the sql syntax for dropping an index.
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
     * Create the sql syntax for dropping an index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    protected function drop_key(Table $table, Magic $command)
    {
        return 'DROP INDEX ' . $command->name;
    }

    /**
     * Create the sql syntax for dropping a foreign key constraint.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_foreign(Table $table, Magic $command)
    {
        return $this->drop_constraint($table, $command);
    }

    /**
     * Create the sql syntax for creating a spatial index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function spatial(Table $table, Magic $command)
    {
        return 'CREATE INDEX ' . $command->name . ' ON ' . $this->wrap($table)
            . ' USING gist(' . $this->columnize($command->columns) . ')';
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
        return 'ALTER TABLE ' . $this->wrap($table)
            . ' RENAME COLUMN ' . $this->wrap($command->from)
            . ' TO ' . $this->wrap($command->to);
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
        $columns = implode(', ', array_map(function ($column) {
            return 'DROP COLUMN IF EXISTS ' . $column;
        }, array_map([$this, 'wrap'], $command->columns)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
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
        return 'DROP INDEX IF EXISTS ' . $command->name;
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
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP CONSTRAINT IF EXISTS ' . $command->name;
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
        return 'DROP INDEX IF EXISTS ' . $command->name;
    }

    /**
     * Create the sql syntax for drop fulltext if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_foreign_if_exists(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP CONSTRAINT IF EXISTS ' . $command->name;
    }

    /**
     * Create the sql definition for string type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_string(Magic $column)
    {
        return 'VARCHAR(' . $column->length . ')';
    }

    /**
     * Create the sql definition for integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_integer(Magic $column)
    {
        return $column->increment ? 'SERIAL' : 'BIGINT';
    }

    /**
     * Create the sql definition for big integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_biginteger(Magic $column)
    {
        return $column->increment ? 'BIGSERIAL' : 'BIGINT';
    }

    /**
     * Create the sql definition for float type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_float(Magic $column)
    {
        return 'REAL';
    }

    /**
     * Create the sql definition for decimal type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_decimal(Magic $column)
    {
        return 'DECIMAL(' . $column->precision . ', ' . $column->scale . ')';
    }

    /**
     * Create the sql definition for enum type.
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

         return sprintf('VARCHAR(255) CHECK ("%s" IN (%s))', $column->name, $allowed);
    }

    /**
     * Create the sql definition for boolean type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_boolean(Magic $column)
    {
        return 'SMALLINT';
    }

    /**
     * Create the sql definition for date type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_date(Magic $column)
    {
        return 'TIMESTAMP(0) WITHOUT TIME ZONE';
    }

    /**
     * Create the sql definition for datetime type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_timestamp(Magic $column)
    {
        return 'TIMESTAMP';
    }

    /**
     * Create the sql definition for text type.
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
     * Create the sql definition for longtext type.
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
     * Create the sql definition for blob type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_blob(Magic $column)
    {
        return 'BYTEA';
    }

    /**
     * Create the sql definition for double type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_double(Magic $column)
    {
        return 'DOUBLE PRECISION';
    }

    /**
     * Create the sql definition for medium integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_mediuminteger(Magic $column)
    {
        return $column->increment ? 'SERIAL' : 'INTEGER';
    }

    /**
     * Create the sql definition for tiny integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_tinyinteger(Magic $column)
    {
        return $column->increment ? 'SMALLSERIAL' : 'SMALLINT';
    }

    /**
     * Create the sql definition for small integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_smallinteger(Magic $column)
    {
        return $column->increment ? 'SMALLSERIAL' : 'SMALLINT';
    }

    /**
     * Create the sql definition for json type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_json(Magic $column)
    {
        return 'JSON';
    }

    /**
     * Create the sql definition for jsonb type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_jsonb(Magic $column)
    {
        return 'JSONB';
    }

    /**
     * Create the sql definition for uuid type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_uuid(Magic $column)
    {
        return 'UUID';
    }

    /**
     * Create the sql definition for ip address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_ipaddress(Magic $column)
    {
        return 'INET';
    }

    /**
     * Create the sql definition for mac address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_macaddress(Magic $column)
    {
        return 'MACADDR';
    }

    /**
     * Create the sql definition for geometry type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_geometry(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create the sql definition for point type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_point(Magic $column)
    {
        return 'GEOMETRY(POINT)';
    }

    /**
     * Create the sql definition for linestring type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_linestring(Magic $column)
    {
        return 'GEOMETRY(LINESTRING)';
    }

    /**
     * Create the sql definition for polygon type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_polygon(Magic $column)
    {
        return 'GEOMETRY(POLYGON)';
    }

    /**
     * Create the sql definition for geometrycollection type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_geometrycollection(Magic $column)
    {
        return 'GEOMETRY(GEOMETRYCOLLECTION)';
    }

    /**
     * Create the sql definition for multipoint type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multipoint(Magic $column)
    {
        return 'GEOMETRY(MULTIPOINT)';
    }

    /**
     * Create the sql definition for multilinestring type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multilinestring(Magic $column)
    {
        return 'GEOMETRY(MULTILINESTRING)';
    }

    /**
     * Create the sql definition for multipolygon type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multipolygon(Magic $column)
    {
        return 'GEOMETRY(MULTIPOLYGON)';
    }

    /**
     * Create the sql definition for set type.
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

        return sprintf('VARCHAR(255) CHECK ("%s" IN (%s))', $column->name, $allowed);
    }
}
