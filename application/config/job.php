<?php

defined('DS') or exit('No direct access.');

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

    'table' => 'rakit_jobs',

    /*
    |--------------------------------------------------------------------------
    | Failed Jobs Table
    |--------------------------------------------------------------------------
    | Nama tabel failed jobs (untuk mencatat job yang gagal dijalankan)
    |
    */

    'failed_table' => 'rakit_failed_jobs',

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
    | Max Retries
    |--------------------------------------------------------------------------
    | Batas maksimum sebuah job diulang ekseskusinya sampai dianggap gagal dan
    | dimasukkan ke tabel failed jobs.
    |
    */

    'max_retries' => 1,

    /*
    |--------------------------------------------------------------------------
    | Sleep
    |--------------------------------------------------------------------------
    | Jeda antar setiap percobaan pengulangan ekseskusi job (dalam milidetik).
    |
    */

    'sleep_ms' => 0,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Log setiap ekseskusi job ke file log rakit.
    |
    */

    'logging' => true,
];
