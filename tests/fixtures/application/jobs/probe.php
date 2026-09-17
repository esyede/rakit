<?php

defined('DS') or exit('No direct access.');

class Probe_Job extends Jobable
{
    public static $seen = [];

    public function run()
    {
        static::$seen[] = $this->get('message');
    }
}
