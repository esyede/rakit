<?php

namespace System;

defined('DS') or exit('No direct access.');

class Html implements Htmlable
{
    /**
     * Container for the html.
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $value
     */
    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    /**
     * Get the value as html that is ready to print.
     *
     * @return string
     */
    public function to_html()
    {
        return $this->value;
    }

    /**
     * Determine if there is no html at all.
     *
     * @return bool
     */
    public function is_empty()
    {
        return '' === trim($this->value);
    }

    /**
     * Determine if there is any html.
     *
     * @return bool
     */
    public function is_not_empty()
    {
        return ! $this->is_empty();
    }

    /**
     * Get the value as html that is ready to print.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
