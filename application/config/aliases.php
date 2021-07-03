<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Alias
    |--------------------------------------------------------------------------
    |
    | Di sini, anda dapat menentukan alias kelas apa pun yang ingin anda
    | daftarkan saat framework dimuat. Alias dimuat dengan teknik lazy-loading,
    | jadi silakan menambahkan sebanyak yang anda mau!
    |
    | Alias membuat kita lebih nyaman saat menggunakan kelas yang ber-namespace.
    | Daripada merujuk ke kelas menggunakan namespace lengkapnya, anda dapat
    | menggunakan alias yang ditentukan di sini.
    |
    */

    'Arr' => 'System\Arr',
    'Auth' => 'System\Auth',
    'Authenticator' => 'System\Auth\Drivers\Driver',
    'Autoloader' => 'System\Autoloader',
    'Blade' => 'System\Blade',
    'Cache' => 'System\Cache',
    'Command' => 'System\Console\Commands\Command',
    'Config' => 'System\Config',
    'Console' => 'System\Console\Console',
    'Container' => 'System\Container',
    'Controller' => 'System\Routing\Controller',
    'Cookie' => 'System\Cookie',
    'Crypter' => 'System\Crypter',
    'Curl' => 'System\Curl',
    'Date' => 'System\Date',
    'DB' => 'System\Database',
    'Email' => 'System\Email',
    'Event' => 'System\Event',
    'Facile' => 'System\Database\Facile\Model',
    'Faker' => 'System\Foundation\Faker\Factory',
    'Hash' => 'System\Hash',
    'Image' => 'System\Image',
    'Input' => 'System\Input',
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
    'Schema' => 'System\Database\Schema',
    'Section' => 'System\Section',
    'Session' => 'System\Session',
    'Storage' => 'System\Storage',
    'Str' => 'System\Str',
    'URI' => 'System\URI',
    'URL' => 'System\URL',
    'Validator' => 'System\Validator',
    'View' => 'System\View',

    // ..
];
