<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use PDO;
use System\Event;
use System\Config;
use System\Database;
use System\Database\Exceptions\QueryException;

class Connection
{
    /**
     * Contans database configuration.
     *
     * @var array
     */
    public $config;

    /**
     * Contans PDO connection instance.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Contains query grammar instance.
     *
     * @var Query\Grammars\Grammar
     */
    protected $grammar;

    /**
     * Contans logged queries.
     *
     * @var array
     */
    public static $queries = [];

    /**
     * Constructor.
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
     * Start a new query builder against a table.
     *
     * <code>
     *
     *      // Start a new query builder against the 'users' table
     *      $query = DB::connection()->table('users');
     *
     *      // Start a new query builder against the 'users' table and get all data
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
     * Create a new instance of the query grammar.
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
            case 'mysql':  return $this->grammar = new Query\Grammars\MySQL($this);
            case 'sqlite': return $this->grammar = new Query\Grammars\SQLite($this);
            case 'sqlsrv': return $this->grammar = new Query\Grammars\SQLServer($this);
            case 'pgsql':  return $this->grammar = new Query\Grammars\Postgres($this);
            default:       return $this->grammar = new Query\Grammars\Grammar($this);
        }
    }

    /**
     * Run the database transaction.
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            $this->pdo()->rollBack();
            throw $e;
        }

        return $this->pdo()->commit();
    }

    /**
     * Run the query and return a single value from the first column of the first row.
     *
     * <code>
     *
     *      // Get the number of users from the users table.
     *      $count = DB::connection()->only('SELECT COUNT(*) FROM users');
     *
     *      // Get the sum of prices from the foods table.
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
     * Run the query and return the first row of the result.
     *
     * <code>
     *
     *      // Run a query against the connection
     *      $user = DB::connection()->first('SELECT * FROM users');
     *
     *      // Run a query against the connection with additional binding data
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
     * Run the query and return an array of stdClass objects.
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
     * Run the query against the connection.
     * Will return an array containing the query and the result of the query (as a boolean).
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
            } elseif (is_bool($bindings[$i])) {
                $bindings[$i] = intval($bindings[$i]);
            }
        }

        try {
            $start = microtime(true);
            $statement = $this->pdo()->prepare($sql);
            $result = $statement->execute($bindings);
        } catch (\Throwable $e) {
            throw new QueryException($this->driver(), $sql, $bindings, $e);
        } catch (\Exception $e) {
            throw new QueryException($this->driver(), $sql, $bindings, $e);
        }

        if (Config::get('debugger.database')) {
            $this->log($sql, $bindings, $start);
        }

        return [$statement, $result];
    }

    /**
     * Fetch all rows from the executed statement.
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
     * Log the executed query.
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
     * Get current database driver.
     *
     * @return string
     */
    public function driver()
    {
        return $this->config['driver'];
    }

    /**
     * Get the PDO connection instance.
     *
     * @return \PDO
     */
    public function pdo()
    {
        return $this->pdo;
    }

    /**
     * Handle dynamic method calls to the connection instance.
     */
    public function __call($method, array $parameters)
    {
        return $this->table($method);
    }
}
