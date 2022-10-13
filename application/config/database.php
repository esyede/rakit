<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | Secara default, record hasil dari kueri database akan direturn sebagai
    | instance dari objek stdClass; namun, anda mungkin ingin mengambil record
    | tersebut sebagai array, bukan sebagai objek. Di sini anda dapat
    | mengontrol gaya pengambilan record PDO dari hasil kueri anda.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Koneksi Database Default
    |--------------------------------------------------------------------------
    |
    | Nama koneksi database default anda. Koneksi ini akan digunakan sebagai
    | default untuk semua operasi database kecuali anda memberikan
    | nama koneksi lain saat melakukan operasi tersebut.
    |
    | Nama koneksi ini harus terdaftar dalam list koneksi database di bawah.
    |
    */

    'default' => 'sqlite',

    /*
    |--------------------------------------------------------------------------
    | List Koneksi Database
    |--------------------------------------------------------------------------
    |
    | List koneksi database yang digunakan oleh aplikasi anda. Biasanya,
    | aplikasi anda hanya akan menggunakan satu koneksi; namun, anda memiliki
    | kebebasan untuk menentukan berapapun koneksi yang ingin anda tangani.
    |
    | Seluruh pengelolaan database di rakit dilakukan melalui fasilitas PDO,
    | jadi pastikan driver PDO untuk database pilihan anda sudah diinstal.
    |
    |
    */

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => 'application',
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => '5432',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => '127.0.0.1',
            'port' => '1433',
            'database' => 'database',
            'username' => 'root',
            'password' => '',
            'prefix' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Redis
    |--------------------------------------------------------------------------
    |
    | Redis adalah penyimpanan berbasis key-value yang cepat dan canggih.
    | Dia juga menyediakan sekumpulan perintah yang lebih kaya daripada
    | penyimpanan berbasis key-value biasa seperti APC atau memcache.
    | Banyak orang menyukainya.
    |
    | Untuk mengetahui informasi tentang Redis, lihat: https://redis.io
    |
    */

    'redis' => [
        'default' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
        ],
    ],
];
