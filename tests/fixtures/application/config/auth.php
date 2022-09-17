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
    | Auth Table
    |--------------------------------------------------------------------------
    |
    | Ketika anda memilih "magic" sebagai auth driver, tabel database yang
    | digunakan untuk memuat user dapat ditentukan di sini. Tabel ini akan
    | digunakan oleh magic query builder untuk mengautentikasi user.
    |
    */

    'table' => 'users',

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
];
