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
        $sql = 'CREATE TABLE '.$this->wrap($table).' ('.$columns;
        $primary = Arr::first($table->commands, function ($key, $value) {
            return 'primary' === $value->type;
        });

        if (! is_null($primary)) {
            $columns = $this->columnize($primary->columns);
            $sql .= ', PRIMARY KEY ('.$columns.')';
        }

        foreach ($table->commands as $item) {
            if ('foreign' === $item->type) {
                $sql .= ', '.$this->foreign_key($item);
            }
        }

        return $sql .= ')';
    }

    /**
     * Create the sql syntax for inline primary key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function primary(Table $table, Magic $command)
    {
        if ($table->creating()) {
            return [];
        }

        throw new \Exception('Adding a primary key to an existing table is not supported in SQLite.');
    }

    /**
     * Create the sql syntax for inline foreign key constraint.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function foreign(Table $table, Magic $command)
    {
        if ($table->creating()) {
            return [];
        }

        throw new \Exception('Adding a foreign key to an existing table is not supported in SQLite.');
    }

    /**
     * Create the sql syntax for a foreign key clause.
     *
     * @param Magic $command
     *
     * @return string
     */
    protected function foreign_key(Magic $command)
    {
        $references = is_array($command->references) ? $command->references : [$command->references];

        $sql = 'FOREIGN KEY ('.$this->columnize($command->columns).')'
            .' REFERENCES '.$this->wrap_table($command->on).' ('.$this->columnize($references).')';
        $sql .= is_null($command->on_delete) ? '' : ' ON DELETE '.$command->on_delete;
        $sql .= is_null($command->on_update) ? '' : ' ON UPDATE '.$command->on_update;

        return $sql;
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
            return 'ADD COLUMN '.$column;
        }, $this->columns($table));

        $sql = [];

        foreach ($columns as $column) {
            $sql[] = 'ALTER TABLE '.$this->wrap($table).' '.$column;
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
            $sql = $this->wrap($column).' '.$this->type($column);
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
            return " DEFAULT '".str_replace("'", "''", $this->default_value($column->defaults))."'";
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
            return ' COLLATE '.$column->collate;
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
        return 'CREATE VIRTUAL TABLE '.$this->wrap($table).' USING fts4('.$columns.')';
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
        return ($unique ? 'CREATE UNIQUE' : 'CREATE').' INDEX '.$command->name
            .' ON '.$this->wrap($table).' ('.$this->columnize($command->columns).')';
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
        return 'ALTER TABLE '.$this->wrap($table).' RENAME TO '.$this->wrap($command->name);
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
        return 'DROP INDEX '.$this->wrap($command->name);
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
        throw new \Exception('Dropping a primary key is not supported in SQLite. Recreate the table instead.');
    }

    /**
     * Create the sql syntax for drop foreign key.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_foreign(Table $table, Magic $command)
    {
        throw new \Exception('Dropping a foreign key is not supported in SQLite. Recreate the table instead.');
    }

    /**
     * Create the sql syntax for drop fulltext index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function drop_fulltext(Table $table, Magic $command)
    {
        return 'DROP TABLE '.$this->wrap($command->name);
    }

    /**
     * Create the sql syntax for drop column.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function drop_column(Table $table, Magic $command)
    {
        $this->supported('3.35.0', 'Drop column');

        $sql = [];

        foreach ($command->columns as $column) {
            $sql[] = 'ALTER TABLE '.$this->wrap($table).' DROP COLUMN '.$this->wrap($column);
        }

        return $sql;
    }

    /**
     * Make sure the SQLite library is new enough for an operation.
     *
     * @param string $minimum
     * @param string $operation
     */
    protected function supported($minimum, $operation)
    {
        $version = (string) $this->connection->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);

        if (version_compare($version, $minimum, '<')) {
            throw new \Exception(sprintf(
                '%s requires SQLite %s or newer, %s given.',
                $operation,
                $minimum,
                $version
            ));
        }
    }

    /**
     * Get the list of existing columns of a table.
     *
     * @param Table $table
     *
     * @return array
     */
    protected function existing(Table $table)
    {
        $prefix = isset($this->connection->config['prefix']) ? $this->connection->config['prefix'] : '';
        $name = str_replace(["'", '.'], ["''", '__'], $prefix.$table->name);
        $statement = $this->connection->pdo()->query("PRAGMA table_info('".$name."')");

        return $statement ? $statement->fetchAll(\PDO::FETCH_COLUMN, 1) : [];
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
        return 'CREATE VIRTUAL TABLE '.$this->wrap($table)
            .' USING rtree('.$this->columnize($command->columns).')';
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
        $this->supported('3.25.0', 'Rename column');

        return 'ALTER TABLE '.$this->wrap($table).' RENAME COLUMN '
            .$this->wrap($command->from).' TO '.$this->wrap($command->to);
    }

    /**
     * Create the sql syntax for drop column if exists.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return array
     */
    public function drop_column_if_exists(Table $table, Magic $command)
    {
        $columns = array_values(array_intersect($command->columns, $this->existing($table)));

        if (empty($columns)) {
            return [];
        }

        return $this->drop_column($table, new Magic(compact('columns')));
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
        return 'DROP INDEX IF EXISTS '.$this->wrap($command->name);
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
        return 'DROP INDEX IF EXISTS '.$this->wrap($command->name);
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
        return 'DROP TABLE IF EXISTS '.$this->wrap($command->name);
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
        return [];
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
        return 'DECIMAL('.$column->precision.', '.$column->scale.')';
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
            return "'".str_replace("'", "''", (string) $item)."'";
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
            return "'".str_replace("'", "''", (string) $item)."'";
        }, $column->allowed));

        return sprintf('TEXT CHECK ("%s" IN (%s))', $column->name, $allowed);
    }
}
