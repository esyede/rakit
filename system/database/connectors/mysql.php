<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct access.');

use PDO;

class MySQL extends Connector
{
    /**
     * Connect to the database and return the PDO instance.
     *
     * @param array $config
     *
     * @return PDO
     */
    public function connect(array $config)
    {
        $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'];
        $dsn .= isset($config['port']) ? ';port=' . $config['port'] : '';
        $dsn .= isset($config['unix_socket']) ? ';unix_socket=' . $config['unix_socket'] : '';

        $pdo = new PDO($dsn, $config['username'], $config['password'], $this->options($config));

        if (isset($config['charset'])) {
            $pdo->prepare("SET NAMES '" . $config['charset'] . "'")->execute();
        }

        return $pdo;
    }
}
