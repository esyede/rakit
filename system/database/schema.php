<?php

namespace System\Database;

defined('DS') or exit('No direct script access.');

use System\Config;
use System\Magic;
use System\Database as DB;

class Schema
{
    /**
     * Mulai operasi schema terhadap tabel.
     *
     * @param string   $table
     * @param \Closure $callback
     */
    public static function table($table, \Closure $callback)
    {
        $table = new Schema\Table($table);

        call_user_func($callback, $table);

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
        $driver = DB::connection()->driver();

        if (! is_null($connection) && '' !== trim($connection)) {
            $driver = $connection;
        }

        $database = Config::get('database.connections.'.$driver.'.database');
        $database = DB::escape($database);

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = "SELECT table_name FROM information_schema.tables".
                    " WHERE table_type='BASE TABLE' AND table_schema=".$database.
                    " AND table_schema NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')";
                break;

            case 'pgsql':
                $query = "SELECT table_name FROM information_schema.tables".
                    " WHERE table_schema='public' AND table_type='BASE TABLE'";
                break;

            case 'sqlite':
                $query = "SELECT table_name FROM sqlite_master".
                    " WHERE type = 'table' AND table_name NOT LIKE 'sqlite_%'";
                break;

            case 'sqlsrv':
                $query = "SELECT table_name FROM information_schema.tables".
                    " WHERE table_type='BASE TABLE' AND table_catalog=".$database.
                    " AND table_name <> 'sysdiagrams'";
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s', $driver
                ));
                break;
        }

        try {
            $statement = DB::connection()->pdo()->prepare($query);
            $statement->execute();
            return $statement->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * List seluruh kolom milik suatu tabel saat ini.
     *
     * @param string $table
     *
     * @return array
     */
    public static function columns($table)
    {
        $driver = DB::connection()->driver();

        $database = Config::get('database.connections.'.$driver.'.database');
        $database = DB::escape($database);
        $table = DB::escape($table);

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT column_name FROM information_schema.columns '.
                    'WHERE table_schema='.$database.' AND table_name='.$table;
                break;

            case 'pgsql':
                $query = 'SELECT column_name FROM information_schema.columns WHERE table_name='.$table;
                break;

            case 'sqlite':
                $query = 'PRAGMA table_info('.str_replace('.', '__', $table).')';
                break;

            case 'sqlsrv':
                $query = 'SELECT column_name FROM information_schema.columns WHERE table_name=N'.$table;
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s', $driver
                ));
                break;
        }

        try {
            $statement = DB::connection()->pdo()->prepare($query);
            $statement->execute();
            return $statement->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Cek apakah tabel ada di database saat ini.
     *
     * @param string $table
     *
     * @return bool
     */
    public static function has_table($table, $connection = null)
    {
        $tables = static::tables($connection);
        return in_array($table, $tables);
    }

    /**
     * Cek apakah kolom ada di suatu tabel.
     *
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    public static function has_column($table, $column)
    {
        $columns = static::columns($table);
        return in_array($column, $columns);
    }

    /**
     * Hidupkan foreign key constraint checking.
     *
     * @param string $table
     *
     * @return bool
     */
    public static function enable_fk_checks($table)
    {
        $table = DB::escape($table);
        $driver = DB::connection()->driver();

        switch ($driver) {
            case 'mysql':  $query = 'SET FOREIGN_KEY_CHECKS=1;'; break;
            case 'pqsql':  $query = 'SET CONSTRAINTS ALL IMMEDIATE;'; break;
            case 'sqlite': $query = 'PRAGMA foreign_keys = ON;'; break;
            case 'sqlsrv':
                $query = 'EXEC sp_msforeachtable @command1="print \''.$table.'\'", '.
                    '@command2="ALTER TABLE '.$table.' WITH CHECK CHECK CONSTRAINT all";';
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s', $driver
                ));
                break;
        }

        try {
            return false !== DB::connection()->pdo()->exec($query);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Matikan foreign key constraint checking.
     *
     * @param string $table
     *
     * @return bool
     */
    public static function disable_fk_checks($table)
    {
        $table = DB::escape($table);
        $driver = DB::connection()->driver();

        switch ($driver) {
            case 'mysql':  $query = 'SET FOREIGN_KEY_CHECKS=0;'; break;
            case 'pqsql':  $query = 'SET CONSTRAINTS ALL DEFERRED;'; break;
            case 'sqlite': $query = 'PRAGMA foreign_keys = OFF;'; break;
            case 'sqlsrv':
                $query = 'EXEC sp_msforeachtable "ALTER TABLE '.$table.' NOCHECK CONSTRAINT all";';
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s', $driver
                ));
                break;
        }

        try {
            return false !== DB::connection()->pdo()->exec($query);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Buat skema tabel baru.
     *
     * @param string   $table
     * @param \Closure $callback
     */
    public static function create($table, \Closure $callback)
    {
        $table = new Schema\Table($table);
        $table->create();

        call_user_func($callback, $table);

        return static::execute($table);
    }

    /**
     * Buat skema tabel baru jika tabel belum ada.
     *
     * @param string   $table
     * @param \Closure $callback
     */
    public static function create_if_not_exists($table, \Closure $callback)
    {
        if (! static::has_table($table)) {
            static::create($table, $callback);
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
        if (count($table->columns) > 0 && ! $table->creating()) {
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
     * @param Connection $connection
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
            case 'mysql':  return new Schema\Grammars\MySQL($connection);
            case 'pgsql':  return new Schema\Grammars\Postgres($connection);
            case 'sqlsrv': return new Schema\Grammars\SQLServer($connection);
            case 'sqlite': return new Schema\Grammars\SQLite($connection);
            default:       throw new \Exception(sprintf(
                'Unsupported schema operations for selected driver: %s', $driver
            ));
        }
    }
}
