<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | String Infleksi
    |--------------------------------------------------------------------------
    |
    | Ini adalah array berisi kata tunggal (singular) dan jamak (plural).
    | Array ini digunakan oleh Str::plural() dan Str::singular() untuk
    | mengubah kata tertentu dari tunggal menjadi jamak dan sebaliknya.
    |
    | Perhatikan bahwa pola regex dibawah ini hanya bekerja untuk kata
    | dalam bahasa Inggris. Untuk mengubah string non-Inggris, silahkan
    | tambahkan bentuk tunggal dan jamaknya ke array "irregular".
    |
    */

    'plural' => [
        '/(quiz)$/i' => '$1zes',
        '/^(ox)$/i' => '$1en',
        '/([m|l])ouse$/i' => '$1ice',
        '/(matr|vert|ind)ix|ex$/i' => '$1ices',
        '/(x|ch|ss|sh)$/i' => '$1es',
        '/([^aeiouy]|qu)y$/i' => '$1ies',
        '/(hive)$/i' => '$1s',
        '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
        '/(shea|lea|loa|thie)f$/i' => '$1ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '$1a',
        '/(tomat|potat|ech|her|vet)o$/i' => '$1oes',
        '/(bu)s$/i' => '$1ses',
        '/(alias)$/i' => '$1es',
        '/(octop)us$/i' => '$1i',
        '/(ax|test)is$/i' => '$1es',
        '/(us)$/i' => '$1es',
        '/s$/i' => 's',
        '/$/' => 's',
    ],

    'singular' => [
        '/(quiz)zes$/i' => '$1',
        '/(matr)ices$/i' => '$1ix',
        '/(vert|ind)ices$/i' => '$1ex',
        '/^(ox)en$/i' => '$1',
        '/(alias)es$/i' => '$1',
        '/(octop|vir)i$/i' => '$1us',
        '/(cris|ax|test)es$/i' => '$1is',
        '/(shoe)s$/i' => '$1',
        '/(o)es$/i' => '$1',
        '/(bus)es$/i' => '$1',
        '/([m|l])ice$/i' => '$1ouse',
        '/(x|ch|ss|sh)es$/i' => '$1',
        '/(m)ovies$/i' => '$1ovie',
        '/(s)eries$/i' => '$1eries',
        '/([^aeiouy]|qu)ies$/i' => '$1y',
        '/([lr])ves$/i' => '$1f',
        '/(tive)s$/i' => '$1',
        '/(hive)s$/i' => '$1',
        '/(li|wi|kni)ves$/i' => '$1fe',
        '/(shea|loa|lea|thie)ves$/i' => '$1f',
        '/(^analy)ses$/i' => '$1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
        '/([ti])a$/i' => '$1um',
        '/(n)ews$/i' => '$1ews',
        '/(h|bl)ouses$/i' => '$1ouse',
        '/(corpse)s$/i' => '$1',
        '/(us)es$/i' => '$1',
        '/(us|ss)$/i' => '$1',
        '/s$/i' => '',
    ],

    'irregular' => [
        'child' => 'children',
        'foot' => 'feet',
        'goose' => 'geese',
        'man' => 'men',
        'move' => 'moves',
        'person' => 'people',
        'sex' => 'sexes',
        'tooth' => 'teeth',
    ],

    'uncountable' => [
        'audio',
        'equipment',
        'deer',
        'fish',
        'gold',
        'information',
        'money',
        'rice',
        'police',
        'series',
        'sheep',
        'species',
        'moose',
        'chassis',
        'traffic',
    ],
];
