<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct access.');

use PDO;

class SQLServer extends Connector
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
    ];

    /**
     * Connect to the database and return the PDO instance.
     *
     * @param array $config
     *
     * @return PDO
     */
    public function connect(array $config)
    {
        return new PDO($this->dsn($config), $config['username'], $config['password'], $this->options($config));
    }

    /**
     * Build the DSN string for the connection.
     *
     * @param array      $config
     * @param array|null $drivers
     *
     * @return string
     */
    protected function dsn(array $config, $drivers = null)
    {
        $drivers = is_null($drivers) ? PDO::getAvailableDrivers() : $drivers;

        // Note: sqlsrv is preferred whenever it is present. The previous check
        // picked dblib as soon as it was available, even alongside sqlsrv. The
        // two also spell the port differently: sqlsrv uses 'Server=host,port'
        // while dblib uses 'host=host:port'.
        if (in_array('sqlsrv', $drivers)) {
            $port = isset($config['port']) ? ',' . $config['port'] : '';
            return 'sqlsrv:Server=' . $config['host'] . $port . ';Database=' . $config['database'];
        }

        $port = isset($config['port']) ? ':' . $config['port'] : '';

        return 'dblib:host=' . $config['host'] . $port . ';dbname=' . $config['database'];
    }
}
