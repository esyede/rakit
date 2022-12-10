<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Route;

Route::get('dashboard', ['as' => 'dashboard', function () {
    // ..
}]);

Route::controller('dashboard::panel');
