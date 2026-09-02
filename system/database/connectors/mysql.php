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
        $pdo = new PDO($this->dsn($config), $config['username'], $config['password'], $this->options($config));

        if (isset($config['charset'])) {
            $charset = (string) $config['charset'];
            // Strict allowlist for charset to prevent injection via config
            // Typical MySQL charsets: utf8, utf8mb4, latin1, ascii, etc.
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $charset)) {
                throw new \InvalidArgumentException(sprintf('Invalid charset: %s', $charset));
            }
            // Use PDO::quote for extra safety, though charset is validated
            $quoted = $pdo->quote($charset);
            // PDO::quote includes surrounding quotes, so we can use it directly
            // But SET NAMES expects quoted string, e.g., SET NAMES 'utf8mb4'
            $pdo->exec("SET NAMES $quoted");
        }

        return $pdo;
    }

    /**
     * Build the DSN string for the connection.
     *
     * @param array $config
     *
     * @return string
     */
    protected function dsn(array $config)
    {
        $dsn = 'mysql:host='.$config['host'].';dbname='.$config['database'];
        $dsn .= isset($config['port']) ? ';port='.$config['port'] : '';
        $dsn .= isset($config['unix_socket']) ? ';unix_socket='.$config['unix_socket'] : '';

        return $dsn;
    }
}
