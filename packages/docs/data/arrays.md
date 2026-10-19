# Arrays

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Available Methods](#available-methods)
    -   [Arr::accessible\(\)](#arraccessible)
    -   [Arr::add\(\)](#arradd)
    -   [Arr::associative\(\)](#arrassociative)
    -   [Arr::collapse\(\)](#arrcollapse)
    -   [Arr::cross_join\(\)](#arrcross_join)
    -   [Arr::divide\(\)](#arrdivide)
    -   [Arr::dot\(\)](#arrdot)
    -   [Arr::except\(\)](#arrexcept)
    -   [Arr::exists\(\)](#arrexists)
    -   [Arr::first\(\)](#arrfirst)
    -   [Arr::flatten\(\)](#arrflatten)
    -   [Arr::forget\(\)](#arrforget)
    -   [Arr::get\(\)](#arrget)
    -   [Arr::has\(\)](#arrhas)
    -   [Arr::has_any\(\)](#arrhas_any)
    -   [Arr::last\(\)](#arrlast)
    -   [Arr::only\(\)](#arronly)
    -   [Arr::pluck\(\)](#arrpluck)
    -   [Arr::prepend\(\)](#arrprepend)
    -   [Arr::pull\(\)](#arrpull)
    -   [Arr::random\(\)](#arrrandom)
    -   [Arr::recsort\(\)](#arrrecsort)
    -   [Arr::sequential\(\)](#arrsequential)
    -   [Arr::set\(\)](#arrset)
    -   [Arr::shuffle\(\)](#arrshuffle)
    -   [Arr::sort\(\)](#arrsort)
    -   [Arr::undot\(\)](#arrundot)
    -   [Arr::where\(\)](#arrwhere)
    -   [Arr::wrap\(\)](#arrwrap)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

The `Arr` class holds static helpers for working with arrays. Most accept "dot" notation for nested keys. A few more array helpers (`data_get`, `head`, `last`, ...) live on the [Helpers](/docs/helpers#arrays--data) page.

> Callbacks given to `first`, `last` and `where` receive `($key, $value)`, in that order.

<a id="available-methods"></a>
<a id="list-helper"></a>

## Available Methods

<a id="arraccessible"></a>

### Arr::accessible()

Checks whether the value is an array or implements `ArrayAccess`.

```php
Arr::accessible(['a' => 1]);     // true
Arr::accessible(new stdClass()); // false
```

<a id="arradd"></a>

### Arr::add()

Adds a key / value pair if the key is missing or `null`.

```php
Arr::add(['name' => 'Desk', 'price' => null], 'price', 100);
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrassociative"></a>

### Arr::associative()

Checks whether the array is associative, i.e. its keys are not `0, 1, 2, ...`.

```php
Arr::associative(['name' => 'Desk']);    // true
Arr::associative([0 => 'a', 'key' => 'b']); // true
Arr::associative([1, 2, 3]);             // false
```

<a id="arrcollapse"></a>

### Arr::collapse()

Merges an array of arrays into one array.

```php
Arr::collapse([[1, 2], [3, 4], [5]]);
// [1, 2, 3, 4, 5]
```

<a id="arrcross_join"></a>

### Arr::cross_join()

Returns every combination of the given arrays (any number of them).

```php
Arr::cross_join(['S', 'M'], ['red', 'blue']);
// [['S', 'red'], ['S', 'blue'], ['M', 'red'], ['M', 'blue']]
```

<a id="arrdivide"></a>

### Arr::divide()

Returns two arrays: the keys and the values.

```php
list($keys, $values) = Arr::divide(['name' => 'Desk']);
// $keys: ['name'], $values: ['Desk']
```

<a id="arrdot"></a>

### Arr::dot()

Flattens a nested array into one level with "dot" keys.

```php
Arr::dot(['products' => ['desk' => ['price' => 100]]]);
// ['products.desk.price' => 100]
```

<a id="arrexcept"></a>

### Arr::except()

Removes the given keys (dot notation allowed).

```php
Arr::except(['name' => 'Desk', 'price' => 100], ['price']);
// ['name' => 'Desk']
```

<a id="arrexists"></a>

### Arr::exists()

Checks whether a top-level key exists (no dot notation).

```php
Arr::exists(['name' => 'Agung'], 'name');   // true
Arr::exists(['name' => 'Agung'], 'salary'); // false
```

<a id="arrfirst"></a>

### Arr::first()

Returns the first element passing the callback, or the first element when no callback is given. The third argument is the default.

```php
Arr::first([100, 200, 300], function ($key, $value) {
    return $value >= 150;
});
// 200

Arr::first([], null, 'none'); // 'none'
```

<a id="arrflatten"></a>

### Arr::flatten()

Flattens a nested array into a list of values. The optional second argument limits the depth.

```php
Arr::flatten(['name' => 'Dimas', 'languages' => ['PHP', 'Ruby']]);
// ['Dimas', 'PHP', 'Ruby']

Arr::flatten(['a', ['b', ['c']]], 1);
// ['a', 'b', ['c']]
```

<a id="arrforget"></a>

### Arr::forget()

Removes one or more keys from an array in place, using dot notation.

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::forget($array, 'products.desk');
// $array: ['products' => []]
```

<a id="arrget"></a>

### Arr::get()

Gets a value using dot notation, with an optional default.

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::get($array, 'products.desk.price');       // 100
Arr::get($array, 'products.desk.discount', 0); // 0
```

<a id="arrhas"></a>

### Arr::has()

Checks whether a key exists, using dot notation.

```php
$array = ['product' => ['name' => 'Desk']];

Arr::has($array, 'product.name');     // true
Arr::has($array, 'product.discount'); // false
```

<a id="arrhas_any"></a>

### Arr::has_any()

Checks whether at least one of the given keys exists, using dot notation.

```php
Arr::has_any($array, ['product.name', 'product.discount']); // true
Arr::has_any($array, ['category', 'product.discount']);     // false
```

<a id="arrlast"></a>

### Arr::last()

Returns the last element passing the callback, or the last element when no callback is given. The third argument is the default.

```php
Arr::last([100, 200, 300, 110], function ($key, $value) {
    return $value >= 150;
});
// 300
```

<a id="arronly"></a>

### Arr::only()

Keeps only the given top-level keys.

```php
Arr::only(['name' => 'Desk', 'price' => 100, 'orders' => 10], ['name', 'price']);
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrpluck"></a>

### Arr::pluck()

Gets the values of a (dot notation) key from every item. The optional third argument picks the key for each result.

```php
$array = [
    ['developer' => ['id' => 1, 'name' => 'Budi']],
    ['developer' => ['id' => 2, 'name' => 'Sarah']],
];

Arr::pluck($array, 'developer.name');                 // ['Budi', 'Sarah']
Arr::pluck($array, 'developer.name', 'developer.id'); // [1 => 'Budi', 2 => 'Sarah']
```

<a id="arrprepend"></a>

### Arr::prepend()

Adds an item to the start of an array, optionally under a key.

```php
Arr::prepend(['one', 'two'], 'zero');        // ['zero', 'one', 'two']
Arr::prepend(['price' => 100], 'Desk', 'name'); // ['name' => 'Desk', 'price' => 100]
```

<a id="arrpull"></a>

### Arr::pull()

Returns a value and removes it from the array. The third argument is the default.

```php
$array = ['name' => 'Desk', 'price' => 100];

$name = Arr::pull($array, 'name');
// $name: 'Desk', $array: ['price' => 100]
```

<a id="arrrandom"></a>

### Arr::random()

Returns a random value. With a count as second argument, it always returns an array. Asking for more items than exist throws `InvalidArgumentException`.

```php
Arr::random([1, 2, 3, 4, 5]);    // 4
Arr::random([1, 2, 3, 4, 5], 2); // [2, 5]
```

<a id="arrrecsort"></a>

### Arr::recsort()

Sorts an array recursively: [sort](https://www.php.net/sort) for lists, [ksort](https://www.php.net/ksort) for associative arrays.

```php
Arr::recsort([
    ['Roman', 'Budi', 'Li'],
    ['one' => 1, 'two' => 2, 'three' => 3],
]);
// [['one' => 1, 'three' => 3, 'two' => 2], ['Budi', 'Li', 'Roman']]
```

<a id="arrsequential"></a>

### Arr::sequential()

Checks whether the array is a list with keys `0, 1, 2, ...`. The opposite of `associative()` for arrays.

```php
Arr::sequential(['a', 'b', 'c']);       // true
Arr::sequential([0 => 'a', 2 => 'b']);  // false
Arr::sequential(['name' => 'Desk']);    // false
```

<a id="arrset"></a>

### Arr::set()

Sets a value in place using dot notation, creating missing levels.

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::set($array, 'products.desk.price', 200);
// $array: ['products' => ['desk' => ['price' => 200]]]
```

<a id="arrshuffle"></a>

### Arr::shuffle()

Shuffles the items. Pass a seed as second argument for a repeatable order.

```php
Arr::shuffle([1, 2, 3, 4, 5]);     // [3, 2, 5, 1, 4]
Arr::shuffle([1, 2, 3, 4, 5], 42); // same order every time
```

<a id="arrsort"></a>

### Arr::sort()

Sorts by value, keeping keys. Pass a closure or a dot notation key to sort by.

```php
Arr::sort(['Desk', 'Table', 'Chair']);
// [2 => 'Chair', 0 => 'Desk', 1 => 'Table']

Arr::sort([['name' => 'Desk'], ['name' => 'Chair']], 'name');
// [1 => ['name' => 'Chair'], 0 => ['name' => 'Desk']]

Arr::sort($array, function ($value) {
    return $value['name'];
});
```

<a id="arrundot"></a>

### Arr::undot()

Turns a "dot" keyed array back into a nested array.

```php
Arr::undot(['user.name' => 'Budi', 'user.age' => 28]);
// ['user' => ['name' => 'Budi', 'age' => 28]]
```

<a id="arrwhere"></a>

### Arr::where()

Keeps the items passing the callback, preserving keys.

```php
Arr::where([100, '200', 300, '400'], function ($key, $value) {
    return is_string($value);
});
// [1 => '200', 3 => '400']
```

<a id="arrwrap"></a>

### Arr::wrap()

Wraps a value in an array. Arrays are returned unchanged and `null` becomes `[]`.

```php
Arr::wrap('Bakso'); // ['Bakso']
Arr::wrap(null);    // []
```
