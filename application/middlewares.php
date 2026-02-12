<?php

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Route Middlewares
|--------------------------------------------------------------------------
|
| Middleware provides a way to attach functionality to your routes.
| It allows you to define middleware functions that can be applied to
| specific routes or groups of routes. Middleware functions can perform
| tasks such as authentication, authorization, rate limiting, and more.
|
|
*/

Route::middleware('csrf', function () {
    if (Request::forged()) {
        return Response::error(422);
    }
});

Route::middleware('auth', function () {
    if (Auth::guest()) {
        return Response::error(401);
    }
});

Route::middleware('throttle', function ($limit, $minutes) {
    if (Throttle::exceeded($limit, $minutes)) {
        return Throttle::error();
    }
});
