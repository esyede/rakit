<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Driver
    |--------------------------------------------------------------------------
    |
    | Rakit uses a flexible driver-based authentication system.
    | You can register your own driver via Auth::extended().
    |
    | Of course, some built-in drivers are also provided to make basic
    | authentication easy and straightforward.
    |
    | Built-in drivers: 'magic', 'facile'.
    |
    */

    'driver' => 'magic',

    /*
    |--------------------------------------------------------------------------
    | Auth Identifier
    |--------------------------------------------------------------------------
    |
    | Here, you can specify the database column that should be considered
    | as "username" for your application users. Typically, this can be
    | a "username" or "email".
    | Of course, you can change it according to your needs.
    |
    */

    'identifier' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Auth Table
    |--------------------------------------------------------------------------
    |
    | When you choose "magic" as the auth driver, the database table that
    | should be used to load users can be specified here. This table will
    | be used by magic query builder to authenticate users.
    |
    */

    'table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Auth Model
    |--------------------------------------------------------------------------
    |
    | When you choose "facile" as the auth driver, you can specify the model
    | that should be used as the "User" model. This model will be used for
    | authentication purposes.
    |
    */

    'model' => 'User',
];
