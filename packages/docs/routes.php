<?php

defined('DS') or exit('No direct access.');

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

Route::get('(:package)', 'docs::home@index');
Route::get('(:package)/(en|id)', 'docs::home@index');
Route::get('(:package)/(en|id)/(:any)/(:any?)', 'docs::home@page');
