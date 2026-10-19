# Collection

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Creating Collections](#creating-collections)
-   [Available Methods](#available-methods)
    -   [all()](#all)
    -   [avg() / average()](#avg--average)
    -   [collapse()](#collapse)
    -   [contains()](#contains)
    -   [diff()](#diff)
    -   [each()](#each)
    -   [filter()](#filter)
    -   [first()](#first)
    -   [flatten()](#flatten)
    -   [forget()](#forget)
    -   [get()](#get)
    -   [group_by()](#group_by)
    -   [implode()](#implode)
    -   [is_empty()](#is_empty)
    -   [key_by()](#key_by)
    -   [keys()](#keys)
    -   [last()](#last)
    -   [map()](#map)
    -   [max()](#max)
    -   [median()](#median)
    -   [merge()](#merge)
    -   [min()](#min)
    -   [pluck()](#pluck)
    -   [pop()](#pop)
    -   [prepend()](#prepend)
    -   [pull()](#pull)
    -   [push()](#push)
    -   [put()](#put)
    -   [reduce()](#reduce)
    -   [reject()](#reject)
    -   [reverse()](#reverse)
    -   [search()](#search)
    -   [shuffle()](#shuffle)
    -   [slice()](#slice)
    -   [sort()](#sort)
    -   [sort_by()](#sort_by)
    -   [sum()](#sum)
    -   [take()](#take)
    -   [to_array()](#to_array)
    -   [to_json()](#to_json)
    -   [unique()](#unique)
    -   [values()](#values)
    -   [where()](#where)
    -   [zip()](#zip)
-   [Other Methods](#other-methods)
-   [Array Access & Iteration](#array-access--iteration)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

`Collection` wraps an array in a fluent, chainable API.

```php
collect([1, 2, 3, 4])
    ->filter(function ($v) { return $v > 1; })
    ->map(function ($v) { return $v * 10; })
    ->values()
    ->all();
// [20, 30, 40]
```

Most methods return a new collection and leave the original alone. `forget`, `pop`, `prepend`, `pull`, `push`, `put`, `shift`, `splice` and `transform` modify it in place.

> Methods that filter or reorder (`diff`, `filter`, `reject`, `reverse`, `slice`, `sort`, `take`, `where`, ...) keep the original keys. Call `values()` to reindex. Samples below show values only where keys do not matter.

<a id="creating-collections"></a>

## Creating Collections

```php
$collection = collect(['a', 'b']);            // helper
$collection = new Collection(['a', 'b']);
$collection = Collection::make(['a', 'b']);

$users = DB::table('users')->get();           // query results are collections
```

<a id="available-methods"></a>
<a id="method-reference"></a>

## Available Methods

Samples use this data where a list of records is needed:

```php
$people = collect([
    ['name' => 'John', 'age' => 25, 'dept' => 'IT'],
    ['name' => 'Jane', 'age' => 30, 'dept' => 'HR'],
    ['name' => 'Bob',  'age' => 20, 'dept' => 'IT'],
]);
```

<a id="all"></a>

### all()

Returns the underlying array.

```php
collect(['a', 'b'])->all(); // ['a', 'b']
```

<a id="avg--average"></a>

### avg() / average()

Returns the average of the values, or of a key / callback result. `average()` is an alias.

```php
collect([1, 2, 3, 4, 5])->avg(); // 3
$people->avg('age');              // 25
```

<a id="collapse"></a>

### collapse()

Merges a collection of arrays (or collections) into one.

```php
collect([[1, 2], [3, 4]])->collapse()->all(); // [1, 2, 3, 4]
```

<a id="contains"></a>

### contains()

Checks whether the collection holds a value (loose `==`). Also accepts a key / value pair or a closure.

```php
collect(['a', 'b'])->contains('b');  // true
$people->contains('name', 'Jane');   // true
$people->contains(function ($item) {
    return $item['age'] > 26;
});                                  // true
```

<a id="diff"></a>

### diff()

Returns the values not present in the given array or collection.

```php
collect([1, 2, 3, 4])->diff([2, 4, 6])->values()->all(); // [1, 3]
```

<a id="each"></a>

### each()

Calls a closure with each `($item, $key)`. Return `false` to stop the loop.

```php
$people->each(function ($item, $key) {
    echo $item['name'];
});
```

<a id="filter"></a>

### filter()

Keeps the items for which the closure (`$value, $key`) returns true. Without a closure, removes falsy items.

```php
collect([1, 2, 3, 4, 5])->filter(function ($v) {
    return $v > 3;
})->values()->all(); // [4, 5]
```

<a id="first"></a>

### first()

Returns the first item, or the first one passing a closure. The second argument is the default.

```php
collect([1, 2, 3])->first(); // 1
collect([1, 2, 3])->first(function ($v) { return $v > 1; }); // 2
```

<a id="flatten"></a>

### flatten()

Flattens nested arrays into a single list. The optional argument limits the depth.

```php
collect(['name' => 'John', 'langs' => ['PHP', 'Go']])->flatten()->all();
// ['John', 'PHP', 'Go']
```

<a id="forget"></a>

### forget()

Removes one or more keys, in place.

```php
collect(['name' => 'John', 'age' => 25])->forget('age')->all(); // ['name' => 'John']
```

<a id="get"></a>

### get()

Returns the item at a key, or a default.

```php
collect(['name' => 'John'])->get('email', 'n/a'); // 'n/a'
```

<a id="group_by"></a>

### group_by()

Groups items by a key or closure result. Pass `true` as second argument to keep original keys.

```php
$people->group_by('dept')->all();
// ['IT' => [John, Bob], 'HR' => [Jane]]
```

<a id="implode"></a>

### implode()

Joins the values into a string. For records, pass the key first, then the glue.

```php
collect(['a', 'b', 'c'])->implode(', '); // 'a, b, c'
$people->implode('name', ', ');           // 'John, Jane, Bob'
```

<a id="is_empty"></a>

### is_empty()

Checks whether the collection is empty. See also `is_not_empty()`.

```php
collect()->is_empty(); // true
```

<a id="key_by"></a>

### key_by()

Re-keys the items by a key or closure result.

```php
collect([['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']])->key_by('id')->all();
// [1 => ['id' => 1, 'name' => 'John'], 2 => ['id' => 2, 'name' => 'Jane']]
```

<a id="keys"></a>

### keys()

Returns the keys.

```php
collect(['name' => 'John', 'age' => 25])->keys()->all(); // ['name', 'age']
```

<a id="last"></a>

### last()

Returns the last item, or the last one passing a closure. The second argument is the default.

```php
collect([1, 2, 3])->last(); // 3
```

<a id="map"></a>

### map()

Transforms each item with a closure (`$value, $key`), keeping keys.

```php
collect([1, 2, 3])->map(function ($v) { return $v * 2; })->all(); // [2, 4, 6]
```

<a id="max"></a>

### max()

Returns the largest value, or of a key / callback result.

```php
collect([1, 5, 3])->max(); // 5
$people->max('age');        // 30
```

<a id="median"></a>

### median()

Returns the median value, optionally of a key.

```php
collect([1, 2, 3, 4])->median(); // 2.5
```

<a id="merge"></a>

### merge()

Merges an array or collection (`array_merge` rules).

```php
collect([1, 2])->merge([3, 4])->all(); // [1, 2, 3, 4]
```

<a id="min"></a>

### min()

Returns the smallest value, or of a key / callback result.

```php
collect([1, 5, 3])->min(); // 1
```

<a id="pluck"></a>

### pluck()

Gets the values of a key (dot notation allowed). The optional second argument picks the result keys.

```php
$people->pluck('name')->all();        // ['John', 'Jane', 'Bob']
$people->pluck('age', 'name')->all(); // ['John' => 25, 'Jane' => 30, 'Bob' => 20]
```

<a id="pop"></a>

### pop()

Removes and returns the last item.

```php
$c = collect([1, 2, 3]);
$c->pop(); // 3, $c is now [1, 2]
```

<a id="prepend"></a>

### prepend()

Adds an item to the start, optionally under a key.

```php
collect([2, 3])->prepend(1)->all(); // [1, 2, 3]
```

<a id="pull"></a>

### pull()

Removes and returns the item at a key. The second argument is the default.

```php
$c = collect(['name' => 'John', 'age' => 25]);
$c->pull('age'); // 25, $c is now ['name' => 'John']
```

<a id="push"></a>

### push()

Adds an item to the end.

```php
collect([1, 2])->push(3)->all(); // [1, 2, 3]
```

<a id="put"></a>

### put()

Sets the value at a key.

```php
collect(['name' => 'John'])->put('age', 25)->all(); // ['name' => 'John', 'age' => 25]
```

<a id="reduce"></a>

### reduce()

Reduces the items to one value. The second argument is the initial value.

```php
collect([1, 2, 3])->reduce(function ($carry, $item) {
    return $carry + $item;
}, 0); // 6
```

<a id="reject"></a>

### reject()

The inverse of `filter()`: removes items for which the closure returns true. A non-closure argument removes items equal to it.

```php
collect([1, 2, 3, 4])->reject(function ($v) { return $v > 2; })->all(); // [1, 2]
```

<a id="reverse"></a>

### reverse()

Reverses the order of the items.

```php
collect([1, 2, 3])->reverse()->values()->all(); // [3, 2, 1]
```

<a id="search"></a>

### search()

Returns the key of a value (or of the first item passing a closure), or `false`. Pass `true` as second argument for strict comparison.

```php
collect(['a', 'b', 'c'])->search('b'); // 1
```

<a id="shuffle"></a>

### shuffle()

Returns the items in random order. Pass a seed for a repeatable order.

```php
collect([1, 2, 3, 4, 5])->shuffle()->all(); // [3, 1, 5, 2, 4]
```

<a id="slice"></a>

### slice()

Returns the items from an offset, optionally limited to a length.

```php
collect([1, 2, 3, 4, 5])->slice(2, 2)->values()->all(); // [3, 4]
```

<a id="sort"></a>

### sort()

Sorts by value, or with a `uasort()` comparison closure.

```php
collect([3, 1, 4, 2])->sort()->values()->all(); // [1, 2, 3, 4]
```

<a id="sort_by"></a>

### sort_by()

Sorts by a key or closure result. Strings sort naturally and case-insensitively. Use `sort_by_desc()` for descending order.

```php
$people->sort_by('age')->pluck('name')->all(); // ['Bob', 'John', 'Jane']
```

<a id="sum"></a>

### sum()

Returns the sum of the values, or of a key / callback result.

```php
collect([1, 2, 3])->sum(); // 6
$people->sum('age');        // 75
```

<a id="take"></a>

### take()

Takes items from the start, or from the end with a negative number.

```php
collect([1, 2, 3, 4, 5])->take(3)->all();            // [1, 2, 3]
collect([1, 2, 3, 4, 5])->take(-2)->values()->all(); // [4, 5]
```

<a id="to_array"></a>

### to_array()

Returns a plain array, converting Facile models to arrays.

```php
User::all()->to_array();
```

<a id="to_json"></a>

### to_json()

Encodes the collection as JSON. Takes `json_encode()` options.

```php
collect(['name' => 'John', 'age' => 25])->to_json(); // '{"name":"John","age":25}'
```

<a id="unique"></a>

### unique()

Removes duplicate values, or duplicates by a key / callback result.

```php
collect([1, 2, 2, 3])->unique()->values()->all(); // [1, 2, 3]
$people->unique('dept')->pluck('name')->all();    // ['John', 'Jane']
```

<a id="values"></a>

### values()

Reindexes the keys to `0, 1, 2, ...`.

```php
collect(['a' => 1, 'b' => 2])->values()->all(); // [1, 2]
```

<a id="where"></a>

### where()

Filters items by a key comparison. Operators: `=`, `==`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `===`, `!==`. With two arguments, `=` is used.

```php
$people->where('age', '>', 22)->pluck('name')->all(); // ['John', 'Jane']
$people->where('dept', 'HR')->count();                 // 1
```

<a id="zip"></a>

### zip()

Pairs the items with those of one or more arrays at the same index.

```php
collect(['John', 'Jane'])->zip([25, 30])->to_array();
// [['John', 25], ['Jane', 30]] (each pair is a Collection)
```

<a id="other-methods"></a>

## Other Methods

| Method | Description |
| --- | --- |
| `chunk($size)` | Split into collections of `$size` items |
| `combine($values)` | Use the items as keys and `$values` as values |
| `count()` | Number of items (also `count($collection)`) |
| `diff_keys($items)` | Keep keys not present in `$items` |
| `every($step, $offset = 0)` | Take every `$step`-th item, starting at position `$offset` |
| `except($keys)` | Remove the given keys |
| `flat_map($callback)` | Map, then collapse the results one level |
| `flip()` | Swap keys and values |
| `for_page($page, $per_page)` | The slice for page `$page` |
| `has($key)` | Whether a key exists |
| `intersect($items)` | Keep values also present in `$items` |
| `is_not_empty()` | Opposite of `is_empty()` |
| `map_with_keys($callback)` | Callback returns `[key => value]` pairs, merged into one collection |
| `mode($key = null)` | Most frequent value(s), as a collection |
| `only($keys)` | Keep only the given keys |
| `partition($callback)` | Two collections: passing and failing items |
| `pipe($callback)` | Pass the collection to a closure, return its result |
| `random($amount = 1)` | One random item, or a collection of `$amount` items |
| `shift()` | Remove and return the first item |
| `sort_by_desc($callback, $options)` | `sort_by()` in descending order |
| `splice($offset, $length, $replacement)` | Remove (and optionally replace) a portion in place, return the removed items |
| `split($groups)` | Split into `$groups` collections of roughly equal size |
| `to_base()` | Plain `Collection` from a subclass |
| `transform($callback)` | Like `map()`, but in place |
| `union($items)` | Add items whose keys are missing (`+` operator) |
| `where_in($key, $values, $strict = false)` | Keep items whose `$key` is in `$values` |

Strict (`===`) variants: `contains_strict()`, `where_strict()`, `where_in_strict()`, `unique_strict()`.

<a id="array-access--iteration"></a>

## Array Access & Iteration

`Collection` implements `ArrayAccess`, `Countable`, `IteratorAggregate` and `JsonSerializable`. Casting it to a string returns its JSON.

```php
$c = collect(['name' => 'John', 'age' => 25]);

$c['name'];          // 'John'
isset($c['age']);    // true
count($c);           // 2
foreach ($c as $key => $value) {}
echo json_encode($c); // {"name":"John","age":25}
```
