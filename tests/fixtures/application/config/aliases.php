<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    |
    | Here, you can define any class aliases you want to register when the
    | framework is loaded. Aliases are loaded using lazy-loading technique,
    | so feel free to add as many as you want!
    |
    | Alias makes it more convenient to use classes with namespaces.
    | Instead of referring to classes using their full namespaces, you can use
    | the aliases defined here.
    |
    */

    'Arr' => 'System\Arr',
    'Auth' => 'System\Auth',
    'Authenticator' => 'System\Auth\Drivers\Driver',
    'Autoloader' => 'System\Autoloader',
    'Blade' => 'System\Blade',
    'Cache' => 'System\Cache',
    'Carbon' => 'System\Carbon',
    'Command' => 'System\Console\Commands\Command',
    'Config' => 'System\Config',
    'Console' => 'System\Console\Console',
    'Container' => 'System\Container',
    'Controller' => 'System\Routing\Controller',
    'Cookie' => 'System\Cookie',
    'Crypter' => 'System\Crypter',
    'Curl' => 'System\Curl',
    'DB' => 'System\Database',
    'Email' => 'System\Email',
    'Event' => 'System\Event',
    'Facile' => 'System\Database\Facile\Model',
    'Faker' => 'System\Foundation\Faker\Factory',
    'Hash' => 'System\Hash',
    'Image' => 'System\Image',
    'Input' => 'System\Input',
    'Job' => 'System\Job',
    'Jobable' => 'System\Job\Jobable',
    'JWT' => 'System\JWT',
    'Lang' => 'System\Lang',
    'Log' => 'System\Log',
    'Mailer' => 'System\Email\Drivers\Driver',
    'Markdown' => 'System\Markdown',
    'Memcached' => 'System\Memcached',
    'Middleware' => 'System\Routing\Middleware',
    'Package' => 'System\Package',
    'Paginator' => 'System\Paginator',
    'Redirect' => 'System\Redirect',
    'Redis' => 'System\Redis',
    'Request' => 'System\Request',
    'Response' => 'System\Response',
    'Route' => 'System\Routing\Route',
    'Router' => 'System\Routing\Router',
    'RSA' => 'System\RSA',
    'Schema' => 'System\Database\Schema',
    'Section' => 'System\Section',
    'Session' => 'System\Session',
    'Storage' => 'System\Storage',
    'Str' => 'System\Str',
    'Throttle' => 'System\Routing\Throttle',
    'URI' => 'System\URI',
    'URL' => 'System\URL',
    'Validator' => 'System\Validator',
    'View' => 'System\View',

    // ..
];
