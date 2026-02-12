<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

class MySQL extends Grammar
{
    /**
     * Contains the wrapper format.
     *
     * @var string
     */
    protected $wrapper = '`%s`';
}
