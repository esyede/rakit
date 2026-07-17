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

    'activate' => true,

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

    'database' => true,

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
    | the dd(), bd(), and dump() helper? Zero (0) will display everything.
    |
    */

    'depth' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Maximum Length
    |--------------------------------------------------------------------------
    |
    | How many characters should be displayed when calling
    | the dd(), bd(), and dump() helper? Zero (0) will display everything.
    |
    */

    'length' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Show Location
    |--------------------------------------------------------------------------
    |
    | Should the file location also be displayed when calling
    | the dd(), bd(), and dump() helper?
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

    /*
    |--------------------------------------------------------------------------
    | Editor
    |--------------------------------------------------------------------------
    |
    | The editor that opens when you click a file:line reference in the debug
    | bar or on the error page. Use a preset name (phpstorm, idea, vscode,
    | vscode-insiders, sublime, textmate, atom, macvim, emacs, netbeans) or a
    | custom URL template containing the %file% and %line% placeholders, e.g.
    | 'vscode://file/%file%:%line%'. Set to null to disable clickable links.
    |
    */

    'editor' => 'phpstorm',

    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | Toggle individual debug bar panels on or off. Set any entry to false to
    | stop that collector from gathering data and hide its tab (useful to cut
    | overhead during heavy operations). Omitted keys default to enabled.
    |
    */

    'collectors' => [
        'messages' => true,
        'exceptions' => true,
        'deprecations' => true,
        'timeline' => true,
        'queries' => true,
        'views' => true,
        'routes' => true,
        'http' => true,
        'mails' => true,
        'session' => true,
        'auth' => true,
        'request' => true,
        'cache' => true,
        'events' => true,
        'config' => true,
        'errors' => true,
    ],
];
