<?php

namespace System\Transformer;

defined('DS') or exit('No direct access.');

class Merged
{
    /**
     * Contains the values to fold into the array around it.
     *
     * @var array
     */
    public $values;

    /**
     * Constructor.
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }
}
