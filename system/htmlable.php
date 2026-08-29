<?php

namespace System;

defined('DS') or exit('No direct access.');

interface Htmlable
{
    /**
     * Get the value as html that is ready to print.
     *
     * @return string
     */
    public function to_html();
}
