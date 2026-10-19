<?php

namespace System\Database\Query;

defined('DS') or exit('No direct access.');

class Cursor implements \Iterator
{
    /**
     * Contains the callable that runs the query and returns the statement.
     *
     * @var callable
     */
    protected $run;

    /**
     * Contains the callable applied to every row, if any.
     *
     * @var callable|null
     */
    protected $map;

    /**
     * Contains the executed statement.
     *
     * @var \PDOStatement|null
     */
    protected $statement;

    /**
     * Contains the current row, FALSE when exhausted.
     *
     * @var mixed
     */
    protected $row = false;

    /**
     * Contains the position of the current row.
     *
     * @var int
     */
    protected $key = 0;

    /**
     * Constructor.
     *
     * @param callable      $run
     * @param callable|null $map
     */
    public function __construct($run, $map = null)
    {
        $this->run = $run;
        $this->map = $map;
    }

    /**
     * Make a cursor: a generator on PHP 5.5+, an instance otherwise.
     *
     * @param callable      $run
     * @param callable|null $map
     *
     * @return \Generator|Cursor
     */
    public static function make($run, $map = null)
    {
        if (PHP_VERSION_ID < 50500) {
            return new static($run, $map);
        }

        return include dirname(__DIR__) . DS . 'cursor.php';
    }

    /**
     * Fetch the next row from the statement.
     */
    protected function fetch()
    {
        $row = $this->statement->fetch();
        $this->row = (false !== $row && $this->map) ? call_user_func($this->map, $row) : $row;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        if ($this->statement) {
            if ($this->key > 0) {
                throw new \Exception('Cannot rewind a cursor that was already run');
            }

            return;
        }

        $this->statement = call_user_func($this->run);
        $this->fetch();
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return false !== $this->row;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->valid() ? $this->row : null;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        if ($this->valid()) {
            $this->key++;
            $this->fetch();
        }
    }
}
