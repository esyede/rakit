<?php

defined('DS') or exit('No direct script access.');

class User extends Facile
{
    /**
     * Atribut yang mass-assignable.
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
     * Atribut yang harus disembunyikan saat serialisasi.
     *
     * @var array
     */
    public static $hidden = [
        'password',
    ];

    // ..
}
