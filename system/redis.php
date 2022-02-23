<?php

namespace System;

defined('DS') or exit('No direct script access.');

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
        if (! isset(static::$databases[$name])) {
            if (empty($config = Config::get('database.redis.'.$name, []))) {
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
    public function run($method, $parameters)
    {
        fwrite($this->connect(), $this->command($method, (array) $parameters));
        return $this->parse(trim(fgets($this->connection, 512)));
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
        switch (substr($response, 0, 1)) {
            case '-': throw new \Exception(sprintf('Redis error: %s', substr(trim($response), 4)));
            case '+':
            case ':': return $this->inline($response);
            case '$': return $this->bulk($response);
            case '*': return $this->multibulk($response);
            default:  throw new \Exception(sprintf('Unknown response: %s', substr($response, 0, 1)));
        }
    }

    /**
     * Buat koneksi ke Redis server.
     *
     * @return resource
     */
    protected function connect()
    {
        if (! is_null($this->connection)) {
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
    protected function command($method, $parameters)
    {
        $command = '*'.(count($parameters) + 1).CRLF.'$'.strlen($method).CRLF.strtoupper($method).CRLF;

        foreach ($parameters as $parameter) {
            $command .= '$'.strlen($parameter).CRLF.$parameter.CRLF;
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
        return substr(trim($response), 1);
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
        if ('$-1' === $head) {
            return;
        }

        list($read, $response, $size) = [0, '', substr($head, 1)];

        if ($size > 0) {
            do {
                // Hitung dan baca bytes dari respon Redis server (baca per 1024 bytes)
                $block = (($remaining = $size - $read) < 1024) ? $remaining : 1024;
                $response .= fread($this->connection, $block);
                $read += $block;
            } while ($read < $size);
        }

        fread($this->connection, 2);
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
        if ('-1' === ($count = substr($head, 1))) {
            return;
        }

        $response = [];

        for ($i = 0; $i < $count; ++$i) {
            $response[] = $this->parse(trim(fgets($this->connection, 512)));
        }

        return $response;
    }

    /**
     * Tangani pemanggilan method secara dinamis.
     */
    public function __call($method, $parameters)
    {
        return $this->run($method, $parameters);
    }

    /**
     * Tangani pemanggilan static method secara dinamis.
     */
    public static function __callStatic($method, $parameters)
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
