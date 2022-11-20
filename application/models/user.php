<?php

defined('DS') or exit('No direct script access.');

class User extends Facile
{
    /**
     * Atribut yang dapat di assign secara massal.
     *
     * @var array
     */
    public static $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi.
     *
     * @var array
     */
    public static $hidden = [
        'password',
        'remember_token',
    ];

    // ..
}
