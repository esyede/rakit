<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct script access.');

use PDO;
use System\Config;
use System\Str;

class SQLite extends Connector
{
    /**
     * Buat koneksi PDO.
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

        $key = Config::get('application.key');

        if (! $key) {
            throw new \Exception('The application key needs to be set before using sqlite database.');
        }

        $path = Str::slug($key).'-'.$config['database'];
        $path = path('storage').'database'.DS.$path;

        return new PDO('sqlite:'.$path, null, null, $options);
    }
}
