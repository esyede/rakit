<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | This file contains the language strings used by the Validation library.
    | Feel free to change them to anything you want to customize your views to
    | better match your application. You can also change the default error
    | messages used by the library.
    |
    */

    'accepted' => 'Bilah :attribute harus disetujui.',
    'active_url' => 'Bilah :attribute bukan URL yang valid.',
    'after' => 'Bilah :attribute harus tanggal setelah :date.',
    'alpha' => 'Bilah :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Bilah :attribute hanya boleh berisi huruf, angka, dan strip.',
    'alpha_num' => 'Bilah :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Bilah :attribute harus memiliki elemen yang dipilih.',
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
    'date' => 'Bilah :attribute harus berisi tanggal.',
    'date_format' => 'Bilah :attribute harus diisi tanggal dengan format :format.',
    'different' => 'Bilah :attribute dan :other harus berbeda.',
    'email' => 'Format isian :attribute tidak valid.',
    'exists' => 'Bilah :attribute yang dipilih tidak valid.',
    'image' => ':attribute harus berupa gambar.',
    'in' => 'Bilah :attribute yang dipilih tidak valid.',
    'integer' => 'Bilah :attribute harus merupakan bilangan.',
    'ip' => 'Bilah :attribute harus alamat IP yang valid.',
    'match' => 'Format isian :attribute tidak valid.',
    'regex' => 'Format :attribute tidak valid.',
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
    'required_with' => 'Bilah :attribute wajib diisi dengan :field',
    'same' => 'Bilah :attribute dan :other harus sama.',
    'size' => [
        'numeric' => 'Bilah :attribute harus berukuran :size.',
        'file' => 'Bilah :attribute harus berukuran :size kilobyte.',
        'string' => 'Bilah :attribute harus berukuran :size karakter.',
    ],
    'unique' => 'Bilah :attribute sudah ada sebelumnya.',
    'url' => 'Format bilah :attribute tidak valid.',
    'gt' => 'Bilah :attribute harus lebih besar dari :value.',
    'gte' => 'Bilah :attribute harus lebih besar atau sama dengan :value.',
    'lt' => 'Bilah :attribute harus lebih kecil dari :value.',
    'lte' => 'Bilah :attribute harus lebih kecil atau sama dengan :value.',
    'digits' => 'Bilah :attribute harus :digits digit.',
    'digits_between' => 'Bilah :attribute harus antara :min dan :max digit.',
    'string' => 'Bilah :attribute harus berupa string.',
    'json' => 'Bilah :attribute harus berupa JSON yang valid.',
    'timezone' => 'Bilah :attribute harus berupa timezone yang valid.',
    'ipv4' => 'Bilah :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Bilah :attribute harus berupa alamat IPv6 yang valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'present' => 'Bilah :attribute harus ada.',
    'filled' => 'Bilah :attribute harus diisi.',
    'file' => 'Bilah :attribute harus berupa file.',
    'mimetypes' => 'Bilah :attribute harus berupa file dengan tipe: :values.',
    'dimensions' => 'Bilah :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Bilah :attribute memiliki nilai duplikat.',
    'ends_with' => 'Bilah :attribute harus diakhiri dengan salah satu dari: :values.',
    'starts_with' => 'Bilah :attribute harus dimulai dengan salah satu dari: :values.',
    'in_array' => 'Bilah :attribute harus ada dalam :other.',
    'date_equals' => 'Bilah :attribute harus sama dengan tanggal :date.',
    'required_if' => 'Bilah :attribute diperlukan ketika :other adalah :value.',
    'required_unless' => 'Bilah :attribute diperlukan kecuali :other adalah :value.',
    'required_with_all' => 'Bilah :attribute diperlukan ketika :values ada.',
    'required_without' => 'Bilah :attribute diperlukan ketika :values tidak ada.',
    'required_without_all' => 'Bilah :attribute diperlukan ketika tidak ada :values.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    | For example, to specify a custom message for the 'email' attribute
    | simple add 'email_required' to this array with your custom message.
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
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    | The class validator will automatically search this array, and replace
    | the :attribute placeholder with the value you specify here.
    |
    */

    'attributes' => [
        // ..
    ],

];
