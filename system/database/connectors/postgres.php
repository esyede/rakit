<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct script access.');

use PDO;

class Postgres extends Connector
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
        $host = isset($config['host']) ? 'host=' . $config['host'] . ';' : '';
        $dsn = 'pgsql:' . $host . 'dbname=' . $config['database'];
        $dsn .= isset($config['port']) ? ';port=' . $config['port'] : '';

        $pdo = new PDO($dsn, $config['username'], $config['password'], $this->options($config));

        if (isset($config['charset'])) {
            $pdo->prepare("SET NAMES '" . $config['charset'] . "'")->execute();
        }

        if (isset($config['schema'])) {
            $pdo->prepare('SET search_path TO ' . $config['schema'])->execute();
        }

        return $pdo;
    }
}
