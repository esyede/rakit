<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct script access.');

use PDO;

class SQLServer extends Connector
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
    ];

    /**
     * Buat koneksi PDO.
     *
     * @param array $config
     *
     * @return PDO
     */
    public function connect(array $config)
    {
        $port = isset($config['port']) ? ','.$config['port'] : '';

        if (in_array('dblib', PDO::getAvailableDrivers())) {
            $dsn = 'dblib:host='.$config['host'].$port.';dbname='.$config['database'];
        } else {
            $dsn = 'sqlsrv:Server='.$config['host'].$port.';Database='.$config['database'];
        }

        return new PDO($dsn, $config['username'], $config['password'], $this->options($config));
    }
}
