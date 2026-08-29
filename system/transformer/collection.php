<?php

namespace System\Transformer;

defined('DS') or exit('No direct access.');

use System\Paginator;
use System\Transformer;

class Collection extends Transformer implements \Countable, \IteratorAggregate
{
    /**
     * Contains the transformer class each item is handed to.
     *
     * @var string
     */
    public $collects;

    /**
     * Contains the transformer of every item.
     *
     * @var array
     */
    public $collection = [];

    /**
     * Constructor.
     *
     * @param mixed  $resource
     * @param string $collects
     */
    public function __construct($resource, $collects = null)
    {
        parent::__construct($resource);

        $this->collects = is_null($collects) ? '\System\Transformer' : $collects;
        $this->collection = $this->transform();
    }

    /**
     * Hand every item of the resource to its own transformer.
     *
     * @return array
     */
    protected function transform()
    {
        $items = $this->items();
        $collects = $this->collects;
        $result = [];

        foreach ($items as $item) {
            $result[] = ($item instanceof Transformer) ? $item : new $collects($item);
        }

        return $result;
    }

    /**
     * Get the items of the resource, whatever shape it arrived in.
     *
     * @return array
     */
    protected function items()
    {
        $resource = $this->resource;

        // The results of a paginator are a collection of their own as often as
        // they are a plain array, so they go through the same reading below.
        if ($resource instanceof Paginator) {
            $resource = $resource->results;
        }

        if (is_object($resource) && method_exists($resource, 'all')) {
            return (array) $resource->all();
        }

        if ($resource instanceof \Traversable) {
            return iterator_to_array($resource);
        }

        return (array) $resource;
    }

    /**
     * Shape every item into the array that goes out.
     *
     * @return array
     */
    public function to_array()
    {
        $result = [];

        foreach ($this->collection as $item) {
            $result[] = $item->filtered();
        }

        return $result;
    }

    /**
     * Get the links and the counts of the paginator behind the collection.
     *
     * @return array
     */
    public function with()
    {
        if (! ($this->resource instanceof Paginator)) {
            return [];
        }

        $paginator = $this->resource;

        return [
            'links' => [
                'first' => $paginator->first_page_url(),
                'last' => $paginator->last_page_url(),
                'prev' => $paginator->previous_page_url(),
                'next' => $paginator->next_page_url(),
            ],
            'meta' => [
                'current_page' => $paginator->page,
                'from' => $paginator->first_item(),
                'last_page' => $paginator->last,
                'path' => $paginator->path(),
                'per_page' => $paginator->perpage,
                'to' => $paginator->last_item(),
                'total' => $paginator->total,
            ],
        ];
    }

    /**
     * Count the items of the collection.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Get an iterator for the transformers of the collection.
     *
     * @return \Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }
}
