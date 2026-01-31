<?php

namespace System\Database\Schema\Grammars;

defined('DS') or exit('No direct access.');

use System\Magic;
use System\Database\Schema\Table;

class MySQL extends Grammar
{
    /**
     * Identifier keyword milik engine database.
     *
     * @var string
     */
    public $wrapper = '`%s`';

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

        $sql = 'CREATE TABLE ' . $this->wrap($table) . ' (' . $columns . ')';
        $sql .= is_null($table->engine) ? '' : ' ENGINE = ' . $table->engine;
        $sql .= is_null($table->charset) ? '' : ' DEFAULT CHARACTER SET = ' . $table->charset;
        $sql .= is_null($table->collation) ? '' : ' COLLATE = ' . $table->collation;

        return $sql;
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
        $columns = implode(', ', array_map(function ($column) {
            return 'ADD ' . $column;
        }, $this->columns($table)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
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
     * Buat sintaks sql untuk indikasi unsigned column.
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
     * Buat sintaks sql untuk set charset.
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
     * Buat sintaks sql untuk set collation.
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
            return " DEFAULT '" . $this->default_value($column->defaults) . "'";
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
            return ' AUTO_INCREMENT PRIMARY KEY';
        }
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
        if (isset($column->comment) && $column->comment) {
            return " COMMENT '" . addslashes($column->comment) . "'";
        }
    }

    /**
     * Buat sintaks sql untuk after kolom.
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
     * Buat sintaks sql untuk first kolom.
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
     * Buat sintaks sql untuk membuat kolom primary key.
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
     * Buat sintaks sql untuk membuat unique index.
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
     * Buat sintaks sql untuk membuat fulltext index.
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
     * Buat sintaks sql untuk membuat index biasa.
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
     * Buat sintaks sql untuk membuat index baru.
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
     * Buat sintaks sql untuk rename tabel.
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
     * Buat sintaks sql untuk drop kolom.
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
     * Buat sintaks sql untuk drop primary key.
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
     * Buat sintaks sql untuk drop fultext key.
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
     * Buat sintaks sql untuk drop key index biasa.
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
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP INDEX ' . $command->name;
    }

    /**
     * Drop foreign key constraint dari tabel.
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
     * Buat sintaks sql untuk spatial index.
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
     * Buat sintaks sql untuk rename kolom.
     *
     * @param Table $table
     * @param Magic $command
     *
     * @return string
     */
    public function rename_column(Table $table, Magic $command)
    {
        return 'ALTER TABLE ' . $this->wrap($table) . ' RENAME COLUMN ' . $this->wrap($command->from) . ' TO ' . $this->wrap($command->to);
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
        $columns = implode(', ', array_map(function ($column) {
            return 'DROP COLUMN ' . $column;
        }, array_map([$this, 'wrap'], $command->columns)));

        return 'ALTER TABLE ' . $this->wrap($table) . ' ' . $columns;
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
        return $this->drop_key($table, $command);
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
        return $this->drop_key($table, $command);
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
        return $this->drop_key($table, $command);
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
        return 'ALTER TABLE ' . $this->wrap($table) . ' DROP FOREIGN KEY ' . $command->name;
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
        return 'VARCHAR(' . $column->length . ')';
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
        return 'DOUBLE';
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
        return 'MEDIUMINT';
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
        return 'TINYINT';
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
        return 'SMALLINT';
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
        return 'JSON';
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
        return 'JSON';
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
        return 'CHAR(36)';
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
        return 'VARCHAR(45)';
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
        return 'VARCHAR(17)';
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
        return 'GEOMETRY';
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
        return 'POINT';
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
        return 'LINESTRING';
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
        return 'POLYGON';
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
        return 'GEOMETRYCOLLECTION';
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
        return 'MULTIPOINT';
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
        return 'MULTILINESTRING';
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
        return 'MULTIPOLYGON';
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

        return sprintf('SET(%s)', $allowed);
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
        return 'INT';
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
        return 'BIGINT';
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
        return 'DECIMAL(' . $column->precision . ', ' . $column->scale . ')';
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

        return sprintf('ENUM(%s)', $allowed);
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
        return 'TINYINT(1)';
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
        return 'TIMESTAMP';
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
        return 'LONGTEXT';
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
}
