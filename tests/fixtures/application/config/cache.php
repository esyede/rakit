<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Driver Cache
    |--------------------------------------------------------------------------
    |
    | Berbagai driver bawaan telah tersedia untuk anda. Beberapa, seperti APC,
    | sangat cepat. Namun, jika itu bukan pilihan untuk anda, coba driver
    | lain seperti file atau database.
    |
    | Driver bawaan: 'file', 'memcached', 'apc', 'redis', 'database'.
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | Key ini akan ditambahkan ke key item yang disimpan menggunakan Memcached
    | dan APC untuk mencegah kesamaan nama key dengan aplikasi lain di server.
    |
    | Karena penyimpanan berbasis memori dapat digunakan bersama oleh
    | aplikasi lain, kami harus bersikap sopan dan menggunakan awalan untuk
    | mengidentifikasi item kami secara unik.
    |
    */

    'key' => 'rakit',

    /*
    |--------------------------------------------------------------------------
    | Cache Database
    |--------------------------------------------------------------------------
    |
    | Ketika anda memilih "database" sebagai cache driver, tabel database ini
    | akan digunakan untuk menyimpan item cache. Jika mau, anda juga boleh
    | menambahkan opsi "connection" untuk menentukan koneksi database mana
    | yang harus digunakan.
    |
    */

    'database' => ['table' => 'rakit_cache'],

    /*
    |--------------------------------------------------------------------------
    | Server Memcached
    |--------------------------------------------------------------------------
    |
    | Server memcached yang digunakan oleh aplikasi anda. Memcached adalah
    | sistem cache berbasis memori terdistribusi yang gratis, open source dan
    | berperforma tinggi. Ini bersifat umum tetapi dimaksudkan untuk digunakan
    | dalam mempercepat aplikasi web dengan mengurangi beban database.
    |
    | Untuk informasi lebih lanjut, lihat: https://memcached.org
    |
    */

    'memcached' => [
        ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
    ],
];
