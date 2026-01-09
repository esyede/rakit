<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Driver
    |--------------------------------------------------------------------------
    |
    | Rakit menerapkan penanganan pengiriman email berbasis driver.
    | Tentu saja, beberapa driver bawaan juga sudah disediakan agar
    | anda bisa langsung mengirim email secara sederhana dan mudah.
    |
    | Driver tersedia: 'mail', 'smtp', 'sendmail' atau 'log' (testing).
    |
    */

    'driver' => 'mail',

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    |
    | Setelan akun SMTP. Digunakan ketika anda memilih 'smtp'
    | sebagai driver email default.
    |
    */

    'smtp' => [
        'host' => '',
        'port' => 465,
        'username' => '',
        'password' => '',
        'timeout' => 5,
        'starttls' => true,
        'keep_alive' => false,
        'options' => [
            // ..
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sendmail
    |--------------------------------------------------------------------------
    |
    | Path file binary sendmail. Digunakan ketika anda memilih 'sendmail'
    | sebagai driver email default.
    |
    */

    'sendmail_binary' => '/usr/sbin/sendmail',

    /*
    |--------------------------------------------------------------------------
    | Mail Type
    |--------------------------------------------------------------------------
    |
    | Tentukan apakah email perlu dikirim dalam bentuk html atau teks biasa,
    | set ke NULL untuk pendeteksian otomatis.
    |
    */

    'as_html' => null,

    /*
    |--------------------------------------------------------------------------
    | Character Encoding
    |--------------------------------------------------------------------------
    |
    | Character encoding default yang digunakan oleh email anda.
    |
    | Opsi tersedia: '8bit', 'base64' atau 'quoted-printable'.
    |
    */

    'encoding' => '8bit',

    /*
    |--------------------------------------------------------------------------
    | Encode Headers
    |--------------------------------------------------------------------------
    |
    | Apakah subjek dan nama penerima juga perlu di-encode?
    |
    */

    'encode_headers' => true,

    /*
    |--------------------------------------------------------------------------
    | Priority
    |--------------------------------------------------------------------------
    |
    | Atur prioritas pengiriman email.
    |
    | Opsi tersedia: LOWEST, LOW, NORMAL, HIGH, HIGHEST
    |
    */

    'priority' => System\Email::NORMAL,

    /*
    |--------------------------------------------------------------------------
    | Default Sender
    |--------------------------------------------------------------------------
    |
    | Identitas default anda sebagai pengirim email.
    |
    */

    'from' => [
        'email' => 'noreply@example.com',
        'name' => 'Administrator',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Validasi alamat email?
    |
    */

    'validate' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-attach
    |--------------------------------------------------------------------------
    |
    | Lampirkan file inline secara otomatis?
    |
    */

    'attachify' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-generate Alt Body
    |--------------------------------------------------------------------------
    |
    | Buat alt-body secara otomatis dari tag <body> html?
    |
    */

    'alternatify' => true,

    /*
    |--------------------------------------------------------------------------
    | Force Mixed
    |--------------------------------------------------------------------------
    |
    | Ubah paksa content-type multipart/related menjadi multipart/mixed?
    |
    */

    'force_mixed' => false,

    /*
    |--------------------------------------------------------------------------
    | Wordwrap
    |--------------------------------------------------------------------------
    |
    | Ukuran wordwrap (pemenggalan kalimat). Set ke NULL, 0 atau FALSE untuk
    | mematikan fitur ini.
    |
    */

    'wordwrap' => 76,

    /*
    |--------------------------------------------------------------------------
    | Newline
    |--------------------------------------------------------------------------
    |
    | Karakter newline untuk penyusunan header dan body email.
    |
    */

    'newline' => "\r\n",

    /*
    |--------------------------------------------------------------------------
    | Return Path
    |--------------------------------------------------------------------------
    |
    | Return path default untuk email anda.
    |
    */

    'return_path' => false,

    /*
    |--------------------------------------------------------------------------
    | Strip Comments
    |--------------------------------------------------------------------------
    |
    | Bersihkan seluruh html comment dari body email?
    |
    */

    'strip_comments' => true,

    /*
    |--------------------------------------------------------------------------
    | Replace Protocol
    |--------------------------------------------------------------------------
    |
    | Ketika protokol URI relatif ('//fooobar') digunakan di body email,
    | anda dapat menentukan di sini dengan apa anda ingin menggantinya.
    |
    | Opsi yang tersedia adalah: 'http://', 'https://' atau FALSE.
    |
    */

    'protocol_replacement' => false,
];
