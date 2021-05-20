<?php

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Konfigurasi Paket
|--------------------------------------------------------------------------
|
| Paket memungkinkan anda untuk memodularisasi aplikasi anda dengan mudah.
| Bayangkan paket sebagai aplikasi mandiri. Mereka dapat memiliki route,
| kontroler, model, view, konfigurasi, dll.
|
*/

return [
    'dashboard' => ['handles' => 'dashboard'],
    'dummy',
];
