<?php

defined('DS') or exit('No direct access.');

use System\Routing\Route;
use System\View;

/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
|
| Cukup beri tahu rakit kata kerja HTTP dan URI yang harus ditanggapi.
| Rakit juga mendukung RESTful routing yang sangat cocok untuk membangun
| aplikasi berskala besar maupun API sederhana.
|
*/

Route::get('/, home', ['as' => 'home', function () {
    return View::make('home.index');
}]);

Route::controller([
    'auth',
    'middleware',
    'home',
    'restful',
    'template.basic',
    'template.name',
    'template.override',
    'admin.panel',
]);
