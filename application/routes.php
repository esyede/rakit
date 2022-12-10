<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Route;

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

Route::get('/', 'home@index');

/*
|--------------------------------------------------------------------------
| Auto-generated auth routes
|--------------------------------------------------------------------------
*/

Route::get('login', 'auth.login@show');
Route::post('login', ['as' => 'login', 'uses' => 'auth.login@login']);
Route::post('logout', 'auth.login@logout');

// Registration routes..
Route::get('register', 'auth.register@show');
Route::post('register', ['as' => 'register', 'uses' => 'auth.register@register']);

// Password reset routes...
Route::get('password/email', 'auth.password@show_resend');
Route::post('password/email', 'auth.password@resend');
Route::post('password/reset', 'auth.password@reset');
Route::get('password/reset/(:any?)', 'auth.password@show_reset');

// Dashboard routes..
Route::get('/dashboard', 'dashboard@index');
