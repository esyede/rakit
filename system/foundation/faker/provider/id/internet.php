<?php

namespace System\Foundation\Faker\Provider\id;

defined('DS') or exit('No direct script access.');

use System\Foundation\Faker\Provider\Internet as BaseInternet;

class Internet extends BaseInternet
{
    protected static $freeEmailDomain = ['gmail.com', 'yahoo.com', 'hotmail.com', 'mail.ru'];
    protected static $tld = [
        'com', 'net', 'org', 'asia', 'tv', 'biz', 'info', 'in', 'name', 'co',
        'ac.id', 'sch.id', 'go.id', 'mil.id', 'co.id', 'or.id', 'web.id',
        'my.id', 'biz.id', 'desa.id', 'id',
    ];
}
