<?php

defined('DS') or exit('No direct access.');

class Dummy_Probe_Observer
{
    public static $seen = [];

    public function creating($model)
    {
        static::$seen[] = 'creating';
    }
}
