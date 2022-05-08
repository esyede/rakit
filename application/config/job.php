<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Enable
    |--------------------------------------------------------------------------
    | Aktifkan / nonaktifkan job
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    | Nama tabel job
    |
    */

    'table' => 'jobs',

    /*
    |--------------------------------------------------------------------------
    | CLI Only
    |--------------------------------------------------------------------------
    | Hanya izinkan job dijalankan dari CLI, FALSE untuk menjalankan via web.
    | Pastikan route diproteksi jika ingin dijalankan via web.
    |
    */

    'cli_only' => true,

    /*
    |--------------------------------------------------------------------------
    | Max Job
    |--------------------------------------------------------------------------
    | Batas maksimum baris job di database yang akan dieksekusi setiap kali
    | job dijalankan.
    |
    */

    'max_job' => 50,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Log setiap ekseskusi job ke file log rakit.
    |
    */

    'logging' => true,
];
