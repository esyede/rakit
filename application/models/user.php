<?php

defined('DS') or exit('No direct script access.');

class User extends Facile
{
    /**
     * Atribut yang mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ..
}
