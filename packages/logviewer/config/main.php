<?php

defined('DS') or exit('No direct script access.');

return [

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware untuk proteksi akses ke log viewer. Pastikan proteksi ini
    | diaktifkan dan pastikan HANYA ADMIN yang bisa mengakses log viewer.
    |
    */

    'middleware' => [
        'auth',
        // 'admin_only',
    ],
];
