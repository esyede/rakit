<?php

defined('DS') or exit('No direct access.');

use System\View;

/*
|--------------------------------------------------------------------------
| View Composer
|--------------------------------------------------------------------------
|
| Setiap kali suatu view dibuat, event `'composer'`-nya akan tereksekusi.
| Anda dapat me-listen event ini dan menggunakannya untuk binding aset
| dan data ke view setiap kali ia dimuat.
|
| Penggunaan umum fitur ini contohnya adalah view parsial navigasi sidebar
| yang memperlihatkan daftar posting blog secara acak. Anda dapat membuat
| nested view parsial dengan memuatnya dalam layout view anda.
| Kemudian, daftarkan composer untuk view parsial tersebut.
|
| <code>
|
|      // Mendaftarkan sebuah view composer untuk view "home":
|      View::composer('home', function ($view) {
|          $view->nest('footer', 'partials.footer');
|      });
|
|      // Mendaftarkan sebuah composer yang menangani beberapa view:
|      View::composer(['home', 'profile'], function ($view) {
|          // ..
|      });
|
| </code>
|
*/

// ..
