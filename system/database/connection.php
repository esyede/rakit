<?php

namespace System\Database;

defined('DS') or exit('No direct access.');

use PDO;
use System\Hook;
use System\Config;
use System\Database;
use System\Exceptions\QueryException;

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
     * Number of transactions that are currently open. Anything beyond the
     * first one is handled with a savepoint instead of a real transaction.
     *
     * @var int
     */
    protected $transactions = 0;

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

        if (isset(Database::$registrar[$this->driver()]['query'])) {
            $resolver = Database::$registrar[$this->driver()]['query'];
            return $this->grammar = is_string($resolver) ? new $resolver($this) : $resolver($this);
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
     * Run the database transaction.
     *
     * @param \Closure $callback
     *
     * @return bool
     */
    public function transaction(\Closure $callback)
    {
        $this->begin_transaction();

        try {
            $result = call_user_func($callback, $this);
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        $this->commit();

        return $result;
    }

    /**
     * Open a transaction. Calling it again while one is already open opens a
     * savepoint instead, so a method that wraps its work in a transaction may
     * safely be called from inside another one.
     *
     * @return bool
     */
    public function begin_transaction()
    {
        if (0 === $this->transactions) {
            $this->pdo()->beginTransaction();
        } else {
            $this->savepoint($this->grammar()->savepoint($this->savepoint_name($this->transactions + 1)));
        }

        ++$this->transactions;

        return true;
    }

    /**
     * Commit the transaction. When it is a nested one, only its savepoint is
     * released and the outermost transaction stays open.
     *
     * @return bool
     */
    public function commit()
    {
        if (0 === $this->transactions) {
            return false;
        }

        if (1 === $this->transactions) {
            $this->pdo()->commit();
        } else {
            $this->savepoint($this->grammar()->release_savepoint($this->savepoint_name($this->transactions)));
        }

        --$this->transactions;

        return true;
    }

    /**
     * Roll the transaction back. When it is a nested one, only the work done
     * since its savepoint is undone and the outermost transaction stays open.
     * Rolling back without an open transaction is not an error, it simply does
     * nothing, so that it is safe to call from an error handler.
     *
     * @return bool
     */
    public function rollback()
    {
        if (0 === $this->transactions) {
            return false;
        }

        try {
            if (1 === $this->transactions) {
                $this->pdo()->rollBack();
            } else {
                $this->savepoint($this->grammar()->rollback_savepoint($this->savepoint_name($this->transactions)));
            }
        } catch (\Throwable $e) {
            $this->transactions = 0;
            throw $e;
        } catch (\Exception $e) {
            $this->transactions = 0;
            throw $e;
        }

        --$this->transactions;

        return true;
    }

    /**
     * Get how many transactions are currently open.
     *
     * @return int
     */
    public function transaction_level()
    {
        return $this->transactions;
    }

    /**
     * Run a savepoint statement, unless the driver has nothing to run.
     *
     * @param string $sql
     */
    protected function savepoint($sql)
    {
        if ('' !== trim((string) $sql)) {
            $this->pdo()->exec($sql);
        }
    }

    /**
     * Get the name of the savepoint of the given nesting level.
     *
     * @param int $level
     *
     * @return string
     */
    protected function savepoint_name($level)
    {
        return 'rakit_savepoint_' . (int) $level;
    }

    /**
     * Run the query and return a single value from the first column of the first row.
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
            return ! ($binding instanceof Expression);
        });

        $bindings = array_values($bindings);
        $sql = $this->grammar()->shortcut($sql, $bindings);

        $datetime = $this->grammar()->datetime;
        $count = count($bindings);

        for ($i = 0; $i < $count; ++$i) {
            if ($bindings[$i] instanceof \DateTime) {
                $bindings[$i] = $bindings[$i]->format($datetime);
            } elseif (is_bool($bindings[$i])) {
                $bindings[$i] = (int) ($bindings[$i]);
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
        $source = $this->source();

        Hook::fire('rakit.query', [$sql, $bindings, $time]);

        $record = compact('sql', 'bindings', 'time', 'source');
        $record['start'] = defined('RAKIT_START') ? ($start - RAKIT_START) * 1000 : 0;

        static::$queries[] = $record;
    }

    /**
     * Determine the application file:line that issued the current query.
     * Internal framework frames (folder 'system/') are skipped so the source
     * points to the developer's controller/model/route, not the query builder.
     *
     * @return string|null
     */
    protected function source()
    {
        $system = dirname(__DIR__) . DS;
        $base = dirname($system);
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($frames as $frame) {
            if (! isset($frame['file'])) {
                continue;
            }

            $file = $frame['file'];

            if (0 === strpos($file, $system)) {
                continue;
            }

            $line = isset($frame['line']) ? $frame['line'] : 0;

            if (0 === strpos($file, $base . DS)) {
                $file = substr($file, strlen($base) + 1);
            }

            return $file . ':' . $line;
        }
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
        if (! $this->pdo instanceof PDO) {
            throw new \Exception('This database connection has been closed. Reopen it with DB::reconnect(), or ask DB::connection() for it again.');
        }

        return $this->pdo;
    }

    /**
     * Determine if the connection is still open.
     *
     * @return bool
     */
    public function connected()
    {
        return $this->pdo instanceof PDO;
    }

    /**
     * Close the connection to the database. Whatever an open transaction had
     * done so far is rolled back by the server when the connection goes. A
     * persistent connection goes back to the PDO pool instead of closing, and
     * the next one opened is the same connection again.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->pdo = null;
        $this->transactions = 0;
    }

    /**
     * Replace the PDO instance the queries run on.
     *
     * @param PDO $pdo
     *
     * @return $this
     */
    public function set_pdo(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->transactions = 0;

        return $this;
    }

    /**
     * Handle dynamic method calls to the connection instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return \System\Database\Query
     */
    public function __call($method, array $parameters)
    {
        return $this->table($method);
    }
}
