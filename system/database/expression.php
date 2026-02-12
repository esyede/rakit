<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

class Expression
{
    /**
     * Contains value of the database expression.
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value of the database expression.
     *
     * @return string
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Get the string representation of the database expression value.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}
