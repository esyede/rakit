<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct access.');

use PDO;

abstract class Connector
{
    /**
     * Contains default PDO connection options.
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
     * Connect to the database and return the PDO instance.
     *
     * @param array $config
     *
     * @return PDO
     */
    abstract public function connect(array $config);

    /**
     * Get the PDO connection options.
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
