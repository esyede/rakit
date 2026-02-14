# Collection

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating Collections](#creating-collections)
- [Method Reference](#method-reference)
  - [all()](#all)
  - [avg() / average()](#avg--average)
  - [collapse()](#collapse)
  - [contains()](#contains)
  - [diff()](#diff)
  - [each()](#each)
  - [filter()](#filter)
  - [first()](#first)
  - [flatten()](#flatten)
  - [forget()](#forget)
  - [get()](#get)
  - [group_by()](#group_by)
  - [key_by()](#key_by)
  - [implode()](#implode)
  - [is_empty()](#is_empty)
  - [keys()](#keys)
  - [last()](#last)
  - [map()](#map)
  - [max()](#max)
  - [median()](#median)
  - [merge()](#merge)
  - [min()](#min)
  - [pluck()](#pluck)
  - [pop()](#pop)
  - [prepend()](#prepend)
  - [pull()](#pull)
  - [push()](#push)
  - [put()](#put)
  - [reduce()](#reduce)
  - [reject()](#reject)
  - [reverse()](#reverse)
  - [search()](#search)
  - [shuffle()](#shuffle)
  - [slice()](#slice)
  - [sort()](#sort)
  - [sort_by()](#sort_by)
  - [sum()](#sum)
  - [take()](#take)
  - [to_array()](#to_array)
  - [to_json()](#to_json)
  - [unique()](#unique)
  - [values()](#values)
  - [where()](#where)
  - [zip()](#zip)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Collection is a wrapper for arrays that provides various methods to work with array data more easily and expressively.

<a id="creating-collections"></a>

## Creating Collections

Collections can be created in various ways:

#### From array:

```php
$collection = new Collection(['item1', 'item2', 'item3']);
```

#### From range:

```php
$collection = Collection::range(1, 10); // [1, 2, 3, ..., 10]
```

#### From database query results:

```php
$users = DB::table('users')->get(); // Returns Collection
```

#### Helper function:

```php
$collection = collect(['item1', 'item2']); // Shortcut for new Collection()
```


<a id="method-reference"></a>

## Method Reference

<a id="all"></a>

### all()

Returns the original array wrapped by the Collection:

```php
$collection = collect(['item1', 'item2']);
$array = $collection->all(); // ['item1', 'item2']
```

<a id="avg--average"></a>

### avg() / average()

Calculates the average of numeric values in the Collection:

```php
$collection = collect([1, 2, 3, 4, 5]);
$average = $collection->avg(); // 3
```

Can also use a specific key:

```php
$collection = collect([
    ['price' => 100],
    ['price' => 200],
    ['price' => 300],
]);
$average = $collection->avg('price'); // 200
```

<a id="collapse"></a>

### collapse()

Flattens a Collection containing arrays into a single Collection:

```php
$collection = collect([[1, 2, 3], [4, 5, 6]]);
$collapsed = $collection->collapse(); // [1, 2, 3, 4, 5, 6]
```

<a id="contains"></a>

### contains()

Checks if the Collection contains a specific item:

```php
$collection = collect(['item1', 'item2', 'item3']);
$collection->contains('item2'); // true
$collection->contains('item4'); // false
```

Can use Closure for complex conditions:

```php
$collection = collect([
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
]);
$collection->contains(function ($item) {
    return $item['age'] > 26;
}); // true
```

<a id="diff"></a>

### diff()

Returns items that do not exist in another Collection:

```php
$collection1 = collect([1, 2, 3, 4]);
$collection2 = collect([2, 4, 6, 8]);
$diff = $collection1->diff($collection2); // [1, 3]
```

<a id="each"></a>

### each()

Iterates over each item in the Collection:

```php
$collection = collect([1, 2, 3]);
$collection->each(function ($item, $key) {
    echo $item . ' ';
}); // Output: 1 2 3
```

<a id="filter"></a>

### filter()

Filters items using a Closure:

```php
$collection = collect([1, 2, 3, 4, 5]);
$filtered = $collection->filter(function ($item) {
    return $item > 3;
}); // [4, 5]
```

<a id="first"></a>

### first()

Returns the first item in the Collection:

```php
$collection = collect([1, 2, 3]);
$first = $collection->first(); // 1
```

Can use Closure for conditions:

```php
$first = $collection->first(function ($item) {
    return $item > 2;
}); // 3
```

<a id="flatten"></a>

### flatten()

Flattens a multi-dimensional Collection:

```php
$collection = collect(['name' => 'John', 'languages' => ['PHP', 'Java']]);
$flattened = $collection->flatten(); // ['John', 'PHP', 'Java']
```

<a id="forget"></a>

### forget()

Removes an item based on key:

```php
$collection = collect(['name' => 'John', 'age' => 25]);
$collection->forget('age'); // Collection now contains only 'name'
```

<a id="get"></a>

### get()

Retrieves an item based on key:

```php
$collection = collect(['name' => 'John', 'age' => 25]);
$name = $collection->get('name'); // 'John'
$default = $collection->get('email', 'not found'); // 'not found'
```

<a id="group_by"></a>

### group_by()

Groups items based on a specific key:

```php
$collection = collect([
    ['name' => 'John', 'department' => 'IT'],
    ['name' => 'Jane', 'department' => 'HR'],
    ['name' => 'Bob', 'department' => 'IT'],
]);
$grouped = $collection->group_by('department');
/**
    [
        'IT' => [['name' => 'John', 'department' => 'IT'], ['name' => 'Bob', 'department' => 'IT']],
        'HR' => [['name' => 'Jane', 'department' => 'HR']],
    ]
*/
```

<a id="key_by"></a>

### key_by()

Uses a specific key as the key for the new Collection:

```php
$collection = collect([
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
]);
$keyed = $collection->key_by('id');
/**
    [
        1 => ['id' => 1, 'name' => 'John'],
        2 => ['id' => 2, 'name' => 'Jane'],
    ]
*/
```

<a id="implode"></a>

### implode()

Joins items into a string:

```php
$collection = collect(['John', 'Jane', 'Bob']);
$imploded = $collection->implode(', '); // 'John, Jane, Bob'
```

For associative arrays:

```php
$collection = collect([
    ['name' => 'John'],
    ['name' => 'Jane'],
]);
$imploded = $collection->implode('name', ', '); // 'John, Jane'
```

<a id="is_empty"></a>

### is_empty()

Checks if the Collection is empty:

```php
$collection = collect([]);
$collection->is_empty(); // true
```

<a id="keys"></a>

### keys()

Returns all keys from the Collection:

```php
$collection = collect(['name' => 'John', 'age' => 25]);
$keys = $collection->keys(); // ['name', 'age']
```

<a id="last"></a>

### last()

Returns the last item in the Collection:

```php
$collection = collect([1, 2, 3]);
$last = $collection->last(); // 3
```

<a id="map"></a>

### map()

Transforms each item using a Closure:

```php
$collection = collect([1, 2, 3]);
$mapped = $collection->map(function ($item) {
    return $item * 2;
}); // [2, 4, 6]
```

<a id="max"></a>

### max()

Returns the maximum value:

```php
$collection = collect([1, 5, 3]);
$max = $collection->max(); // 5
```

<a id="median"></a>

### median()

Calculates the median of the Collection:

```php
$collection = collect([1, 2, 3, 4, 5]);
$median = $collection->median(); // 3
```

<a id="merge"></a>

### merge()

Merges the Collection with an array or another Collection:

```php
$collection1 = collect([1, 2]);
$collection2 = collect([3, 4]);
$merged = $collection1->merge($collection2); // [1, 2, 3, 4]
```

<a id="min"></a>

### min()

Returns the minimum value:

```php
$collection = collect([1, 5, 3]);
$min = $collection->min(); // 1
```

<a id="pluck"></a>

### pluck()

Retrieves values from a specific key:

```php
$collection = collect([
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
]);
$names = $collection->pluck('name'); // ['John', 'Jane']
```

<a id="pop"></a>

### pop()

Removes and returns the last item:

```php
$collection = collect([1, 2, 3]);
$popped = $collection->pop(); // 3, collection now [1, 2]
```

<a id="prepend"></a>

### prepend()

Adds an item at the beginning of the Collection:

```php
$collection = collect([2, 3]);
$collection->prepend(1); // [1, 2, 3]
```

<a id="pull"></a>

### pull()

Removes and returns an item based on key:

```php
$collection = collect(['name' => 'John', 'age' => 25]);
$age = $collection->pull('age'); // 25, collection now only has 'name'
```

<a id="push"></a>

### push()

Adds an item at the end of the Collection:

```php
$collection = collect([1, 2]);
$collection->push(3); // [1, 2, 3]
```

<a id="put"></a>

### put()

Sets a value for a specific key:

```php
$collection = collect(['name' => 'John']);
$collection->put('age', 25); // ['name' => 'John', 'age' => 25]
```

<a id="reduce"></a>

### reduce()

Reduces the Collection to a single value using a Closure:

```php
$collection = collect([1, 2, 3, 4]);
$sum = $collection->reduce(function ($carry, $item) {
    return $carry + $item;
}); // 10
```

<a id="reject"></a>

### reject()

Removes items that pass the Closure condition:

```php
$collection = collect([1, 2, 3, 4]);
$rejected = $collection->reject(function ($item) {
    return $item > 2;
}); // [1, 2]
```

<a id="reverse"></a>

### reverse()

Reverses the order of items:

```php
$collection = collect([1, 2, 3]);
$reversed = $collection->reverse(); // [3, 2, 1]
```

<a id="search"></a>

### search()

Searches for an item and returns its key:

```php
$collection = collect([1, 2, 3]);
$key = $collection->search(2); // 1
```

<a id="shuffle"></a>

### shuffle()

Shuffles the order of items:

```php
$collection = collect([1, 2, 3, 4, 5]);
$shuffled = $collection->shuffle(); // Random order
```

<a id="slice"></a>

### slice()

Takes a subset from the Collection:

```php
$collection = collect([1, 2, 3, 4, 5]);
$sliced = $collection->slice(2, 3); // [3, 4, 5]
```

<a id="sort"></a>

### sort()

Sorts the Collection:

```php
$collection = collect([3, 1, 4, 2]);
$sorted = $collection->sort(); // [1, 2, 3, 4]
```

<a id="sort_by"></a>

### sort_by()

Sorts based on a Closure:

```php
$collection = collect([
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
]);
$sorted = $collection->sort_by('age'); // Sorted by age
```

<a id="sum"></a>

### sum()

Calculates the sum of numeric values:

```php
$collection = collect([1, 2, 3]);
$sum = $collection->sum(); // 6
```

<a id="take"></a>

### take()

Takes a number of items from the beginning or end:

```php
$collection = collect([1, 2, 3, 4, 5]);
$taken = $collection->take(3); // [1, 2, 3]
$taken = $collection->take(-2); // [4, 5]
```

<a id="to_array"></a>

### to_array()

Converts the Collection to an array:

```php
$collection = collect([1, 2, 3]);
$array = $collection->to_array(); // [1, 2, 3]
```

<a id="to_json"></a>

### to_json()

Converts the Collection to JSON:

```php
$collection = collect(['name' => 'John', 'age' => 25]);
$json = $collection->to_json(); // '{"name":"John","age":25}'
```

<a id="unique"></a>

### unique()

Removes duplicates:

```php
$collection = collect([1, 2, 2, 3, 3, 3]);
$unique = $collection->unique(); // [1, 2, 3]
```

<a id="values"></a>

### values()

Resets keys to numeric indices:

```php
$collection = collect(['a' => 1, 'b' => 2]);
$values = $collection->values(); // [1, 2]
```

<a id="where"></a>

### where()

Filters items based on conditions:

```php
$collection = collect([
    ['name' => 'John', 'age' => 25],
    ['name' => 'Jane', 'age' => 30],
]);
$filtered = $collection->where('age', '>', 26); // Jane's record
```

<a id="zip"></a>

### zip()

Combines the Collection with another Collection:

```php
$collection1 = collect(['John', 'Jane']);
$collection2 = collect([25, 30]);
$zipped = $collection1->zip($collection2); // [['John', 25], ['Jane', 30]]
```
