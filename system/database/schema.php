<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use System\Magic;
use System\Database as DB;

class Schema
{
    /**
     * Start the schema builder for a table.
     *
     * @param string      $table
     * @param \Closure    $builder
     * @param string|null $connection
     */
    public static function table($table, \Closure $builder, $connection = null)
    {
        $table = new Schema\Table($table);
        $table->connection($connection);

        call_user_func($builder, $table);

        return static::execute($table);
    }

    /**
     * List all tables in the current database.
     *
     * @param string $connection
     *
     * @return array
     */
    public static function tables($connection = null)
    {
        $connection = DB::connection($connection);
        $driver = $connection->driver();
        $database = static::quote($connection, static::option($connection, 'database'));

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT table_name FROM information_schema.tables'
                    ." WHERE table_type='BASE TABLE' AND table_schema=".$database
                    ." AND table_schema NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')";
                break;

            case 'pgsql':
                $query = 'SELECT table_name FROM information_schema.tables'
                    ." WHERE table_schema='public' AND table_type='BASE TABLE'";
                break;

            case 'sqlite':
                $query = 'SELECT name FROM sqlite_master '
                    ."WHERE type IN ('table','view') AND name NOT LIKE 'sqlite_%' "
                    .'UNION ALL SELECT name FROM sqlite_temp_master '
                    ."WHERE type IN ('table','view') ORDER BY 1";
                break;

            case 'sqlsrv':
                $query = 'SELECT table_name FROM information_schema.tables'
                    ." WHERE table_type='BASE TABLE' AND table_catalog=".$database
                    ." AND table_name <> 'sysdiagrams'";
                break;

            default:
                throw new \Exception(sprintf('Unsupported schema operations for selected driver: %s', $driver));
                break;
        }

        $statement = $connection->pdo()->prepare($query);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * List all columns of a table.
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
        $database = static::quote($connection, static::option($connection, 'database'));
        $table = static::quote($connection, static::prefixed($connection, $table));

        $query = '';

        switch ($driver) {
            case 'mysql':
                $query = 'SELECT column_name FROM information_schema.columns '
                    .'WHERE table_schema='.$database.' AND table_name='.$table;
                break;

            case 'pgsql':
                $query = 'SELECT column_name FROM information_schema.columns '
                    .'WHERE table_schema='.$database.' AND table_name='.$table;
                break;

            case 'sqlite':
                $query = 'PRAGMA table_info('.str_replace('.', '__', $table).')';
                break;

            case 'sqlsrv':
                $query = 'SELECT column_name FROM information_schema.columns '
                    .'WHERE table_schema=N'.$database.' AND table_name=N'.$table;
                break;

            default:
                throw new \Exception(sprintf('Unsupported schema operations for selected driver: %s', $driver));
                break;
        }

        $statement = $connection->pdo()->prepare($query);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN, ($driver === 'sqlite') ? 1 : 0);
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return bool
     */
    public static function has_table($table, $connection = null)
    {
        $table = static::prefixed(DB::connection($connection), $table);

        return in_array($table, static::tables($connection));
    }

    /**
     * Check if a column exists in a table.
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
     * Enable foreign key constraint checking.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return bool
     */
    public static function enable_fk_checks($table, $connection = null)
    {
        $connection = DB::connection($connection);
        $driver = $connection->driver();
        $table = static::quote($connection, static::prefixed($connection, $table));

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
                $query = 'EXEC sp_msforeachtable @command1="print \''.$table.'\'",'
                    .' @command2="ALTER TABLE '.$table.' WITH CHECK CHECK CONSTRAINT all";';
                break;

            default:
                throw new \Exception(sprintf('Unsupported schema operations for selected driver: %s', $driver));
                break;
        }

        try {
            return false !== $connection->pdo()->exec($query);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Disable foreign key constraint checking.
     *
     * @param string      $table
     * @param string|null $connection
     *
     * @return bool
     */
    public static function disable_fk_checks($table, $connection = null)
    {
        $connection = DB::connection($connection);
        $driver = $connection->driver();
        $table = static::quote($connection, static::prefixed($connection, $table));

        switch ($driver) {
            case 'mysql':  $query = 'SET FOREIGN_KEY_CHECKS=0;';
                break;
            case 'pgsql':  $query = 'SET CONSTRAINTS ALL DEFERRED;';
                break;
            case 'sqlite': $query = 'PRAGMA foreign_keys = OFF;';
                break;
            case 'sqlsrv': $query = 'EXEC sp_msforeachtable "ALTER TABLE '.$table.' NOCHECK CONSTRAINT all";';
                break;
            default:       throw new \Exception(sprintf('Unsupported schema operations for selected driver: %s', $driver));
        }

        try {
            return false !== $connection->pdo()->exec($query);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Create a new table schema.
     *
     * @param string      $table
     * @param \Closure    $builder
     * @param string|null $connection
     */
    public static function create($table, \Closure $builder, $connection = null)
    {
        $table = new Schema\Table($table);
        $table->connection($connection);
        $table->create();

        call_user_func($builder, $table);

        return static::execute($table);
    }

    /**
     * Create a new table schema if it does not exist.
     *
     * @param string      $table
     * @param \Closure    $builder
     * @param string|null $connection
     */
    public static function create_if_not_exists($table, \Closure $builder, $connection = null)
    {
        if (! static::has_table($table, $connection)) {
            static::create($table, $builder, $connection);
        }
    }

    /**
     * Rename a table in the schema.
     *
     * @param string      $table
     * @param string      $new_name
     * @param string|null $connection
     */
    public static function rename($table, $new_name, $connection = null)
    {
        $table = new Schema\Table($table);
        $table->connection($connection);
        $table->rename($new_name);

        return static::execute($table);
    }

    /**
     * Delete a table from the schema.
     *
     * @param string      $table
     * @param string|null $connection
     */
    public static function drop($table, $connection = null)
    {
        $table = new Schema\Table($table);
        $table->connection($connection);
        $table->drop();

        return static::execute($table);
    }

    /**
     * Drop a table from the schema if it exists.
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
     * Execute the schema operations for a table.
     *
     * @param Schema\Table $table
     */
    public static function execute($table)
    {
        static::implications($table);

        foreach ($table->commands as $command) {
            $connection = DB::connection($table->connection);
            $grammar = static::grammar($connection);

            if (! method_exists($grammar, $command->type)) {
                throw new \Exception(sprintf(
                    'Unsupported schema command for the %s driver: %s',
                    $connection->driver(),
                    $command->type
                ));
            }

            $statements = (array) $grammar->{$command->type}($table, $command);

            foreach ($statements as $statement) {
                $connection->query($statement);
            }
        }
    }

    /**
     * Add an implicit command to the table if necessary.
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
     * Prepend the table prefix of a connection to a table name.
     *
     * @param Connection $connection
     * @param string     $table
     *
     * @return string
     */
    protected static function prefixed(Connection $connection, $table)
    {
        return static::option($connection, 'prefix').$table;
    }

    /**
     * Read an option from the configuration of a connection.
     *
     * @param Connection $connection
     * @param string     $key
     *
     * @return string
     */
    protected static function option(Connection $connection, $key)
    {
        return isset($connection->config[$key]) ? (string) $connection->config[$key] : '';
    }

    /**
     * Quote a value using the connection it is meant for.
     *
     * @param Connection $connection
     * @param string     $value
     *
     * @return string
     */
    protected static function quote(Connection $connection, $value)
    {
        return $connection->pdo()->quote($value);
    }

    /**
     * Get a schema grammar instance for the connection.
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
            return is_string($resolver) ? new $resolver($connection) : $resolver($connection);
        }

        switch ($driver) {
            case 'mysql':  return new Schema\Grammars\MySQL($connection);
            case 'pgsql':  return new Schema\Grammars\Postgres($connection);
            case 'sqlsrv': return new Schema\Grammars\SQLServer($connection);
            case 'sqlite': return new Schema\Grammars\SQLite($connection);
            default:       throw new \Exception(sprintf('Unsupported schema operations for selected driver: %s', $driver));
        }
    }
}
