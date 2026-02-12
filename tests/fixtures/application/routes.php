<?php

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
|
| Simply tell Rakit the HTTP verbs and URIs it should respond to. It is a
| breeze to setup your application using Rakit's RESTful routing and it
| is perfectly suited for building large applications and simple APIs.
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
