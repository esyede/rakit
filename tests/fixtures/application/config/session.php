<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Session Driver
    |--------------------------------------------------------------------------
    |
    | Name of the session driver to be used by your application. Since HTTP
    | is stateless, this session is used to simulate "state" across all requests
    | sent by the user. In other words, this is how the application knows who
    | you really are.
    |
    | Available drivers: 'cookie', 'file', 'database', 'memcached', 'apc', 'redis'.
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Session Table
    |--------------------------------------------------------------------------
    |
    | Table where session data should be stored. This option is only applicable
    | when you choose "database" as your session driver. The table definition
    | should match with the one included in the database migration.
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | The number of minutes the session should be valid.
    |
    */

    'lifetime' => 60,

    /*
    |--------------------------------------------------------------------------
    | Expire On Close
    |--------------------------------------------------------------------------
    |
    | Do session expire when the browser is closed?
    |
    */

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Name that should be given to the session cookie.
    |
    */

    'cookie' => 'rakit_session',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | Path where the session cookie is available.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Domain where the session cookie is available.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Secure
    |--------------------------------------------------------------------------
    |
    | Determine whether the session cookie should be secure.
    |
    */

    'secure' => false,

    /*
    |--------------------------------------------------------------------------
    | Same-site Cookie
    |--------------------------------------------------------------------------
    |
    | The SameSite attribute of the session cookie. Set to 'Lax' or 'Strict'
    | for default behavior, or 'None' if you want to allow cross-site requests.
    |
    | Available options: 'Lax', 'Strict', 'None'.
    |
    */

    'samesite' => 'Lax',

    /*
    |--------------------------------------------------------------------------
    | Session ID Length
    |--------------------------------------------------------------------------
    |
    | Length of session ID in characters. Default 32 for PHP 7.1+.
    | Leave null to use default PHP.
    |
    */

    'sid_length' => null,

    /*
    |--------------------------------------------------------------------------
    | Session Serialize Handler
    |--------------------------------------------------------------------------
    |
    | Serialization handler for session data. Default to 'php'
    |
    | Available options: 'php', 'php_serialize', 'php_binary'.
    |
    | php: Default PHP serializer using serialize()/unserialize()
    | php_serialize: Improved serialize(), better for objects and security,
    |                Requires PHP 5.5.4 or newer.
    | php_binary: Binary serialization, more compact and faster,
    |             but not human-readable.
    |
    */

    'serialize' => 'php',
];
