<?php

namespace System\Database\Schema\Grammars;

defined('DS') or exit('No direct access.');

use System\Magic;
use System\Database\Schema\Table;

class MySQL extends Grammar
{
    /**
     * Wrapper format.
     *
     * @var string
     */
    public $wrapper = '`%s`';

    /**
     * Create the sql for creating a new table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function create(Table $table, Magic $command)
    {
        $columns = implode(', ', $this->columns($table));

        $sql = 'CREATE TABLE ' . $this->wrap($table) . ' (' . $columns . ')';
        $sql .= is_null($table->engine) ? '' : ' ENGINE = ' . $table->engine;
        $sql .= is_null($table->charset) ? '' : ' DEFAULT CHARACTER SET = ' . $table->charset;
        $sql .= is_null($table->collation) ? '' : ' COLLATE = ' . $table->collation;

        return $sql;
    }

    /**
     * Create the sql for adding new columns to a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function add(Table $table, Magic $command)
    {
        $columns = implode(', ', array_map(function ($column) {
            return 'ADD ' . $column;
        }, $this->columns($table)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
    }

    /**
     * Create the column definitions for a table.
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
            $sql .= $this->charset($table, $column);
            $sql .= $this->collate($table, $column);
            $sql .= $this->nullable($table, $column);
            $sql .= $this->defaults($table, $column);
            $sql .= $this->incrementer($table, $column);
            $sql .= $this->comment($table, $column);
            $sql .= $this->after($table, $column);
            $sql .= $this->first($table, $column);
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Create the sql for unsigned attribute.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function unsigned(Table $table, Magic $column)
    {
        $integers = ['integer', 'biginteger', 'medium_integer', 'tiny_integer', 'small_integer', 'float', 'double', 'decimal'];

        if (in_array($column->type, $integers) && isset($column->unsigned) && $column->unsigned) {
            return ' UNSIGNED';
        }
    }

    /**
     * Create the sql for set charset.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function charset(Table $table, Magic $column)
    {
        $strings = ['string', 'text', 'json', 'jsonb', 'enum', 'set'];

        if (in_array($column->type, $strings) && isset($column->charset) && $column->charset) {
            return ' CHARACTER SET ' . $column->charset;
        }
    }

    /**
     * Create the sql for set collation.
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
     * Create the sql for nullable attribute.
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
     * Create the sql for default value attribute.
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
     * Create the sql for incrementer attribute.
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
            return ' AUTO_INCREMENT PRIMARY KEY';
        }
    }

    /**
     * Create the sql for comment attribute.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function comment(Table $table, Magic $column)
    {
        if (isset($column->comment) && $column->comment) {
            return " COMMENT '" . addslashes($column->comment) . "'";
        }
    }

    /**
     * Create the sql for after column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function after(Table $table, Magic $column)
    {
        if (isset($column->after) && $column->after) {
            return ' AFTER ' . $this->wrap($column->after);
        }
    }

    /**
     * Create the sql for first column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function first(Table $table, Magic $column)
    {
        if (isset($column->first) && $column->first) {
            return ' FIRST';
        }
    }

    /**
     * Create the sql for primary key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function primary(Table $table, Magic $command)
    {
        return $this->key($table, $command->name(null), 'PRIMARY KEY');
    }

    /**
     * Create the sql for unique index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function unique(Table $table, Magic $command)
    {
        return $this->key($table, $command, 'UNIQUE');
    }

    /**
     * Create the sql for fulltext index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function fulltext(Table $table, Magic $command)
    {
        return $this->key($table, $command, 'FULLTEXT');
    }

    /**
     * Create the sql for standard index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function index(Table $table, Magic $command)
    {
        return $this->key($table, $command, 'INDEX');
    }

    /**
     * Create the sql for key.
     *
     * @param Table  $table
     * @param Magic  $command
     * @param string $type
     *
     * @return string
     */
    protected function key(Table $table, Magic $command, $type)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' ADD ' . $type . ' '
            . $command->name . '(' . $this->columnize($command->columns) . ')';
    }

    /**
     * Create the sql for renaming a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function rename(Table $table, Magic $command)
    {
        return 'RENAME TABLE ' . $this->wrap($table) . ' TO ' . $this->wrap($command->name);
    }

    /**
     * Create the sql for drop column.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_column(Table $table, Magic $command)
    {
        $columns = implode(', ', array_map(function ($column) {
            return 'DROP ' . $column;
        }, array_map([$this, 'wrap'], $command->columns)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
    }

    /**
     * Create the sql for drop primary key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_primary(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP PRIMARY KEY';
    }

    /**
     * Create the sql for drop unique key.
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
     * Create the sql for drop fulltext key.
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
     * Create the sql for drop index key.
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
     * Create the sql for drop key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    protected function drop_key(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP INDEX ' . $command->name;
    }

    /**
     * Drop a foreign key constraint from a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_foreign(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP FOREIGN KEY ' . $command->name;
    }

    /**
     * Create the sql for spatial index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function spatial(Table $table, Magic $command)
    {
        return $this->key($table, $command, 'SPATIAL INDEX');
    }

    /**
     * Rename a column on a table.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function rename_column(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' RENAME COLUMN '
            . $this->wrap($command->from) . ' TO ' . $this->wrap($command->to);
    }

    /**
     * Create sql syntax to drop column if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_column_if_exists(Table $table, Magic $command)
    {
        $columns = implode(', ', array_map(function ($column) {
            return 'DROP COLUMN ' . $column;
        }, array_map([$this, 'wrap'], $command->columns)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
    }

    /**
     * Create sql syntax to drop index if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_index_if_exists(Table $table, Magic $command)
    {
        return $this->drop_key($table, $command);
    }

    /**
     * Create sql syntax to drop unique index if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_unique_if_exists(Table $table, Magic $command)
    {
        return $this->drop_key($table, $command);
    }

    /**
     * Create sql syntax to drop fulltext index if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_fulltext_if_exists(Table $table, Magic $command)
    {
        return $this->drop_key($table, $command);
    }

    /**
     * Create sql syntax to drop foreign key if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_foreign_if_exists(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP FOREIGN KEY ' . $command->name;
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
        return 'VARCHAR(' . $column->length . ')';
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
        return 'DOUBLE';
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
        return 'MEDIUMINT';
    }

    /**
     * Create a definition for tiny integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_tinyinteger(Magic $column)
    {
        return 'TINYINT';
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
        return 'SMALLINT';
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
        return 'JSON';
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
        return 'JSON';
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
        return 'CHAR(36)';
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
        return 'VARCHAR(45)';
    }

    /**
     * Create a definition for ip address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_macaddress(Magic $column)
    {
        return 'VARCHAR(17)';
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
        return 'GEOMETRY';
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
        return 'POINT';
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
        return 'LINESTRING';
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
        return 'POLYGON';
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
        return 'GEOMETRYCOLLECTION';
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
        return 'MULTIPOINT';
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
        return 'MULTILINESTRING';
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
        return 'MULTIPOLYGON';
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

        return sprintf('SET(%s)', $allowed);
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
        return 'INT';
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
        return 'BIGINT';
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
        return 'DECIMAL(' . $column->precision . ', ' . $column->scale . ')';
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

        return sprintf('ENUM(%s)', $allowed);
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
        return 'TINYINT(1)';
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
     * Create a definition for timestamp type.
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
        return 'LONGTEXT';
    }

    /**
     * Create a definition for mediumtext type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_blob(Magic $column)
    {
        return 'BLOB';
    }
}
