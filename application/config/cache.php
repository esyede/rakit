<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | Various cache drivers are available for you. Some, like APC,
    | are very fast. However, if that is not an option for you, try the file
    | or database driver.
    |
    | Available: 'file', 'memcached', 'apc', 'redis', 'database'.
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | This key will be added to the key item that is stored using Memcached
    | and APC to prevent key name collisions with other applications on the
    | same server because memory-based storage can be shared by other apps,
    | we must be polite and use a prefix to identify our items uniquely.
    |
    */

    'key' => 'rakit',

    /*
    |--------------------------------------------------------------------------
    | Cache Database
    |--------------------------------------------------------------------------
    |
    | When you select "database" as the cache driver, this table will be used
    | to store cache items. If you want, you can also add the "connection"
    | option to specify which database connection should be used.
    |
    */

    'database' => ['table' => 'caches'],

    /*
    |--------------------------------------------------------------------------
    | Memcached Server
    |----------------------------------------------------------------------
    |
    | The Memcached server used by your application. Memcached is a free,
    | open-source, and high-performance distributed memory-based caching system.
    | It is general-purpose but is intended for use in speeding up
    | web applications by reducing database load.
    |
    | For more information, see: https://memcached.org
    |
    */

    'memcached' => [
        ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
    ],
];
