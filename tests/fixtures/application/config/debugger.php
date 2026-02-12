<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    |
    | This option controls whether the debugger is activated or not. If this
    | option is enabled, every error that occurs will always be displayed;
    | Disable this option when your application is already on a production server.
    |
    */

    'activate' => false,

    /*
    |--------------------------------------------------------------------------
    | Show Debug Bar
    |--------------------------------------------------------------------------
    |
    | This option controls whether the debug bar is displayed or not. The debug
    | bar is a small taskbar that floats in the bottom right corner of your screen
    | and contains quick debug information about your application.
    |
    */

    'debugbar' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Query Logging
    |--------------------------------------------------------------------------
    |
    | By default, SQL queries, bindings and execution time for each database
    | operation will be logged into an array for easy inspection.
    |
    | The log can be viewed using the DB::profile() method or through the
    | debugbar.
    |
    | However, in some situations you may want to disable this feature, such
    | as when your application is running a heavy database operation.
    |
    */

    'database' => false,

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, any error will stop the execution of your application;
    | otherwise, the application will continue running, but the error will be
    | displayed in the debug bar.
    |
    */

    'strict' => true,

    /*
    |--------------------------------------------------------------------------
    | Scream!
    |--------------------------------------------------------------------------
    |
    | When enabled, the @ operator will be disabled, so notices and warnings
    | will no longer be hidden by PHP.
    |
    */

    'scream' => true,

    /*
    |--------------------------------------------------------------------------
    | Maximum Depth
    |--------------------------------------------------------------------------
    |
    | How deep should arrays and objects be displayed when calling
    | the dd(), bd(), and dump() helper functions?
    |
    */

    'depth' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Length
    |--------------------------------------------------------------------------
    |
    | How many characters should be displayed when calling
    | the dd(), bd(), and dump() helper functions?
    |
    */

    'length' => 10000,

    /*
    |--------------------------------------------------------------------------
    | Show Location
    |--------------------------------------------------------------------------
    |
    | Should the file location also be displayed when calling
    | the dd(), bd(), and dump() helper functions?
    |
    */

    'location' => false,

    /*
    |--------------------------------------------------------------------------
    | Error Email
    |--------------------------------------------------------------------------
    |
    | Fill in your email address if you want to receive error notifications
    | for your application.
    |
    */

    'email' => '',
];
