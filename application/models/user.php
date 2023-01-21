<?php

defined('DS') or exit('No direct script access.');

use System\Database\Facile\Model as Facile;

class User extends Facile
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public static $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    public static $hidden = [
        'password',
        'remember_token',
    ];

    // ..
}
