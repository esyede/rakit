<?php

defined('DS') or exit('No direct script access.');

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
