<?php

namespace System\Database;

defined('DS') or exit('No direct script access.');

use PDO;
use System\Config;
use System\Database;
use System\Event;

class Connection
{
    /**
     * Berisi array konfigurasi koneksi.
     *
     * @var array
     */
    public $config;

    /**
     * Berisi instance kelas PDO.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Berisi isntance kelas query grammar.
     *
     * @var Query\Grammars\Grammar
     */
    protected $grammar;

    /**
     * Berisi catatan seluruh query yang telah dijalankan.
     *
     * @var array
     */
    public static $queries = [];

    /**
     * Buat instance koneksi database baru.
     *
     * @param PDO   $pdo
     * @param array $config
     */
    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * Mulai query builder terhadap sebuah tabel.
     *
     * <code>
     *
     *      // Mulai query builder terhadap tabel 'users'
     *      $query = DB::connection()->table('users');
     *
     *      // Mulai query builder terhadap tabel 'users' dan ambi seluruh datanya
     *      $users = DB::connection()->table('users')->get();
     *
     * </code>
     *
     * @param string $table
     *
     * @return Query
     */
    public function table($table)
    {
        return new Query($this, $this->grammar(), $table);
    }

    /**
     * Buat query grammar baru untuk koneksi saat ini.
     *
     * @return Query\Grammars\Grammar
     */
    protected function grammar()
    {
        if (isset($this->grammar)) {
            return $this->grammar;
        }

        if (isset(Database::$registrar[$this->driver()])) {
            return $this->grammar = Database::$registrar[$this->driver()]['query']();
        }

        switch ($this->driver()) {
            case 'mysql':
                return $this->grammar = new Query\Grammars\MySQL($this);

            case 'sqlite':
                return $this->grammar = new Query\Grammars\SQLite($this);

            case 'sqlsrv':
                return $this->grammar = new Query\Grammars\SQLServer($this);

            case 'pgsql':
                return $this->grammar = new Query\Grammars\Postgres($this);

            default:
                return $this->grammar = new Query\Grammars\Grammar($this);
        }
    }

    /**
     * Jalankan database transaction.
     *
     * @param \Closure $callback
     *
     * @return bool
     */
    public function transaction(\Closure $callback)
    {
        $this->pdo()->beginTransaction();

        try {
            call_user_func($callback);
        } catch (\Throwable $e) {
            $this->pdo()->rollBack();
            throw $e;
        } catch (\Exception $e) {
            $this->pdo()->rollBack();
            throw $e;
        }

        return $this->pdo()->commit();
    }

    /**
     * Jalankan sebuah query terhadap koneksi saat ini dan return hasil sebuah kolom.
     *
     * <code>
     *
     *      // Ambil total baris milik tabel users
     *      $count = DB::connection()->only('SELECT COUNT(*) FROM users');
     *
     *      // Ambil jumlah harga dari tabel foods.
     *      $sum = DB::connection()->only('SELECT SUM(price) FROM foods');
     *
     * </code>
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return mixed
     */
    public function only($sql, array $bindings = [])
    {
        $results = (array) $this->first($sql, $bindings);
        return reset($results);
    }

    /**
     * Jalankan sebuah query terhadap koneksi saat ini dan return hasil pertama.
     *
     * <code>
     *
     *      // Jalankan sebuah query terhadap koneksi
     *      $user = DB::connection()->first('SELECT * FROM users');
     *
     *      // Jalankan sebuah query terhadap koneksi dengan tambahan binding data
     *      $user = DB::connection()->first('SELECT * FROM users WHERE id = ?', [$id]);
     *
     * </code>
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return \stdClass|null
     */
    public function first($sql, array $bindings = [])
    {
        $results = $this->query($sql, $bindings);
        return (count($results) > 0) ? $results[0] : null;
    }

    /**
     * Jalankan sebuah query dan return array berisi objek stdClass.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return array
     */
    public function query($sql, array $bindings = [])
    {
        $sql = trim((string) $sql);
        list($statement, $result) = $this->execute($sql, $bindings);

        if (0 === stripos($sql, 'select') || 0 === stripos($sql, 'show')) {
            return $this->fetch($statement, Config::get('database.fetch'));
        } elseif (0 === stripos($sql, 'update') || 0 === stripos($sql, 'delete')) {
            return $statement->rowCount();
        } elseif (0 === stripos($sql, 'insert') || false !== stripos($sql, 'returning')) {
            return $this->fetch($statement, Config::get('database.fetch'));
        }

        return $result;
    }

    /**
     * Jalankan sebuah query terhadap koneksi saat ini.
     * Akan mereturn array berisi query dan hasil query tersebut (berupa boolean).
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return array
     */
    protected function execute($sql, array $bindings = [])
    {
        $bindings = array_filter($bindings, function ($binding) {
            return !($binding instanceof Expression);
        });

        $bindings = array_values($bindings);
        $sql = $this->grammar()->shortcut($sql, $bindings);

        $datetime = $this->grammar()->datetime;
        $count = count($bindings);

        for ($i = 0; $i < $count; ++$i) {
            if ($bindings[$i] instanceof \DateTime) {
                $bindings[$i] = $bindings[$i]->format($datetime);
            }
        }

        try {
            $start = microtime(true);
            $statement = $this->pdo()->prepare($sql);
            $result = $statement->execute($bindings);
        } catch (\Throwable $e) {
            throw new Failure($sql, $bindings, $e);
        } catch (\Exception $e) {
            throw new Failure($sql, $bindings, $e);
        }

        if (Config::get('debugger.database')) {
            $this->log($sql, $bindings, $start);
        }

        return [$statement, $result];
    }

    /**
     * Ambil seluruh baris untuk statement yang diberikan.
     *
     * @param \PDOStatement $statement
     * @param int           $style
     *
     * @return array
     */
    protected function fetch($statement, $style)
    {
        if (PDO::FETCH_CLASS === $style) {
            return $statement->fetchAll(PDO::FETCH_CLASS, 'stdClass');
        }

        return $statement->fetchAll($style);
    }

    /**
     * Log query dan jalankan event query.
     *
     * @param string $sql
     * @param array  $bindings
     * @param int    $start
     */
    protected function log($sql, array $bindings, $start)
    {
        $time = number_format((microtime(true) - $start) * 1000, 2);

        Event::fire('rakit.query', [$sql, $bindings, $time]);

        static::$queries[] = compact('sql', 'bindings', 'time');
    }

    /**
     * Ambil nama driver database milik koneksi saat ini.
     *
     * @return string
     */
    public function driver()
    {
        return $this->config['driver'];
    }

    /**
     * Ambil object koneksi PDO.
     *
     * @return \PDO
     */
    public function pdo()
    {
        return $this->pdo;
    }

    /**
     * Magic method untuk memulai query ke tabel secara dinamis.
     */
    public function __call($method, array $parameters)
    {
        return $this->table($method);
    }
}
