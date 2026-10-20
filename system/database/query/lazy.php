<?php

namespace System\Database\Query;

defined('DS') or exit('No direct access.');

class Lazy implements \Iterator
{
    /**
     * Contains the chunk source.
     *
     * @var callable
     */
    protected $source;

    /**
     * Contains the chunk size.
     *
     * @var int
     */
    protected $size;

    /**
     * Contains the rows of the current chunk.
     *
     * @var array
     */
    protected $rows = [];

    /**
     * Contains the index of the current chunk.
     *
     * @var int
     */
    protected $page = 0;

    /**
     * Contains the position inside the current chunk.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * Contains the number of rows of the previous chunks.
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * Constructor.
     *
     * @param callable $source
     * @param int      $size
     */
    public function __construct($source, $size)
    {
        $this->source = $source;
        $this->size = static::size($size);
    }

    /**
     * Make a lazy iterator over the chunk source: a generator on PHP 5.5+, an instance otherwise.
     *
     * @param callable $source
     * @param int      $size
     *
     * @return \Generator|Lazy
     */
    public static function make($source, $size)
    {
        if (PHP_VERSION_ID < 50500) {
            return new static($source, $size);
        }

        $fetch = $source;
        $size = static::size($size);

        return include dirname(__DIR__) . DS . 'lazy.php';
    }

    /**
     * Run the callback over every chunk of the source, with (chunk collection, page from 1);
     * FALSE stops the iteration.
     *
     * @param callable $source
     * @param int      $size
     * @param callable $callback
     *
     * @return bool
     */
    public static function chunks($source, $size, $callback)
    {
        $page = 0;
        $rows = [];

        do {
            $rows = call_user_func($source, $page++, $rows);
            $count = count($rows);

            if (0 === $count) {
                break;
            }

            if (false === call_user_func($callback, new \System\Collection($rows), $page)) {
                return false;
            }
        } while ($count === $size);

        return true;
    }

    /**
     * Validate the chunk size.
     *
     * @param int $size
     *
     * @return int
     */
    public static function size($size)
    {
        $size = (int) $size;

        if ($size < 1) {
            throw new \InvalidArgumentException('The chunk size should be at least 1');
        }

        return $size;
    }

    /**
     * Fetch the chunk of the given index.
     *
     * @param int   $page
     * @param array $previous
     *
     * @return array
     */
    protected function fetch($page, array $previous)
    {
        return call_user_func($this->source, $page, $previous);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->page = 0;
        $this->index = 0;
        $this->offset = 0;
        $this->rows = $this->fetch(0, []);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->index < count($this->rows);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->valid() ? $this->rows[$this->index] : null;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->offset + $this->index;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->index++;

        if ($this->index >= count($this->rows) && count($this->rows) === $this->size) {
            $this->offset += $this->size;
            $this->index = 0;
            $this->rows = $this->fetch(++$this->page, $this->rows);
        }
    }
}
