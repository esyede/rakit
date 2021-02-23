<?php

defined('DS') or exit('No direct script access.');

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
Route::get('(:package)/(:any)/(:any?)', 'docs::home@page');
