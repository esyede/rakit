<?php

namespace System\Database;

defined('DS') or exit('No direct script access.');

class Expression
{
    /**
     * Berisi value dari ekspresi database.
     *
     * @var string
     */
    protected $value;

    /**
     * Buat instance ekspresi database baru.
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Ambil value ekspresi database alam bentuk string.
     *
     * @return string
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Ambil value ekspresi database alam bentuk string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}
