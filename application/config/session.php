<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Driver Session
    |--------------------------------------------------------------------------
    |
    | Nama driver session yang digunakan oleh aplikasi anda. Karena HTTP
    | bersifat state-less, session inilah yang digunakan untuk mensimulasikan
    | "state" di seluruh request yang dikirim oleh user. Dengan kata lain,
    | begitulah cara aplikasi mengetahui siapa anda sebenarnya.
    |
    | Driver bawaan: 'cookie', 'file', 'database', 'memcached', 'apc', 'redis'.
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Tabel Session
    |--------------------------------------------------------------------------
    |
    | Tabel tempat session harus disimpan. Opsi ini hanya penting jika anda
    | memilih "database" sebagai driver session untuk aplikasi anda.
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | GC Probability
    |--------------------------------------------------------------------------
    |
    | Beberapa driver session memerlukan pembersihan manual terhadap session
    | yang telah kedaluwarsa. Opsi ini menentukan kemungkinan terjadinya
    | pengumpulan sampah session untuk setiap request yang datang ke aplikasi.
    |
    | Misalnya, default value dibawah ini menyatakan bahwa pengumpulan sampah
    | memiliki peluang 2% terjadi untuk setiap request yang datang ke aplikasi.
    | Jangan ragu untuk menyesuaikan ini dengan kebutuhan anda.
    |
    */

    'sweepage' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Lifetime Session
    |--------------------------------------------------------------------------
    |
    | Jumlah menit session tersedia sebelum ia kedaluwarsa.
    |
    */

    'lifetime' => 60,

    /*
    |--------------------------------------------------------------------------
    | Expire On Close
    |--------------------------------------------------------------------------
    |
    | Apakah session harus kedaluwarsa jika user menutup web browser mereka?
    |
    */

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Nama Cookie Session
    |--------------------------------------------------------------------------
    |
    | Nama yang harus diberikan ke cookie session.
    |
    */

    'cookie' => 'rakit_session',

    /*
    |--------------------------------------------------------------------------
    | Path Cookie Session
    |--------------------------------------------------------------------------
    |
    | Path dimana cookie session tersedia.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Domain Cookie Session
    |--------------------------------------------------------------------------
    |
    | Domain dimana cookie session tersedia.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Cookie Session Hanya HTTPS
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah cookie hanya boleh dikirim melalui HTTPS.
    |
    */

    'secure' => false,

    /*
    |--------------------------------------------------------------------------
    | Same-site Cookie
    |--------------------------------------------------------------------------
    |
    | Atribut samesite cookie di PHP 7.3+. Biarkan default agar mengikuti
    | aturan yang telah ditentukan oleh web browser.
    |
    | Value yang bisa digunakan: 'Lax' (default), 'Strict' atau 'None'.
    |
    */

    'samesite' => 'Lax',
];
