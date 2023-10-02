<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    |
    | Opsi untuk mengakstifkan atau menonaktifkan debugger. Jika opsi ini
    | diaktifkan, setiap error yang terjadi akan selalu ditampilkan;
    | Nonaktifkan opsi ini saat aplikasi anda sudah berada di server produksi.
    |
    */

    'activate' => true,

    /*
    |--------------------------------------------------------------------------
    | Show Debug Bar
    |--------------------------------------------------------------------------
    |
    | Opsi untuk menampilkan / menyembunyikan debug bar. Debug bar adalah
    | taskbar kecil yang melayang di pojok kanan bawah layar anda yang berisi
    | informasi debug singkat di aplikasi anda.
    |
    */

    'debugbar' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Query Logging
    |--------------------------------------------------------------------------
    |
    | Secara default, kueri sql, binding dan execution time tiap-tiap operasi
    | database akan di-log kedalam array agar mudah untuk diperiksa.
    |
    | Log tersebut dapat anda lihat melalui method DB::profile() maupun
    | melalui debugbar.
    |
    | Namun, dalam beberapa situasi mengkin anda ingin
    | mematikan fitur logging ini, misalnya ketika aplikasi anda sedang
    | menjalankan operasi database yang berat.
    |
    */

    'database' => true,

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | Jika mode ini diaktifkan, setiap error yang terjadi akan menyebabkan
    | eksekusi aplikasi anda akan langsung dihentikan; jika sebaliknya, maka
    | aplikasi akan tetap berjalan, error hanya akan ditampilkan di debug bar.
    |
    */

    'strict' => true,

    /*
    |--------------------------------------------------------------------------
    | Scream!
    |--------------------------------------------------------------------------
    |
    | Opsi untuk menonaktifkan operator @ (diam!) sehingga notice dan warning
    | tidak lagi disembunyikan oleh PHP.
    |
    */

    'scream' => true,

    /*
    |--------------------------------------------------------------------------
    | Maximum Depth
    |--------------------------------------------------------------------------
    |
    | Sebarapa dalam level array / object yang harus ditampilkan ketika anda
    | memanggil perintah dd(), bd() dan dump() ?
    |
    */

    'depth' => 50,

    /*
    |--------------------------------------------------------------------------
    | Maximum Length
    |--------------------------------------------------------------------------
    |
    | Sebarapa banyak karakter yang harus ditampilkan ketika anda memanggil
    | perintah dd(), bd() dan dump() ?
    |
    */

    'length' => 10000,

    /*
    |--------------------------------------------------------------------------
    | Show Location
    |--------------------------------------------------------------------------
    |
    | Apakah lokasi file juga perlu ditampilkan ketika anda  memanggil
    | perintah dd(), bd() dan dump() ?
    |
    */

    'location' => false,

    /*
    |--------------------------------------------------------------------------
    | Error Email
    |--------------------------------------------------------------------------
    |
    | Isi dengan alamat email anda jika anda ingin menerima notifikasi error
    | pada aplikasi anda.
    |
    */

    'email' => '',
];
