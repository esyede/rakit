<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Database
{
    /**
     * Berisi koneksi database yang masih terhubung.
     *
     * @var array
     */
    public static $connections = [];

    /**
     * Berisi registrar driver pihak ketiga.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Ambil koneksi database.
     * Jika tidak ada nama yang disebutkan, akan mereturn koneksi default.
     *
     * <code>
     *
     *      // Ambil koneksi default
     *      $connection = DB::connection();
     *
     *      // Ambil koneksi berdasarkan nama
     *      $connection = DB::connection('mysql');
     *
     * </code>
     *
     * @param string $connection
     *
     * @return Connection
     */
    public static function connection($connection = null)
    {
        if (is_null($connection)) {
            $connection = Config::get('database.default');
        }

        if (! isset(static::$connections[$connection])) {
            $config = Config::get('database.connections.'.$connection);

            if (is_null($config)) {
                throw new \Exception(sprintf('Database connection is not defined for: %s', $connection));
            }

            static::$connections[$connection] = new Database\Connection(static::connect($config), $config);
        }

        return static::$connections[$connection];
    }

    /**
     * Ambil koneksi PDO dari konfigurasi database yang diberikan.
     *
     * @param array $config
     *
     * @return \PDO
     */
    protected static function connect($config)
    {
        return static::connector($config['driver'])->connect($config);
    }

    /**
     * Buat instance database connector baru.
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
            case 'sqlite': return new Database\Connectors\SQLite();
            case 'mysql':  return new Database\Connectors\MySQL();
            case 'pgsql':  return new Database\Connectors\Postgres();
            case 'sqlsrv': return new Database\Connectors\SQLServer();
            default:       throw new \Exception(sprintf('Unsupported database driver: %s', $driver));
        }
    }

    /**
     * Mulai magic query terhadap tabel.
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
     * Buat instance database expression baru.
     * Database expression digunakan untuk inject SQL mentah ke magic query.
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
     * Escape (quote) string query sebelum digunakan.
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
     * Ambil profiling data untuk semua query.
     *
     * @return array
     */
    public static function profile()
    {
        return Database\Connection::$queries;
    }

    /**
     * Ambil query terakhir yang dijalankan.
     * Mereturn FALSE jika belum ada query yang dijalankan.
     *
     * @return string
     */
    public static function last_query()
    {
        return end(Database\Connection::$queries);
    }

    /**
     * Daftarkan database connector dan grammar.
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
     * Magic method untuk memanggil method milik koneksi databse default.
     *
     * <code>
     *
     *      // Ambil nama driver milik koneksi default
     *      $driver = DB::driver();
     *
     *      // Eksekusi magic query via koneksi databse default
     *      $users = DB::table('users')->get();
     *
     * </code>
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::connection(), $method], $parameters);
    }
}
