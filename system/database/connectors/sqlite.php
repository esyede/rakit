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
        $options = $this->options($config);

        if (':memory:' === $config['database']) {
            return new PDO('sqlite::memory:', null, null, $options);
        }

        $path = path('storage') . 'database' . DS . $config['database'] . '.sqlite';
        return new PDO('sqlite:' . $path, null, null, $options);
    }
}
