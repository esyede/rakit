<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct script access.');

use PDO;

abstract class Connector
{
    /**
     * Opsi default koneksi PDO.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Buat koneksi PDO.
     *
     * @param array $config
     *
     * @return PDO
     */
    abstract public function connect(array $config);

    /**
     * Ambil konfigurasi opsi koneksi PDO.
     * Opsi default akan ditimpa oleh opsi kustom yang diberikan.
     *
     * @param array $config
     *
     * @return array
     */
    protected function options(array $config)
    {
        return (isset($config['options']) ? $config['options'] : []) + $this->options;
    }
}
