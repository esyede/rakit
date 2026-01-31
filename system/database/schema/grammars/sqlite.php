<?php

namespace System\Database\Schema\Grammars;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Magic;
use System\Database\Schema\Table;

class SQLite extends Grammar
{
    /**
     * Buat sintaks sql untuk pembuatan tabel.
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
     * Buat sintaks sql untuk modifikasi tabel.
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
     * Buat sintaks sql definisi kolom.
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
     * Buat sintaks sql untuk indikasi bahwa kolom boleh null.
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
     * Buat sintaks sql untuk set default value kolom.
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
     * Buat sintaks sql untuk definisi kolom auto-increment.
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
     * Buat sintaks sql untuk indikasi unsigned column.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function unsigned(Table $table, Magic $column)
    {
        // SQLite tidak mendukung unsigned, skip
        return '';
    }

    /**
     * Buat sintaks sql untuk comment kolom.
     *
     * @param Table $table
     * @param Magic $column
     *
     * @return string
     */
    protected function comment(Table $table, Magic $column)
    {
        // SQLite tidak mendukung comment pada kolom, skip
        return '';
    }

    /**
     * Buat sintaks sql untuk collate kolom.
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
     * Buat sintaks sql untuk membuat unique index.
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
     * Buat sintaks sql untuk membuat fulltext index.
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
     * Buat sintaks sql untuk membuat index biasa.
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
     * Buat sintaks sql untuk membuat index baru.
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
     * Buat sintaks sql untuk rename tabel.
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
     * Buat sintaks sql untuk drop unique key.
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
     * Buat sintaks sql untuk drop unique key.
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
     * Buat sintaks sql untuk drop key.
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
     * Buat sintaks sql untuk spatial index.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function spatial(Table $table, Magic $command)
    {
        // SQLite spatial menggunakan R-Tree, asumsikan extension
        return 'CREATE VIRTUAL TABLE ' . $this->wrap($table) . ' USING rtree(' . $this->columnize($command->columns) . ')';
    }

    /**
     * Buat sintaks sql untuk rename kolom.
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
     * Buat sintaks sql untuk drop kolom jika ada.
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
     * Buat sintaks sql untuk drop index jika ada.
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
     * Buat sintaks sql untuk drop unique jika ada.
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
     * Buat sintaks sql untuk drop fulltext jika ada.
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
     * Buat sintaks sql untuk drop foreign jika ada.
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
     * Buat definisi tipe data string.
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
     * Buat definisi tipe data integer.
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
     * Buat definisi tipe data big integer.
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
     * Buat definisi tipe data float.
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
     * Buat definisi tipe data decimal.
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
     * Buat definisi tipe data enum.
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
     * Buat definisi tipe data boolean.
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
     * Buat definisi tipe data date.
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
     * Buat definisi tipe data timestamp.
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
     * Buat definisi tipe data text.
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
     * Buat definisi tipe data longtext.
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
     * Buat definisi tipe data blob.
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
     * Buat definisi tipe data double.
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
     * Buat definisi tipe data medium integer.
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
     * Buat definisi tipe data tiny integer.
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
     * Buat definisi tipe data small integer.
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
     * Buat definisi tipe data json.
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
     * Buat definisi tipe data jsonb.
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
     * Buat definisi tipe data uuid.
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
     * Buat definisi tipe data ip address.
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
     * Buat definisi tipe data mac address.
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
     * Buat definisi tipe data geometry.
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
     * Buat definisi tipe data point.
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
     * Buat definisi tipe data linestring.
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
     * Buat definisi tipe data polygon.
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
     * Buat definisi tipe data geometrycollection.
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
     * Buat definisi tipe data multipoint.
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
     * Buat definisi tipe data multilinestring.
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
     * Buat definisi tipe data multipolygon.
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
     * Buat definisi tipe data set.
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
