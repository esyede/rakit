<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Enable
    |--------------------------------------------------------------------------
    | Aktifkan / nonaktifkan jobs.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Jobs Table
    |--------------------------------------------------------------------------
    | Nama tabel jobs.
    |
    */

    'table' => 'jobs',

    /*
    |--------------------------------------------------------------------------
    | Failed Jobs Table
    |--------------------------------------------------------------------------
    | Nama tabel failed jobs (untuk mencatat job yang gagal dijalankan)
    |
    */

    'failed_table' => 'failed_jobs',

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
