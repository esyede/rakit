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
        call_user_func($callback, $table = new Schema\Table($table));
        return static::execute($table);
    }

    /**
     * Cek apakah tabel ada di database.
     *
     * @param string $table
     *
     * @return bool
     */
    public static function has_table($table, $connection = null)
    {
        $driver = DB::connection()->driver();

        if (! is_null($connection) && '' !== trim($connection)) {
            $driver = $connection;
        }

        $database = Config::get('database.connections.'.$driver.'.database');
        $database = DB::escape($database);
        $table = DB::escape($table);

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT * FROM information_schema.tables '.
                    'WHERE table_schema = '.$database.' AND table_name = '.$table;
                break;

            case 'pgsql':
                $query = 'SELECT * FROM information_schema.tables WHERE table_name = '.$table;
                break;

            case 'sqlite':
                $query = "SELECT * FROM sqlite_master WHERE type = 'table' AND name = ".$table;
                break;

            case 'sqlsrv':
                $query = 'SELECT * FROM sysobjects WHERE type = \'U\' AND name = '.$table;
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s', $driver
                ));
                break;
        }

        return null !== DB::first($query);
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
        $driver = DB::connection()->driver();

        $database = Config::get('database.connections.'.$driver.'.database');
        $database = DB::escape($database);
        $table = DB::escape($table);

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT column_name FROM information_schema.columns '.
                    'WHERE table_schema = '.$database.' AND column_name = '.$column;
                break;

            case 'pgsql':
                $query = 'SELECT column_name FROM information_schema.columns '.
                    'WHERE table_name = '.$table.' AND column_name = '.$column;
                break;

            case 'sqlite':
                try {
                    $query = 'PRAGMA table_info('.str_replace('.', '__', $table).')';
                    $statement = DB::connection()->pdo->prepare($query);
                    $statement->execute();

                    // Listing semua kolom di dalam tabel
                    $columns = $statement->fetchAll(\PDO::FETCH_ASSOC);
                    $columns = array_values(array_map(function ($col) {
                        return isset($col['name']) ? $col['name'] : [];
                    }, $columns));

                    return in_array($column, $columns);
                } catch (\PDOException $e) {
                    return false;
                }
                break;

            case 'sqlsrv':
                $query = 'SELECT col.name FROM sys.columns as col '.
                    'JOIN sys.objects AS obj ON col.object_id = obj.object_id '.
                    'WHERE obj.type = \'U\' AND obj.name = '.$table.' AND col.name = '.$column;
                break;

            default:
                throw new \Exception(sprintf(
                    'Unsupported schema operations for selected driver: %s', $driver
                ));
                break;
        }

        return (null !== DB::first($query));
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
            return false !== DB::connection()->pdo->exec($query);
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
            return false !== DB::connection()->pdo->exec($query);
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

            if (method_exists($grammar, $method = $command->type)) {
                $statements = $grammar->{$method}($table, $command);
                $statements = (array) $statements;

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

        foreach ($table->columns as $column) {
            $indexes = ['primary', 'unique', 'fulltext', 'index'];

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
        }

        throw new \Exception(sprintf('Unsupported schema operations for selected driver: %s', $driver));
    }
}
