<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Dumper
{
    public function inspect($variable)
    {
        ob_start();
        var_dump($variable);
        return sprintf(" → %s", trim(ob_get_clean()));
    }
}
