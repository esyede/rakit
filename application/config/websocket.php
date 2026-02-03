<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Ukuran Buffer Maksimal
    |--------------------------------------------------------------------------
    |
    | Ukuran maksimal buffer untuk menerima data dari socket. Nilai ini
    | menentukan berapa banyak data yang dapat dibaca dalam satu kali
    | operasi socket_recv. Jika pesan lebih besar dari ukuran ini,
    | pesan akan dipotong atau ditangani dalam beberapa bagian.
    |
    */

    'max_buffer_size' => 2048,

    /*
    |--------------------------------------------------------------------------
    | Origin Diperlukan
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah header Origin diperlukan dalam handshake WebSocket.
    | Jika diaktifkan, koneksi tanpa header Origin yang valid akan ditolak.
    | Ini berguna untuk keamanan, terutama dalam produksi.
    |
    */

    'origin_required' => false,

    /*
    |--------------------------------------------------------------------------
    | Protokol Diperlukan
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah header Sec-WebSocket-Protocol diperlukan.
    | Jika diaktifkan, koneksi tanpa protokol yang didukung akan ditolak.
    | Protokol ini digunakan untuk subprotokol WebSocket seperti chat atau binary.
    |
    */

    'protocol_required' => false,

    /*
    |--------------------------------------------------------------------------
    | Ekstensi Diperlukan
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah header Sec-WebSocket-Extensions diperlukan.
    | Jika diaktifkan, koneksi tanpa ekstensi yang didukung akan ditolak.
    | Ekstensi ini dapat digunakan untuk kompresi atau fitur lainnya.
    |
    */

    'extensions_required' => false,

    /*
    |--------------------------------------------------------------------------
    | Origin yang Diizinkan
    |--------------------------------------------------------------------------
    |
    | Daftar origin (domain) yang diizinkan untuk koneksi WebSocket.
    | Jika origin_required aktif, hanya origin dalam daftar ini yang diterima.
    | Kosongkan array untuk mengizinkan semua origin (tidak aman untuk produksi).
    |
    */

    'allowed_origins' => [],

    /*
    |--------------------------------------------------------------------------
    | Host yang Diizinkan
    |--------------------------------------------------------------------------
    |
    | Daftar host yang diizinkan untuk koneksi WebSocket.
    | Jika kosong, semua host diizinkan.
    |
    */

    'allowed_hosts' => [],

    /*
    |--------------------------------------------------------------------------
    | Protokol yang Didukung
    |--------------------------------------------------------------------------
    |
    | Daftar subprotokol WebSocket yang didukung server.
    | Jika kosong, semua protokol diterima (jika protocol_required aktif).
    |
    */

    'supported_protocols' => [],

    /*
    |--------------------------------------------------------------------------
    | Ekstensi yang Didukung
    |--------------------------------------------------------------------------
    |
    | Daftar ekstensi WebSocket yang didukung server.
    | Jika kosong, semua ekstensi diterima (jika extensions_required aktif).
    |
    */

    'supported_extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | Timeout Ping
    |--------------------------------------------------------------------------
    |
    | Waktu dalam detik untuk menunggu aktivitas dari klien sebelum
    | menganggap koneksi idle dan memutuskan. Ping digunakan untuk
    | menjaga koneksi tetap hidup. Nilai 0 menonaktifkan timeout.
    |
    */

    'ping_timeout' => 0,

    /*
    |--------------------------------------------------------------------------
    | Logging Diaktifkan
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah logging untuk WebSocket server diaktifkan.
    | Jika dinonaktifkan, tidak ada log yang akan dicatat.
    |
    */

    'logging_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Output Logging
    |--------------------------------------------------------------------------
    |
    | Menentukan tempat output logging: 'file' untuk menyimpan ke file log
    | menggunakan class Log, atau 'stdout' untuk output ke console.
    |
    */

    'logging_output' => 'stdout',
];
