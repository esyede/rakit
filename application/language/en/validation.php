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

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must have selected elements.',
    'ascii' => 'The :attribute contains non-ASCII characters.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equals' => 'The :attribute must be a date before or equals to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min - :max.',
        'file' => 'The :attribute must be between :min - :max kilobytes.',
        'string' => 'The :attribute must be between :min - :max characters.',
    ],
    'boolean' => 'The :attribute is not a valid boolean value.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'count' => 'The :attribute must have exactly :count selected elements.',
    'countbetween' => 'The :attribute must have between :min and :max selected elements.',
    'countmax' => 'The :attribute must have less than :max selected elements.',
    'countmin' => 'The :attribute must have at least :min selected elements.',
    'date' => 'The :attribute is not a valid date.',
    'date_format' => 'The :attribute must have a valid date format :format.',
    'different' => 'The :attribute and :other must be different.',
    'email' => 'The :attribute format is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'match' => 'The :attribute format is invalid.',
    'max' => [
        'numeric' => 'The :attribute must be less than :max.',
        'file' => 'The :attribute must be less than :max kilobytes.',
        'string' => 'The :attribute must be less than :max characters.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'required' => 'The :attribute field is required.',
    'required_with' => 'The :attribute field is required with :field',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobyte.',
        'string' => 'The :attribute must be :size characters.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'url' => 'The :attribute format is invalid.',
    'utf8' => 'The :attribute should anly contains UTF-8 characters.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language
    |--------------------------------------------------------------------------
    |
    | Di sini anda dapat menentukan pesan validasi kuatom untuk atribut
    | menggunakan konvensi '[atribut] + _ + [rule]'. Ini membantu menjaga
    | validasi kustom anda tetap bersih dan rapi.
    |
    | Jadi, katakanlah anda ingin menggunakan pesan validasi kuatom ketika
    | memvalidasi bahwa atribut 'email' itu unik.
    | Cukup tambahkan 'email_unique' ke array ini dengan pesan kuatom anda.
    |
    */

    'custom' => [
        // ..
    ],

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

    'attributes' => [
        // ..
    ],
];
