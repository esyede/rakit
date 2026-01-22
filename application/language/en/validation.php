<?php

defined('DS') or exit('No direct access.');

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
    'regex' => 'The :attribute format is invalid.',
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
    'gt' => 'The :attribute must be greater than :value.',
    'gte' => 'The :attribute must be greater than or equal to :value.',
    'lt' => 'The :attribute must be less than :value.',
    'lte' => 'The :attribute must be less than or equal to :value.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'string' => 'The :attribute must be a string.',
    'json' => 'The :attribute must be a valid JSON string.',
    'timezone' => 'The :attribute must be a valid timezone.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'not_regex' => 'The :attribute format is invalid.',
    'present' => 'The :attribute field must be present.',
    'filled' => 'The :attribute field must have a value.',
    'file' => 'The :attribute must be a file.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has duplicate values.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'in_array' => 'The :attribute field must exist in :other.',
    'date_equals' => 'The :attribute must be equal to :date.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is :value.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values are not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',

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
