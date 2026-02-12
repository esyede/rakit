<?php

namespace System;

defined('DS') or exit('No direct access.');

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /** The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Constructor..
     *
     * @param mixed $items
     *
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $this->get_arrayable_items($items);
    }

    /**
     * Create a new collection instance.
     *
     * @param mixed $items
     *
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return float|int|null
     */
    public function avg($callback = null)
    {
        if ($count = $this->count()) {
            return $this->sum($callback) / $count;
        }
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return float|int|null
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * Get the median of a given key.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function median($key = null)
    {
        $count = $this->count();

        if ($count === 0) {
            return;
        }

        $values = isset($key) ? $this->pluck($key)->sort()->values() : $this->sort()->values();
        $middle = (int) ($count / 2);

        if ($count % 2) {
            return $values->get($middle);
        }

        return (new static([$values->get($middle - 1), $values->get($middle)]))->average();
    }

    /**
     * Get the mode of a given key.
     *
     * @param string|null $key
     *
     * @return static|null
     */
    public function mode($key = null)
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        $collection = isset($key) ? $this->pluck($key) : $this;

        $counts = [];
        $collection->each(function ($value) use (&$counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });

        $max = max($counts);
        $modes = array_keys(array_filter($counts, function ($value) use ($max) {
            return $value == $max;
        }));

        return new static($modes);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        $results = [];

        foreach ($this->items as $values) {
            if ($values instanceof \System\Collection) {
                $values = $values->all();
            } elseif ($values instanceof \System\Database\Facile\Model) {
                $values = $values->to_array();
            } elseif ($values instanceof \stdClass) {
                $values = (array) $values;
            }

            if (is_array($values)) {
                $results = array_merge($results, $values);
            }
        }

        return new static($results);
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($key, $value = null)
    {
        if (func_num_args() === 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return data_get($item, $key) == $value;
            });
        }

        if ($this->use_as_callable($key)) {
            return !is_null($this->first($key));
        }

        return in_array($key, $this->items, false);
    }

    /**
     * Determine if an item exists in the collection (strict comparison).
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function contains_strict($key, $value = null)
    {
        if (func_num_args() === 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return data_get($item, $key) === $value;
            });
        }

        if ($this->use_as_callable($key)) {
            return !is_null($this->first($key));
        }

        return in_array($key, $this->items, true);
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->get_arrayable_items($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function diff_keys($items)
    {
        return new static(array_diff_key($this->items, $this->get_arrayable_items($items)));
    }

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Get every n-th item from the collection.
     *
     * @param int $step
     * @param int $offset
     *
     * @return static
     */
    public function every($step, $offset = 0)
    {
        $new = [];
        $position = 0;

        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::except($this->items, $keys));
    }

    /**
     * Filter the collection using the given callback.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function filter($callback = null)
    {
        if ($callback) {
            $results = [];

            foreach ($this->items as $key => $value) {
                if ($callback($value, $key)) {
                    $results[$key] = $value;
                }
            }

            return new static($results);
        }

        return new static(array_filter($this->items));
    }

    /**
     * Filter the collection by a given key / value pair.
     *
     * @param string $key
     * @param string $operator
     * @param mixed  $value
     *
     * @return static
     */
    public function where($key, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        });
    }

    /**
     * Filter the collection by a given key / value pair (strict comparison).
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     */
    public function where_strict($key, $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filter the collection by a given key / array of values.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return static
     */
    public function where_in($key, $values, $strict = false)
    {
        $values = $this->get_arrayable_items($values);
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter the collection by a given key / array of values (strict comparison).
     *
     * @param string $key
     * @param mixed  $values
     */
    public function where_in_strict($key, $values)
    {
        return $this->where_in($key, $values, true);
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function first($callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return value($default);
            }

            return reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Flatten a multi-dimensional collection into a single dimension.
     *
     * @param int $depth
     *
     * @return static
     */
    public function flatten($depth = INF)
    {
        return new static($this->flatten_items($this->items, $depth));
    }

    protected function flatten_items(array $items, $depth)
    {
        $result = [];

        foreach ($items as $item) {
            $item = ($item instanceof \System\Collection) ? $item->all() : $item;

            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, $this->flatten_items($item, $depth - 1));
            }
        }

        return $result;
    }

    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param mixed $keys
     *
     * @return $this
     */
    public function forget($keys)
    {
        $keys = (array) $keys;

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->offsetExists($key) ? $this->items[$key] : value($default);
    }

    /**
     * Group the collection's items by a given key.
     *
     * @param callable|string $by
     * @param bool            $preserve_keys
     *
     * @return static
     */
    public function group_by($by, $preserve_keys = false)
    {
        $by = $this->value_retriever($by);
        $results = [];

        foreach ($this->items as $key => $value) {
            $gkeys = $by($value, $key);
            $gkeys = is_array($gkeys) ? $gkeys : [$gkeys];

            foreach ($gkeys as $gkey) {
                if (!array_key_exists($gkey, $results)) {
                    $results[$gkey] = [];
                }

                if ($preserve_keys) {
                    $results[$gkey][$key] = $value;
                } else {
                    $results[$gkey][] = $value;
                }
            }
        }

        return new static($results);
    }

    /**
     * Key the collection by the given key.
     *
     * @param callable|string $by
     *
     * @return static
     */
    public function key_by($by)
    {
        $by = $this->value_retriever($by);
        $results = [];

        foreach ($this->items as $key => $item) {
            $resolved = $by($item, $key);
            $resolved = is_object($resolved) ? (string) $resolved : $resolved;
            $results[$resolved] = $item;
        }

        return new static($results);
    }

    /**
     * Determine if an item exists at a given key.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    public function implode($value, $glue = null)
    {
        $first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    /**
     * Get the items in the collection that are present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->get_arrayable_items($items)));
    }

    /**
     * Get the items in the collection whose keys are present in the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function is_empty()
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function is_not_empty()
    {
        return !$this->is_empty();
    }

    /**
     * Determine if the given value should be used as a callable.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function use_as_callable($value)
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     * Get all of the keys in the collection.
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function last($callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($this->items) ? value($default) : end($this->items);
        }

        $result = $default;
        $reversed = array_reverse($this->items, true);

        foreach ($reversed as $key => $value) {
            if ($callback($value, $key)) {
                $result = $value;
                break;
            }
        }

        return value($result);
    }

    /**
     * Pluck an array of values from the collection.
     *
     * @param string      $value
     * @param string|null $key
     *
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Map a collection's items through a callback.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    /**
     * Map a collection's items through a callback, preserving keys.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function map_with_keys(callable $callback)
    {
        return $this->flat_map($callback);
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function flat_map(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Get the maximum value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->value_retriever($callback);
        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->get_arrayable_items($items)));
    }

    /**
     * Combine the collection's values as keys with the given values.
     *
     * @param mixed $values
     *
     * @return static
     */
    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->get_arrayable_items($values)));
    }

    /**
     * Get the union of the collection with the given items.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->get_arrayable_items($items));
    }

    /**
     * Get the minimum value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->value_retriever($callback);
        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);
            return (is_null($result) || $value < $result) ? $value : $result;
        });
    }

    /**
     * Get only the items in the collection with the specified keys.
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::only($this->items, $keys));
    }

    /**
     * Get the items for the given page.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return static
     */
    public function for_page($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    /**
     * Partition the collection into two arrays using the given callback.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function partition($callback)
    {
        $partitions = [new static, new static];
        $callback = $this->value_retriever($callback);

        foreach ($this->items as $key => $item) {
            $partitions[(int) !$callback($item)][$key] = $item;
        }

        return new static($partitions);
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Remove and return the last item from the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Prepend an item to the beginning of the collection.
     *
     * @param mixed      $value
     * @param mixed|null $key
     *
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $this->items = Arr::prepend($this->items, $value, $key);
        return $this;
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);
        return $this;
    }

    /**
     * Remove and return an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Set the item at a given key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * Get one or more random items from the collection.
     *
     * @param int $amount
     *
     * @return mixed
     */
    public function random($amount = 1)
    {
        if ($amount > ($count = $this->count())) {
            throw new \InvalidArgumentException('You requested ' . $amount . ' items, but there are only ' . $count . ' items in the collection.');
        }

        $keys = array_rand($this->items, $amount);

        if (intval($amount) === 1) {
            return $this->items[$keys];
        }

        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Reject items from the collection using the given callback.
     *
     * @param callable|mixed $callback
     *
     * @return static
     */
    public function reject($callback)
    {
        if ($this->use_as_callable($callback)) {
            return $this->filter(function ($value, $key) use ($callback) {
                return !$callback($value, $key);
            });
        }

        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }

    /**
     * Reverse the order of the collection's items.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Search the collection for a given value and return its key if found.
     *
     * @param mixed $value
     * @param bool  $strict
     *
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (!$this->use_as_callable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Remove and return the first item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Shuffle the items in the collection.
     *
     * @param int|null $seed
     *
     * @return static
     */
    public function shuffle($seed = null)
    {
        $items = $this->items;

        if (is_null($seed)) {
            shuffle($items);
        } else {
            mt_srand($seed);
            usort($items, function () {
                return mt_rand(-1, 1);
            });
        }

        return new static($items);
    }

    /**
     * Get a slice of the collection.
     *
     * @param int      $offset
     * @param int|null $length
     *
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Split the collection into the given number of groups.
     *
     * @param int $number_of_groups
     *
     * @return static
     */
    public function split($number_of_groups)
    {
        return $this->is_empty() ? new static() : $this->chunk(ceil($this->count() / $number_of_groups));
    }

    /**
     * Chunk the collection into smaller collections of a given size.
     *
     * @param int $size
     *
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Sort the collection.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function sort($callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);
        return new static($items);
    }

    /**
     * Sort the collection by the given key.
     *
     * @param callable|string $callback
     * @param int             $options
     * @param bool            $descending
     *
     * @return static
     */
    public function sort_by($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];
        $callback = $this->value_retriever($callback);

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        if (is_string($results[key($results)])) {
            $options = SORT_NATURAL | SORT_FLAG_CASE;
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * Sort the collection by the given key in descending order.
     *
     * @param callable|string $callback
     * @param int             $options
     *
     * @return static
     */
    public function sort_by_desc($callback, $options = SORT_REGULAR)
    {
        return $this->sort_by($callback, $options, true);
    }

    /**
     * Splice a portion of the collection.
     *
     * @param int      $offset
     * @param int|null $length
     * @param array    $replacement
     *
     * @return static
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() == 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Get the sum of the collection.
     *
     * @param callable|string|null $callback
     *
     * @return float|int
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        $callback = $this->value_retriever($callback);
        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Take the first or last given number of items from the collection.
     *
     * @param int $limit
     *
     * @return static
     */
    public function take($limit)
    {
        return ($limit < 0) ? $this->slice($limit, abs($limit)) : $this->slice(0, $limit);
    }

    /**
     * Transform each item in the collection using the given callback.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();
        return $this;
    }

    /**
     * Get only the unique items from the collection.
     *
     * @param callable|string|null $key
     * @param bool                 $strict
     *
     * @return static
     */
    public function unique($key = null, $strict = false)
    {
        if (is_null($key)) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $callback = $this->value_retriever($key);
        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        });
    }

    /**
     * Get only the unique items from the collection (strict comparison).
     *
     * @param callable|string|null $key
     *
     * @return static
     */
    public function unique_strict($key = null)
    {
        return $this->unique($key, true);
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Get a value retriever callback.
     *
     * @param callable|string|null $value
     *
     * @return callable
     */
    protected function value_retriever($value)
    {
        if ($this->use_as_callable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * @param mixed $items
     *
     * @return static
     */
    public function zip($items)
    {
        $arrayables = array_map(function ($items) {
            return $this->get_arrayable_items($items);
        }, func_get_args());

        $params = array_merge([function () {
            return new static(func_get_args());
        }, $this->items], $arrayables);

        return new static(call_user_func_array('array_map', $params));
    }

    /**
     * Convert the collection to a plain array.
     *
     * @return array
     */
    public function to_array()
    {
        return array_map(function ($value) {
            return ($value instanceof \System\Database\Facile\Model) ? $value->to_array() : $value;
        }, $this->items);
    }

    /**
     * Convert the collection to JSON.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof \System\Database\Facile\Model) {
                return $value->to_array();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Convert the collection to JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function to_json($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Get a CachingIterator for the items.
     *
     * @param int $flags
     *
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = \CachingIterator::CALL_TOSTRING)
    {
        return new \CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Get the number of items in the collection.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }

    /**
     * Create a base collection instance if applicable.
     *
     * @return static
     */
    public function to_base()
    {
        return new self($this);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->to_json();
    }

    /**
     * Get the items as an array.
     *
     * @param mixed $items
     *
     * @return array
     */
    protected function get_arrayable_items($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();
        } elseif ($items instanceof \System\Database\Facile\Model) {
            return $items->to_array();
        } elseif ($items instanceof \JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof \Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }
}
