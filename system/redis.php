<?php

namespace System;

defined('DS') or exit('No direct access.');

class Redis
{
    /**
     * Berisi host Redis.
     *
     * @var string
     */
    protected $host;

    /**
     * Berisi port Redis.
     *
     * @var int
     */
    protected $port;

    /**
     * Berisi nomor database yang terpilih saat load.
     *
     * @var int
     */
    protected $database;

    /**
     * Berisi koneksi ke Redis.
     *
     * @var resource
     */
    protected $connection;

    /**
     * Berisi list instance database Redis aktif.
     *
     * @var array
     */
    protected static $databases = [];

    /**
     * Buat instance koneksi Redis baru.
     *
     * @param string $host
     * @param string $port
     * @param int    $database
     */
    public function __construct($host, $port, $database = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
    }

    /**
     * Ambil instance koneksi database Redis.
     * Nama yang diberikan harus sesuai dengan data yang ada di file konfigurasi database.
     *
     * <code>
     *
     *      // Ambil instance database default.
     *      $redis = Redis::db();
     *
     *      // Ambil instance database tertentu.
     *      $reids = Redis::db('redis_2');
     *
     * </code>
     *
     * @param string $name
     *
     * @return Redis
     */
    public static function db($name = 'default')
    {
        if (!isset(static::$databases[$name])) {
            $config = Config::get('database.redis.' . $name, []);

            if (empty($config)) {
                throw new \Exception(sprintf('Redis database config is not configured: %s', $name));
            }

            static::$databases[$name] = new static($config['host'], $config['port'], $config['database']);
        }

        return static::$databases[$name];
    }

    /**
     * Eksekusi perintah database Redis.
     *
     * <code>
     *
     *      // Eksekusi perintah GET untuk key 'name'
     *      $name = Redis::db()->run('get', ['name']);
     *
     *      // Eksekusi perintah LRANGE untuk key 'list'
     *      $list = Redis::db()->run('lrange', [0, 5]);
     *
     * </code>
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function run($method, array $parameters)
    {
        fwrite($this->connect(), $this->command($method, $parameters));
        $line = stream_get_line($this->connection, 512, CRLF);

        if ($line === false) {
            throw new \Exception('Failed to read response from Redis server.');
        }

        return $this->parse($line);
    }

    /**
     * Parse dan return respon dari Redis server.
     *
     * @param string $response
     *
     * @return mixed
     */
    protected function parse($response)
    {
        $response = (string) $response;
        $type = substr($response, 0, 1);

        switch ($type) {
            case '-':
                throw new \Exception(sprintf('Redis error: %s', substr(trim($response), 4)));

            case '+':
            case ':':
                return $this->inline($response);

            case '$':
                return $this->bulk($response);

            case '*':
                return $this->multibulk($response);

            default:
                throw new \Exception(sprintf("Unknown response type: '%s'", $type));
        }
    }

    /**
     * Buat koneksi ke Redis server.
     *
     * @return resource
     */
    protected function connect()
    {
        if (!is_null($this->connection)) {
            return $this->connection;
        }

        $this->connection = @fsockopen($this->host, $this->port, $error, $message);

        if (false === $this->connection) {
            throw new \Exception(sprintf('Error making connection: %s - %s', $error, $message));
        }

        $this->select($this->database);
        return $this->connection;
    }

    /**
     * Susun perintah Redis berdasarkan method dan parameter yang diberikan.
     * Perintah-perintah Redis harus mengikuti format berikut:.
     *
     *     *<jumlah argumen> CR LF
     *     $<jumlah bytes milik argumen 1> CR LF
     *     <data argumen> CR LF
     *     ...
     *     $<jumlah bytes milik argumen ke-N> CR LF
     *     <data argumen> CR LF
     *
     * Referensi: http://redis.io/topics/protocol
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return string
     */
    protected function command($method, array $parameters)
    {
        $method = (string) $method;
        $command = '*' . (count($parameters) + 1) . CRLF .
            '$' . mb_strlen($method, '8bit') . CRLF . strtoupper($method) . CRLF;

        foreach ($parameters as $parameter) {
            $command .= '$' . mb_strlen((string) $parameter, '8bit') . CRLF . $parameter . CRLF;
        }

        return $command;
    }

    /**
     * Parse dan tangani respon inline dari database Redis.
     *
     * @param string $response
     *
     * @return string
     */
    protected function inline($response)
    {
        return substr(trim((string) $response), 1);
    }

    /**
     * Parse dan tangani respon bulk dari database Redis.
     *
     * @param string $head
     *
     * @return string
     */
    protected function bulk($head)
    {
        if (strpos((string) $head, '$-1') === 0) {
            return;
        }

        $size = (int) substr((string) $head, 1);

        if ($size === 0) {
            stream_get_line($this->connection, 2, CRLF);
            return '';
        }

        $response = '';
        $remaining = $size;
        while ($remaining > 0) {
            $block = ($remaining < 8192) ? $remaining : 8192;
            $chunk = fread($this->connection, $block);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
            $remaining -= strlen($chunk);
        }

        stream_get_line($this->connection, 2, CRLF);
        return $response;
    }

    /**
     * Parse dan tangani respon multi-bulk dari database Redis.
     *
     * @param string $head
     *
     * @return array
     */
    protected function multibulk($head)
    {
        $count = (int) substr((string) $head, 1);

        if ($count === -1) {
            return;
        }

        $response = [];

        for ($i = 0; $i < $count; ++$i) {
            $line = stream_get_line($this->connection, 512, CRLF);

            if ($line === false) {
                throw new \Exception('Failed to read multibulk element header from Redis server.');
            }

            $response[] = $this->parse($line);
        }

        return $response;
    }

    /**
     * Tangani pemanggilan method secara dinamis.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        return $this->run($method, $parameters);
    }

    /**
     * Tangani pemanggilan static method secara dinamis.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        return static::db()->run($method, $parameters);
    }

    /**
     * Tutup koneksi ke Redis server.
     */
    public function __destruct()
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
        }

        $this->connection = null;
    }
}
