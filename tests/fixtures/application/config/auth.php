<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Driver
    |--------------------------------------------------------------------------
    |
    | Rakit menerapkan penanganan otentikasi berbasis driver yang fleksibel.
    | Anda bebas mendaftarkan driver anda sendiri via metode Auth::extended().
    |
    | Tentu saja, beberapa driver bawaan juga sudah disediakan agar
    | otentikasi dasar bisa langsung dilakukan secara sederhana dan mudah.
    |
    | Driver bawaan: 'magic', 'facile'.
    |
    */

    'driver' => 'magic',

    /*
    |--------------------------------------------------------------------------
    | Auth Identifier
    |--------------------------------------------------------------------------
    |
    | Di sini anda dapat menentukan kolom database yang harus dianggap
    | sebagai "pengenal" untuk pengguna aplikasi anda. Biasanya, ini bisa
    | berupa "username" atau "email".
    | Tentu saja, anda bebas mengubahnya sesuai kebutuhan.
    |
    */

    'identifier' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Auth Password
    |--------------------------------------------------------------------------
    |
    | Di sini anda dapat menentukan kolom database yang harus dianggap
    | sebagai "password" untuk pengguna aplikasi anda. Biasanya, kolom
    | tersebut bernama "password".
    | Tetapi, sekali lagi, anda bebas mengubanya sesuai kebutuhan.
    |
    */

    'password' => 'password',

    /*
    |--------------------------------------------------------------------------
    | Auth Model
    |--------------------------------------------------------------------------
    |
    | Ketika anda memilih "facile" sebagai auth driver, anda dapat menentukan
    | model mana yang harus digunakan sebagai model "User". Model ini akan
    | digunakan untuk mengautentikasi user.
    |
    */

    'model' => 'User',

    /*
    |--------------------------------------------------------------------------
    | Auth Table
    |--------------------------------------------------------------------------
    |
    | Ketika anda memilih "magic" sebagai auth driver, tabel database yang
    | digunakan untuk memuat user dapat ditentukan di sini. Tabel ini akan
    | digunakan oleh magic query builder untuk mengautentikasi user.
    |
    */

    'table' => 'users',
];
