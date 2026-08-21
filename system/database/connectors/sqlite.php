<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct access.');

use PDO;
use System\Str;

class SQLite extends Connector
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
        return new PDO($this->dsn($config), null, null, $this->options($config));
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
        if (':memory:' === $config['database']) {
            return 'sqlite::memory:';
        }

        return 'sqlite:' . path('storage') . 'database' . DS . $config['database'] . '.sqlite';
    }
}
