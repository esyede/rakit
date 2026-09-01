<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Exporter
{
    /**
     * Inspect a variable and return a formatted string.
     *
     * @param mixed $variable
     *
     * @return string
     */
    public function inspect($variable)
    {
        return sprintf(' → %s', var_export($variable, true));
    }
}
