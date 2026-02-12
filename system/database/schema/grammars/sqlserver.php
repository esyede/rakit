<?php

namespace System\Database\Schema\Grammars;

defined('DS') or exit('No direct access.');

use System\Magic;
use System\Database\Schema\Table;

class SQLServer extends Grammar
{
    /**
     * Wrapper format.
     *
     * @var string
     */
    public $wrapper = '[%s]';

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
     * Create the sql syntax for adding new columns to a table.
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
            $sql .= $this->collate($table, $column);
            $sql .= $this->incrementer($table, $column);
            $sql .= $this->nullable($table, $column);
            $sql .= $this->defaults($table, $column);
            $sql .= $this->comment($table, $column);
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Create the sql syntax for setting column nullability.
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
     * Create the sql syntax for setting column nullability.
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
     * Create the sql syntax for setting column incrementer.
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
            return ' IDENTITY PRIMARY KEY';
        }
    }

    /**
     * Create the sql syntax for setting column unsigned.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function unsigned(Table $table, Magic $column)
    {
        // SQL Server does not support unsigned types natively.
        return '';
    }

    /**
     * Create the sql syntax for setting column comment.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function comment(Table $table, Magic $column)
    {
        throw new \Exception('Column comments are not supported in SQL Server.');
    }

    /**
     * Create the sql syntax for setting column collation.
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
     * Create the sql syntax for adding a foreign key constraint.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function primary(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' ADD CONSTRAINT ' . $command->name
            . ' PRIMARY KEY (' . $this->columnize($command->columns) . ')';
    }

    /**
     * Create the sql syntax for adding a unique index.
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
     * Create the sql syntax for adding a fulltext index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function fulltext(Table $table, Magic $command)
    {
        return [
            'CREATE FULLTEXT CATALOG ' . $command->catalog,
            'CREATE FULLTEXT INDEX ON ' . $this->wrap($table)
                . ' (' . $this->columnize($command->columns) . ') KEY INDEX ' . $command->key
                . ' ON ' . $command->catalog,
        ];
    }

    /**
     * Create the sql syntax for adding a standard index.
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
     * Create the sql syntax for adding a key.
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
     * Create the sql syntax for creating a table.
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
     * Create the sql syntax for dropping columns from a table.
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
     * Create the sql syntax for drop primary key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_primary(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP CONSTRAINT ' . $command->name;
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
     * Create the sql syntax for drop fulltext key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_fulltext(Table $table, Magic $command)
    {
        return [
            'DROP FULLTEXT INDEX ' . $command->name,
            'DROP FULLTEXT CATALOG ' . $command->catalog,
        ];
    }

    /**
     * Create the sql syntax for drop index key.
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
        return 'DROP INDEX ' . $command->name . ' ON ' . $this->wrap($table);
    }

    /**
     * Drop a foreign key constraint.
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
     * Create the sql syntax for adding a spatial index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function spatial(Table $table, Magic $command)
    {
        return 'CREATE SPATIAL INDEX ' . $command->name . ' ON ' . $this->wrap($table)
            . ' (' . $this->columnize($command->columns) . ')';
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
        return 'EXEC sp_rename \'' . $this->wrap($table) . '.' . $this->wrap($command->from) . '\', \'' . $command->to . '\', \'COLUMN\'';
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
            return 'DROP COLUMN ' . $column;
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
        return 'DROP INDEX IF EXISTS ' . $command->name . ' ON ' . $this->wrap($table);
    }

    /**
     * Create the sql syntax for drop primary if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_unique_if_exists(Table $table, Magic $command)
    {
        return 'DROP INDEX IF EXISTS ' . $command->name . ' ON ' . $this->wrap($table);
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
        return [
            'DROP FULLTEXT INDEX ON ' . $this->wrap($table),
            'DROP FULLTEXT CATALOG ' . $command->catalog,
        ];
    }

    /**
     * Create the sql syntax for drop foreign if exists.
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
     * Create a defunition for string type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_string(Magic $column)
    {
        return 'NVARCHAR(' . $column->length . ')';
    }

    /**
     * Create a defunition for integer type.
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
     * Create a defunition for biginteger type.
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
     * Create a defunition for float type.
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
     * create a defunition for decimal type.
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
     * create a defunition for enum type.
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
     * Create a defunition for boolean type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_boolean(Magic $column)
    {
        return 'TINYINT';
    }

    /**
     * Create a defunition for date type.
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
     * Create a defunition for datetime type.
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
     * Create a defunition for text type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_text(Magic $column)
    {
        return 'NVARCHAR(MAX)';
    }

    /**
     * Create a defunition for longtext type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_longtext(Magic $column)
    {
        return 'NVARCHAR(MAX)';
    }

    /**
     * Create a defunition for blob type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_blob(Magic $column)
    {
        return 'VARBINARY(MAX)';
    }

    /**
     * Create a defunition for double type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_double(Magic $column)
    {
        return 'FLOAT';
    }

    /**
     * Create a defunition for medium integer type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_mediuminteger(Magic $column)
    {
        return 'INT';
    }

    /**
     * Create a defunition for tiny integer type.
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
     * Create a defunition for small integer type.
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
     * Create a defunition for json type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_json(Magic $column)
    {
        return 'NVARCHAR(MAX)';
    }

    /**
     * Create a defunition for jsonb type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_jsonb(Magic $column)
    {
        return 'NVARCHAR(MAX)';
    }

    /**
     * Create a defunition for uuid type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_uuid(Magic $column)
    {
        return 'UNIQUEIDENTIFIER';
    }

    /**
     * Create a defunition for ip address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_ipaddress(Magic $column)
    {
        return 'NVARCHAR(45)';
    }

    /**
     * Create a defunition for mac address type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_macaddress(Magic $column)
    {
        return 'NVARCHAR(17)';
    }

    /**
     * Create a defunition for geometry type.
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
     * Create a defunition for point type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_point(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for linestring type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_linestring(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for polygon type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_polygon(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for geometrycollection type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_geometrycollection(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for multipoint type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multipoint(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for multilinestring type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multilinestring(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for multipolygon type.
     *
     * @param Magic $column
     *
     * @return string
     */
    protected function type_multipolygon(Magic $column)
    {
        return 'GEOMETRY';
    }

    /**
     * Create a defunition for set type.
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

        return sprintf('NVARCHAR(255) CHECK ("%s" IN (%s))', $column->name, $allowed);
    }
}
