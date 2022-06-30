<?php

namespace System\Database;

defined('DS') or exit('No direct script access.');

class Failure extends \Exception
{
    /**
     * Berisi data exception mentah.
     *
     * @var \Exception
     */
    protected $inner;

    /**
     * Buat instance database exception baru.
     *
     * @param string    $sql
     * @param array     $bindings
     * @param Exception $inner
     */
    public function __construct($sql, array $bindings, \Exception $inner)
    {
        $this->inner = $inner;
        $this->setMessage($sql, $bindings);
        $this->code = $inner->getCode();
    }

    /**
     * Ambil data exception mentah.
     *
     * @return Exception
     */
    public function getInner()
    {
        return $this->inner;
    }

    /**
     * Tambahkan query sql dan binding pada pesan exception.
     *
     * @param string $sql
     * @param array  $bindings
     */
    protected function setMessage($sql, array $bindings)
    {
        $this->message = $this->inner->getMessage();
        $this->message .= PHP_EOL.' SQL: '.$sql.PHP_EOL.' Bindings: '.var_export($bindings, true);
    }
}
