<?php

namespace System;

defined('DS') or exit('No direct access.');

class Database
{
    /**
     * Contains the active database connections.
     *
     * @var array
     */
    public static $connections = [];

    /**
     * Contains the third-party driver registrar.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Gets the database connection.
     * If no name is specified, will return the default connection.
     *
     * <code>
     *
     *      // Get the default connection
     *      $connection = DB::connection();
     *
     *      // Get the connection based on name
     *      $connection = DB::connection('mysql');
     *
     * </code>
     *
     * @param string $connection
     *
     * @return \System\Database\Connection|null
     */
    public static function connection($connection = null)
    {
        if (is_null($connection)) {
            $connection = Config::get('database.default');
        }

        if (!isset(static::$connections[$connection])) {
            $config = Config::get('database.connections.' . $connection);

            if (is_null($config)) {
                throw new \Exception(sprintf('Database connection is not defined for: %s', $connection));
            }

            static::$connections[$connection] = new Database\Connection(static::connect($config), $config);
        }

        return static::$connections[$connection];
    }

    /**
     * Get the database connection.
     *
     * @param array $config
     *
     * @return \PDO
     */
    protected static function connect(array $config)
    {
        return static::connector($config['driver'])->connect($config);
    }

    /**
     * Create a new database connector instance.
     *
     * @param string $driver
     *
     * @return \System\Database\Connectors\Connector
     */
    protected static function connector($driver)
    {
        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver]['connector'];
            return $resolver();
        }

        switch ($driver) {
            case 'sqlite':
                return new Database\Connectors\SQLite();

            case 'mysql':
                return new Database\Connectors\MySQL();

            case 'pgsql':
                return new Database\Connectors\Postgres();

            case 'sqlsrv':
                return new Database\Connectors\SQLServer();

            default:
                throw new \Exception(sprintf('Unsupported database driver: %s', $driver));
        }
    }

    /**
     * Start a magic query against a table.
     *
     * @param string $table
     * @param string $connection
     *
     * @return \System\Database\Query
     */
    public static function table($table, $connection = null)
    {
        return static::connection($connection)->table($table);
    }

    /**
     * Create a new database expression instance.
     * Database expression is used to inject raw SQL into magic query.
     *
     * @param string $value
     *
     * @return Expression
     */
    public static function raw($value)
    {
        return new Database\Expression($value);
    }

    /**
     * Escape the given sql query.
     *
     * @param string $value
     *
     * @return string
     */
    public static function escape($value)
    {
        return static::connection()->pdo()->quote($value);
    }

    /**
     * Get profiling data for all queries.
     *
     * @return array
     */
    public static function profile()
    {
        return Database\Connection::$queries;
    }

    /**
     * Get the last query that was executed.
     * Returns FALSE if no query has been executed.
     *
     * @return string
     */
    public static function last_query()
    {
        return end(Database\Connection::$queries);
    }

    /**
     * Register database connector and grammar.
     *
     * @param string   $name
     * @param \Closure $connector
     * @param \Closure $query
     * @param \Closure $schema
     */
    public static function extend($name, \Closure $connector, $query = null, $schema = null)
    {
        $query = is_null($query) ? '\System\Database\Query\Grammars\Grammar' : $query;
        static::$registrar[$name] = compact('connector', 'query', 'schema');
    }

    /**
     * Magic method for calling methods of the default database connection.
     *
     * <code>
     *
     *      // Get the driver name of the default database connection
     *      $driver = DB::driver();
     *
     *      // Execute magic query via the default database connection
     *      $users = DB::table('users')->get();
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::connection(), $method], $parameters);
    }
}
