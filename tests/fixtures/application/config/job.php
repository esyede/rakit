<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    | Driver default yang digunakan untuk menyimpan dan mengelola jobs.
    | Pilihan: file, database, redis, memcached
    |
    */

    'driver' => 'file',

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

    'logging' => false,

    /*
    |--------------------------------------------------------------------------
    | Job Key
    |--------------------------------------------------------------------------
    | Key prefix yang akan ditambahkan ke key item yang disimpan menggunakan
    | Redis atau Memcached untuk mencegah kesamaan nama key dengan aplikasi lain di server.
    |
    */

    'key' => 'rakit.job',
];
