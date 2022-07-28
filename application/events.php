<?php

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
|
| Event memberikan cara yang bagus untuk memecah keterkaitan resource dalam
| aplikasi anda, sehingga kelas, library ataupun plugin tidak akan tercampur
| dan mudah untuk diawasi.
|
*/

Event::listen('404', function () {
    return abort(404);
});

Event::listen('500', function () {
    return abort(500);
});
