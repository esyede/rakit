<?php

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Middleware Rute
|--------------------------------------------------------------------------
|
| Middleware menyediakan cara untuk melampirkan fungsionalitas ke rute anda.
| Middleware bawaan 'before' dan 'after' akan dipanggil sebelum dan sesudah
| setiap request direspon. Anda juga dapat membuat middleware baru tentunya.
|
| Mari kita lihat contohnya..
|
| <code>
|
|      // Pertama, definisikan middlewarenya:
|      Route::middleware('middlewareku', function () {
|          return 'Middlewareku sukses dijalankan!';
|      });
|
|      // Lalu, tinggal lampirkan saja ke rute:
|      Route::get('/', ['before' => 'middlewareku', function () {
|          return Halo dunia!';
|      }]);
|
| <code>
|
*/

// ..
