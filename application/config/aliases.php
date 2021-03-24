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
    'Asset' => 'System\Asset',
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
    'Event' => 'System\Event',
    'Facile' => 'System\Database\Facile\Model',
    'File' => 'System\File',
    'Faker' => 'System\Foundation\Faker\Factory',
    'Form' => 'System\Form',
    'Hash' => 'System\Hash',
    'HTML' => 'System\HTML',
    'Image' => 'System\Image',
    'Input' => 'System\Input',
    'Lang' => 'System\Lang',
    'Log' => 'System\Log',
    'Mailer' => 'System\Mailer',
    'Markdown' => 'System\Markdown',
    'Memcached' => 'System\Memcached',
    'Middleware' => 'System\Routing\Middleware',
    'Package' => 'System\Package',
    'Paginator' => 'System\Paginator',
    'URL' => 'System\URL',
    'Redirect' => 'System\Redirect',
    'Redis' => 'System\Redis',
    'Request' => 'System\Request',
    'Response' => 'System\Response',
    'Route' => 'System\Routing\Route',
    'Router' => 'System\Routing\Router',
    'Schema' => 'System\Database\Schema',
    'Section' => 'System\Section',
    'Session' => 'System\Session',
    'Str' => 'System\Str',
    'URI' => 'System\URI',
    'Validator' => 'System\Validator',
    'View' => 'System\View',

    // Tambahkan alias lain disini..
];
