<?php

defined('DS') or exit('No direct access.');

use System\Routing\Route;

/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
|
| Simply tell rakit the HTTP verb and URI it should respond to.
| Rakit also supports RESTful routing which is perfect for building
| large-scale applications as well as simple APIs.
|
*/

Route::get('(:package)', 'docs::home@index');
Route::get('(:package)/search', 'docs::home@search');
Route::get('(:package)/(:any?)/(:any?)', 'docs::home@page');
