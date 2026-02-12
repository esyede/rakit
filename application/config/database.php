<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database query results will be returned as instances of
    | stdClass objects; however, you may wish to retrieve them as arrays
    | instead. Here you can set the fetch mode for PDO records.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | The default connection name for the application. This connection name will
    | be used by default for all database work except when another connection
    | name is explicitly specified.
    |
    | The default connection name must be listed in the database connections
    | array below.
    |
    */

    'default' => 'sqlite',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Database connections are used to connect to your database. You can use
    | multiple connections if you need to connect to multiple databases.
    |
    | All database management in rakit is done through the PDO facilities,
    | so make sure the PDO driver for your chosen database is installed.
    |
    |
    */

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => 'application',
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '5432',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => '127.0.0.1',
            'port' => '1433',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'prefix' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Database Configuration
    |--------------------------------------------------------------------------
    |
    | Redis is a key-value store that is fast and powerful.
    | It also provides a rich set of commands beyond the basic key-value store.
    | Many people love it.
    |
    | To learn more about Redis, visit: https://redis.io
    |
    */

    'redis' => [
        'default' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
        ],
    ],
];
