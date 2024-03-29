<?php

defined('DS') or exit('No direct access.');

use System\Routing\Route;
use System\Request;
use System\Response;
use System\Auth;
use System\Routing\Throttle;

/*
|--------------------------------------------------------------------------
| Middleware
|--------------------------------------------------------------------------
|
| Middleware menyediakan cara untuk melampirkan fungsionalitas ke rute anda.
| Middleware bawaan 'before' dan 'after' akan dipanggil sebelum dan sesudah
| setiap request direspon.
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
