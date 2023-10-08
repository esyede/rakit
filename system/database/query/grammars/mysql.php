<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

class MySQL extends Grammar
{
    /**
     * Identifier keyword engine database.
     *
     * @var string
     */
    protected $wrapper = '`%s`';
}
