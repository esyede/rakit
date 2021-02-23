<?php

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Konfigurasi Paket
|--------------------------------------------------------------------------
|
| Paket memungkinkan anda untuk memodularisasi aplikasi anda dengan mudah.
| Bayangkan paket sebagai aplikasi mandiri. Mereka dapat memiliki route,
| kontroler, model, view, konfigurasi, dll. Anda bahkan dapat membuat file
| paket anda sendiri untuk dibagikan dengan komunitas rakit.
|
| Dibawah ini list paket yang terinstal di aplikasi anda serta konfigurasinya
| ia juga sekaligus memberitahu rakit dimana lokasi direktori root paket,
| serta root URI mana yang direspon oleh paket tersebut.
|
| Misalnya, jika anda memiliki paket bernama 'admin' yang terletak
| di folder 'packages/admin/' dan anda ingin merespon request dengan
| URI yang dimulai dengan 'admin', cukup tambahkan seperti ini:
|
|       'admin' => [
|           'location' => 'admin',
|           'handles'  => 'admin',
|       ],
|
| Perhatikan bahwa 'location' relatif terhadap direktori 'packages/'.
| Sekarang paket tersebut akan dikenali oleh rakit dan akan bisa
| untuk merespon request ke URI yang dimulai dengan 'admin'.
|
| Ingin menginstal paket tetapi tidak ingin ia merespon request apa pun?
| Cukup tuliskan nama paketnya saja dan rakit akan mengurus sisanya.
|
*/

return [
    'docs' => ['handles' => 'docs'],

    // Taruh array konfigurasi paket lain disini..

];
