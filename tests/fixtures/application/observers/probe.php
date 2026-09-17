<?php

defined('DS') or exit('No direct access.');

class Probe_Observer
{
    public static $seen = [];

    public function creating($model)
    {
        static::$seen[] = 'creating';
    }
}
