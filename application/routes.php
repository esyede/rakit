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

Route::get('/', 'home@index');

// Health check for load balancers and uptime monitors: 200 while the application
// boots and routes, 503 during maintenance ('php rakit down').
Route::get('up', function () {
    return Response::make('OK', 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
});
