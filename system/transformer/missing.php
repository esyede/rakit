<?php

namespace System\Transformer;

defined('DS') or exit('No direct access.');

class Missing
{
    /**
     * Get the value as a string, which it never really has.
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }
}
