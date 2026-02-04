<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Magic;
use System\Database as DB;
use System\Str;

class Schema
{
    /**
     * Mulai operasi schema terhadap tabel.
     *
     * @param string   $table
     * @param \Closure $builder
     */
    public static function table($table, \Closure $builder)
    {
        $table = new Schema\Table($table);

        call_user_func($builder, $table);

        return static::execute($table);
    }

    /**
     * List semua tabel di database saat ini.
     *
     * @param string $connection
     *
     * @return array
     */
    public static function tables($connection = null)
    {
        $connection = DB::connection($connection);
        $driver = $connection->driver();
        $database = Config::get('database.connections.' . $driver . '.database');
        $database = DB::escape($database);

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT table_name FROM information_schema.tables' .
                    " WHERE table_type='BASE TABLE' AND table_schema=" . $database .
                    " AND table_schema NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')";
                break;

            case 'pgsql':
                $query = 'SELECT table_name FROM information_schema.tables' .
                    " WHERE table_schema='public' AND table_type='BASE TABLE'";
                break;

            case 'sqlite':
                $query = "SELECT name FROM sqlite_master " .
                    "WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' " .
                    "UNION ALL SELECT name FROM sqlite_temp_master " .
                    "WHERE type IN ('table','view') ORDER BY 1";
                break;

            case 'sqlsrv':
                $query = 'SELECT table_name FROM information_schema.tables' .
                    " WHERE table_type='BASE TABLE' AND table_catalog=" . $database .
                    " AND table_name <> 'sysdiagrams'";
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s',
                    $driver
                ));
                break;
        }

        $statement = $connection->pdo()->prepare($query);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * List seluruh kolom milik suatu tabel saat ini.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return array
     */
    public static function columns($table, $connection = null)
    {
        $connection = DB::connection($connection);
        $driver = $connection->driver();
        $database = Config::get('database.connections.' . $driver . '.database');
        $database = DB::escape($database);
        $table = DB::escape($table);

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT column_name FROM information_schema.columns ' .
                    'WHERE table_schema=' . $database . ' AND table_name=' . $table;
                break;

            case 'pgsql':
                $query = 'SELECT column_name FROM information_schema.columns ' .
                    'WHERE table_schema=' . $database . ' AND table_name=' . $table;
                break;

            case 'sqlite':
                $query = 'PRAGMA table_info(' . str_replace('.', '__', $table) . ')';
                break;

            case 'sqlsrv':
                $query = 'SELECT column_name FROM information_schema.columns ' .
                    'WHERE table_schema=N' . $database . ' AND table_name=N' . $table;
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s',
                    $driver
                ));
                break;
        }

        $statement = $connection->pdo()->prepare($query);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN, ($driver === 'sqlite') ? 1 : 0);
    }

    /**
     * Cek apakah tabel ada di database saat ini.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return bool
     */
    public static function has_table($table, $connection = null)
    {
        return in_array($table, static::tables($connection));
    }

    /**
     * Cek apakah kolom ada di suatu tabel.
     *
     * @param string      $table
     * @param string      $column
     * @param string|null $connection
     *
     * @return bool
     */
    public static function has_column($table, $column, $connection = null)
    {
        return in_array($column, static::columns($table, $connection));
    }

    /**
     * Hidupkan foreign key constraint checking.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return bool
     */
    public static function enable_fk_checks($table, $connection = null)
    {
        $table = DB::escape($table);
        $connection = DB::connection($connection);
        $driver = $connection->driver();

        switch ($driver) {
            case 'mysql':
                $query = 'SET FOREIGN_KEY_CHECKS=1;';
                break;

            case 'pgsql':
                $query = 'SET CONSTRAINTS ALL IMMEDIATE;';
                break;

            case 'sqlite':
                $query = 'PRAGMA foreign_keys = ON;';
                break;

            case 'sqlsrv':
                $query = 'EXEC sp_msforeachtable @command1="print \'' . $table . '\'", ' .
                    '@command2="ALTER TABLE ' . $table . ' WITH CHECK CHECK CONSTRAINT all";';
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s',
                    $driver
                ));
                break;
        }

        try {
            return false !== $connection->pdo()->exec($query);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Matikan foreign key constraint checking.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return bool
     */
    public static function disable_fk_checks($table, $connection = null)
    {
        $table = DB::escape($table);
        $connection = DB::connection($connection);
        $driver = $connection->driver();

        switch ($driver) {
            case 'mysql':
                $query = 'SET FOREIGN_KEY_CHECKS=0;';
                break;

            case 'pgsql':
                $query = 'SET CONSTRAINTS ALL DEFERRED;';
                break;

            case 'sqlite':
                $query = 'PRAGMA foreign_keys = OFF;';
                break;

            case 'sqlsrv':
                $query = 'EXEC sp_msforeachtable "ALTER TABLE ' . $table . ' NOCHECK CONSTRAINT all";';
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s',
                    $driver
                ));
                break;
        }

        try {
            return false !== $connection->pdo()->exec($query);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Buat skema tabel baru.
     *
     * @param string   $table
     * @param \Closure $builder
     */
    public static function create($table, \Closure $builder)
    {
        $table = new Schema\Table($table);
        $table->create();

        call_user_func($builder, $table);

        return static::execute($table);
    }

    /**
     * Buat skema tabel baru jika tabel belum ada.
     *
     * @param string   $table
     * @param \Closure $builder
     */
    public static function create_if_not_exists($table, \Closure $builder)
    {
        if (!static::has_table($table)) {
            static::create($table, $builder);
        }
    }

    /**
     * Ganti nama tabel.
     *
     * @param string $table
     * @param string $new_name
     */
    public static function rename($table, $new_name)
    {
        $table = new Schema\Table($table);
        $table->rename($new_name);

        return static::execute($table);
    }

    /**
     * Hapus tabel dari skema.
     *
     * @param string $table
     * @param string $connection
     */
    public static function drop($table, $connection = null)
    {
        $table = new Schema\Table($table);
        $table->on($connection);
        $table->drop();

        return static::execute($table);
    }

    /**
     * Hapus tabel dari skema (hanya jika tabelnya ada).
     *
     * @param string $table
     * @param string $connection
     */
    public static function drop_if_exists($table, $connection = null)
    {
        if (static::has_table($table, $connection)) {
            static::drop($table, $connection);
        }
    }

    /**
     * Jalankan operasi skema terhadap database.
     *
     * @param Schema\Table $table
     */
    public static function execute($table)
    {
        static::implications($table);

        foreach ($table->commands as $command) {
            $connection = DB::connection($table->connection);
            $grammar = static::grammar($connection);

            if (method_exists($grammar, $command->type)) {
                $statements = (array) $grammar->{$command->type}($table, $command);

                foreach ($statements as $statement) {
                    $connection->query($statement);
                }
            }
        }
    }

    /**
     * Tambahkan perintah implisit apapun ke operasi skema.
     *
     * @param Schema\Table $table
     */
    protected static function implications($table)
    {
        if (count($table->columns) > 0 && !$table->creating()) {
            $command = new Magic(['type' => 'add']);
            array_unshift($table->commands, $command);
        }

        $indexes = ['primary', 'unique', 'fulltext', 'index'];

        foreach ($table->columns as $column) {
            foreach ($indexes as $index) {
                if (isset($column->{$index})) {
                    if (true === $column->{$index}) {
                        $table->{$index}($column->name);
                    } else {
                        $table->{$index}($column->name, $column->{$index});
                    }
                }
            }
        }
    }

    /**
     * Mereturn query grammar yang sesuai untuk driver database saat ini.
     *
     * @param \System\Database\Connection $connection
     *
     * @return Grammar
     */
    public static function grammar(Connection $connection)
    {
        $driver = $connection->driver();

        if (isset(DB::$registrar[$driver]['schema'])) {
            $resolver = DB::$registrar[$driver]['schema'];
            return $resolver();
        }

        switch ($driver) {
            case 'mysql':
                return new Schema\Grammars\MySQL($connection);

            case 'pgsql':
                return new Schema\Grammars\Postgres($connection);

            case 'sqlsrv':
                return new Schema\Grammars\SQLServer($connection);

            case 'sqlite':
                return new Schema\Grammars\SQLite($connection);

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s',
                    $driver
                ));
        }
    }
}
