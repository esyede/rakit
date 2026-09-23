<?php

namespace System\Database\Connectors;

defined('DS') or exit('No direct access.');

use PDO;

class Postgres extends Connector
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
        $pdo = new PDO($this->dsn($config), $config['username'], $config['password'], $this->options($config));

        if (isset($config['charset'])) {
            $charset = (string) $config['charset'];
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $charset)) {
                throw new \InvalidArgumentException(sprintf('Invalid charset: %s', $charset));
            }
            $quoted = $pdo->quote($charset);
            $pdo->exec("SET NAMES $quoted");
        }

        if (isset($config['schema'])) {
            $schema = (string) $config['schema'];
            $schemas = array_map('trim', explode(',', $schema));

            foreach ($schemas as $s) {
                $unquoted = trim($s, '"\'');
                if (
                    '' === $unquoted
                    || !preg_match('/^[A-Za-z_][A-Za-z0-9_\$]*$/', $unquoted)
                    && !preg_match('/^"[A-Za-z_][A-Za-z0-9_\$]*"$/', $s)
                ) {
                    if (!preg_match('/^[A-Za-z0-9_,\s"\']+$/', $s)) {
                        throw new \InvalidArgumentException(sprintf('Invalid schema: %s', $s));
                    }

                    if (preg_match('/[;\(\)\-]{2,}|--|\/\*/', $s)) {
                        throw new \InvalidArgumentException(sprintf('Invalid schema: %s', $s));
                    }
                }
            }

            $pdo->exec('SET search_path TO ' . $schema);
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
        $host = isset($config['host']) ? 'host='.$config['host'].';' : '';
        $dsn = 'pgsql:'.$host.'dbname='.$config['database'];
        $dsn .= isset($config['port']) ? ';port='.$config['port'] : '';

        return $dsn;
    }
}
