<?php

namespace System;

defined('DS') or exit('No direct access.');

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $this->get_arrayable_items($items);
    }

    public static function make($items = [])
    {
        return new static($items);
    }

    public function all()
    {
        return $this->items;
    }

    public function avg($callback = null)
    {
        if ($count = $this->count()) {
            return $this->sum($callback) / $count;
        }
    }

    public function average($callback = null)
    {
        return $this->avg($callback);
    }

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
        return array_keys(array_filter($counts, function ($value) use ($max) {
            return $value == $max;
        }));
    }

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

    public function diff($items)
    {
        return new static(array_diff($this->items, $this->get_arrayable_items($items)));
    }

    public function diff_keys($items)
    {
        return new static(array_diff_key($this->items, $this->get_arrayable_items($items)));
    }

    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

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

    public function filter(callable $callback = null)
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

    public function where($key, $operator, $value = null)
    {
        if (func_num_args() == 2) {
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

    public function where_strict($key, $value)
    {
        return $this->where($key, '===', $value);
    }

    public function where_in($key, $values, $strict = false)
    {
        $values = $this->get_arrayable_items($values);
        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    public function where_in_strict($key, $values)
    {
        return $this->where_in($key, $values, true);
    }

    /** @disregard */
    public function first(callable $callback = null, $default = null)
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

    public function flip()
    {
        return new static(array_flip($this->items));
    }

    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return value($default);
    }

    public function group_by($by, $preserve_keys = false)
    {
        $by = $this->value_retriever($by);
        $results = [];

        foreach ($this->items as $key => $value) {
            $gkeys = $by($value, $key);

            if (!is_array($gkeys)) {
                $gkeys = [$gkeys];
            }

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

    public function key_by($by)
    {
        $by = $this->value_retriever($by);
        $results = [];

        foreach ($this->items as $key => $item) {
            $resolved = $by($item, $key);

            if (is_object($resolved)) {
                $resolved = (string) $resolved;
            }

            $results[$resolved] = $item;
        }

        return new static($results);
    }

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

    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->get_arrayable_items($items)));
    }

    public function is_empty()
    {
        return empty($this->items);
    }

    public function is_not_empty()
    {
        return !$this->is_empty();
    }

    protected function use_as_callable($value)
    {
        return !is_string($value) && is_callable($value);
    }

    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /** @disregard */
    public function last(callable $callback = null, $default = null)
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

    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    public function map_with_keys(callable $callback)
    {
        return $this->flat_map($callback);
    }

    public function flat_map(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

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

    public function merge($items)
    {
        return new static(array_merge($this->items, $this->get_arrayable_items($items)));
    }

    public function combine($values)
    {
        return new static(array_combine($this->all(), $this->get_arrayable_items($values)));
    }

    public function union($items)
    {
        return new static($this->items + $this->get_arrayable_items($items));
    }

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

    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::only($this->items, $keys));
    }

    public function for_page($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    public function partition($callback)
    {
        $partitions = [new static, new static];
        $callback = $this->value_retriever($callback);

        foreach ($this->items as $key => $item) {
            $partitions[(int) !$callback($item)][$key] = $item;
        }

        return new static($partitions);
    }

    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    public function pop()
    {
        return array_pop($this->items);
    }

    public function prepend($value, $key = null)
    {
        $this->items = Arr::prepend($this->items, $value, $key);
        return $this;
    }

    public function push($value)
    {
        $this->offsetSet(null, $value);
        return $this;
    }

    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    public function put($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    public function random($amount = 1)
    {
        if ($amount > ($count = $this->count())) {
            throw new \InvalidArgumentException("You requested {$amount} items, but there are only {$count} items in the collection");
        }

        $keys = array_rand($this->items, $amount);

        if (intval($amount) === 1) {
            return $this->items[$keys];
        }

        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

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

    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

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

    public function shift()
    {
        return array_shift($this->items);
    }

    public function shuffle($seed = null)
    {
        $items = $this->items;
        if (is_null($seed)) {
            shuffle($items);
        } else {
            srand($seed);
            usort($items, function () {
                return rand(-1, 1);
            });
        }
        return new static($items);
    }

    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function split($number_of_groups)
    {
        if ($this->is_empty()) {
            return new static;
        }

        return $this->chunk(ceil($this->count() / $number_of_groups));
    }

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

    /** @disregard */
    public function sort(callable $callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);

        return new static($items);
    }

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

    public function sort_by_desc($callback, $options = SORT_REGULAR)
    {
        return $this->sort_by($callback, $options, true);
    }

    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() == 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

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

    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();
        return $this;
    }

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

    public function unique_strict($key = null)
    {
        return $this->unique($key, true);
    }

    public function values()
    {
        return new static(array_values($this->items));
    }

    protected function value_retriever($value)
    {
        if ($this->use_as_callable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

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

    public function to_array()
    {
        return array_map(function ($value) {
            return ($value instanceof \System\Database\Facile\Model) ? $value->to_array() : $value;
        }, $this->items);
    }

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

    public function to_json($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    public function getCachingIterator($flags = \CachingIterator::CALL_TOSTRING)
    {
        return new \CachingIterator($this->getIterator(), $flags);
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }

    public function to_base()
    {
        return new self($this);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    public function __toString()
    {
        return $this->to_json();
    }

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
