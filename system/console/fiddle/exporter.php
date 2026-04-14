<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Exporter
{
    public function inspect($variable)
    {
        return sprintf(" → %s", var_export($variable, true));
    }
}
