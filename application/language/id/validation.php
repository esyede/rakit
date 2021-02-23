<?php

defined('DS') or exit('No direct script access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language
    |--------------------------------------------------------------------------
    |
    | Baris - baris bahasa berikut berisi pesan error default yang digunakan
    | oleh kelas Validator. Beberapa aturan berisi beberapa versi, seperti
    | ukuran (max, min, between). Versi - versi ini digunakan untuk berbagai
    | jenis input seperti string dan file.
    |
    */

    'accepted' => 'Bilah :attribute harus disetujui.',
    'active_url' => 'Bilah :attribute bukan URL yang valid.',
    'after' => 'Bilah :attribute harus tanggal setelah :date.',
    'alpha' => 'Bilah :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Bilah :attribute hanya boleh berisi huruf, angka, dan strip.',
    'alpha_num' => 'Bilah :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Bilah :attribute harus memiliki elemen yang dipilih.',
    'ascii' => 'Bilah :attribute mengandung karakter non-ASCII.',
    'before' => 'Bilah :attribute harus tanggal sebelum :date.',
    'before_or_equals' => 'Bilah :attribute harus diisi tanggal sebelum atau tepat :date.',
    'between' => [
        'numeric' => 'Bilah :attribute harus antara :min - :max.',
        'file' => 'Bilah :attribute harus antara :min - :max kilobytes.',
        'string' => 'Bilah :attribute harus antara  :min - :max karakter.',
    ],
    'boolean' => 'Bilah :attribute harus diisi dengan nilai boolean.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'count' => 'Bilah :attribute harus memiliki tepat :count elemen.',
    'countbetween' => 'Bilah :attribute harus diantara :min dan :max elemen.',
    'countmax' => 'Bilah :attribute harus lebih kurang dari :max elemen.',
    'countmin' => 'Bilah :attribute harus paling sedikit :min elemen.',
    'different' => 'Bilah :attribute dan :other harus berbeda.',
    'email' => 'Format isian :attribute tidak valid.',
    'exists' => 'Bilah :attribute yang dipilih tidak valid.',
    'image' => ':attribute harus berupa gambar.',
    'in' => 'Bilah :attribute yang dipilih tidak valid.',
    'integer' => 'Bilah :attribute harus merupakan bilangan.',
    'ip' => 'Bilah :attribute harus alamat IP yang valid.',
    'match' => 'Format isian :attribute tidak valid.',
    'max' => [
        'numeric' => 'Bilah :attribute harus kurang dari :max.',
        'file' => 'Bilah :attribute harus kurang dari :max kilobytes.',
        'string' => 'Bilah :attribute harus kurang dari :max karakter.',
    ],
    'mimes' => 'Bilah :attribute harus dokumen berjenis : :values.',
    'min' => [
        'numeric' => 'Bilah :attribute harus minimal :min.',
        'file' => 'Bilah :attribute harus minimal :min kilobytes.',
        'string' => 'Bilah :attribute harus minimal :min karakter.',
    ],
    'not_in' => 'Bilah :attribute yang dipilih tidak valid.',
    'numeric' => 'Bilah :attribute harus berupa angka.',
    'required' => 'Bilah :attribute wajib diisi.',
    'same' => 'Bilah :attribute dan :other harus sama.',
    'size' => [
        'numeric' => 'Bilah :attribute harus berukuran :size.',
        'file' => 'Bilah :attribute harus berukuran :size kilobyte.',
        'string' => 'Bilah :attribute harus berukuran :size karakter.',
    ],
    'unique' => 'Bilah :attribute sudah ada sebelumnya.',
    'url' => 'Format bilah :attribute tidak valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language
    |--------------------------------------------------------------------------
    |
    | Di sini anda dapat menentukan pesan validasi kustom untuk atribut
    | menggunakan konvensi 'atribut_rule'. Ini membantu menjaga validasi
    | kustom anda tetap bersih dan rapi.
    |
    | Jadi, katakanlah anda ingin menggunakan pesan validasi kustom ketika
    | memvalidasi bahwa atribut 'email' itu unik.
    | Cukup tambahkan 'email_unique' ke array ini dengan pesan kustom anda.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Validation Attribute
    |--------------------------------------------------------------------------
    |
    | Baris - baris bahasa berikut digunakan untuk menukar atribut placeholder
    | dengan sesuatu yang lebih ramah pembaca seperti 'alamat email'
    | alih-alih 'email' saja sehingga pesan error anda akan lebih informatif.
    |
    | Kelas validator akan secara otomatis mencari array ini, lalu mengganti
    | placeholder :attribute di dengan value kustom yang anda tentukan disini.
    |
    */

    'attributes' => [],

];
