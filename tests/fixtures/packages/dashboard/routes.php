<?php

defined('DS') or exit('No direct script access.');

Route::get('dashboard', ['as' => 'dashboard', function () {
    // ..
}]);

Route::controller('dashboard::panel');
