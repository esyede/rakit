# Arrays

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [List Helper](#list-helper)
    -   [Arr::accessible\(\)](#arraccessible)
    -   [Arr::add\(\)](#arradd)
    -   [Arr::collapse\(\)](#arrcollapse)
    -   [Arr::cross_join\(\)](#arrcross_join)
    -   [Arr::divide\(\)](#arrdivide)
    -   [Arr::dot\(\)](#arrdot)
    -   [Arr::undot\(\)](#arrundot)
    -   [Arr::except\(\)](#arrexcept)
    -   [Arr::exists\(\)](#arrexists)
    -   [Arr::first\(\)](#arrfirst)
    -   [Arr::flatten\(\)](#arrflatten)
    -   [Arr::forget\(\)](#arrforget)
    -   [Arr::get\(\)](#arrget)
    -   [Arr::has\(\)](#arrhas)
    -   [Arr::has_any\(\)](#arrhas_any)
    -   [Arr::associative\(\)](#arrassociative)
    -   [Arr::sequential\(\)](#arrsequential)
    -   [Arr::last\(\)](#arrlast)
    -   [Arr::only\(\)](#arronly)
    -   [Arr::pluck\(\)](#arrpluck)
    -   [Arr::prepend\(\)](#arrprepend)
    -   [Arr::pull\(\)](#arrpull)
    -   [Arr::random\(\)](#arrrandom)
    -   [Arr::set\(\)](#arrset)
    -   [Arr::shuffle\(\)](#arrshuffle)
    -   [Arr::sort\(\)](#arrsort)
    -   [Arr::recsort\(\)](#arrrecsort)
    -   [Arr::where\(\)](#arrwhere)
    -   [Arr::wrap\(\)](#arrwrap)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

This component includes various helpers to make your life easier when working with arrays.
Here is a list of available helpers:

<a id="list-helper"></a>

## List Helper

Here is the list of helpers available for this component:

<a id="arraccessible"></a>

### Arr::accessible()

This method checks that the given value is an accessible array:

```php
return Arr::accessible(['a' => 1, 'b' => 2]); // true
return Arr::accessible('abc');                // false
return Arr::accessible(new \stdClass());      // false
```

<a id="arradd"></a>

### Arr::add()

This method adds a given key / value pair to an array if the given key
doesn't already exist in the array or is set to `NULL`:

```php
return Arr::add(['name' => 'Desk'], 'price', 100);
// ['name' => 'Desk', 'price' => 100]

return Arr::add(['name' => 'Desk', 'price' => null], 'price', 100);
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrcollapse"></a>

### Arr::collapse()

This method collapses a multi-dimensional array into a single array:

```php
return Arr::collapse([[1, 2, 3], [4, 5, 6], [7, 8, 9]]);
// [1, 2, 3, 4, 5, 6, 7, 8, 9]
```

<a id="arrcross_join"></a>

### Arr::cross_join()

This method performs a cross join on the given arrays, returning all possible combinations:

```php
$sizes = ['S', 'M', 'L'];
$colors = ['red', 'blue'];

$combinations = Arr::cross_join($sizes, $colors);

/**
    [
        ['S', 'red'],
        ['S', 'blue'],
        ['M', 'red'],
        ['M', 'blue'],
        ['L', 'red'],
        ['L', 'blue'],
    ]
*/
```

**Example with 3 arrays:**

```php
$sizes = ['S', 'M'];
$colors = ['red', 'blue'];
$materials = ['cotton', 'polyester'];

$combinations = Arr::cross_join($sizes, $colors, $materials);

/**
    [
        ['S', 'red', 'cotton'],
        ['S', 'red', 'polyester'],
        ['S', 'blue', 'cotton'],
        ['S', 'blue', 'polyester'],
        ['M', 'red', 'cotton'],
        ['M', 'red', 'polyester'],
        ['M', 'blue', 'cotton'],
        ['M', 'blue', 'polyester'],
    ]
*/
```

**Practical use case:**

```php
// Generate product variants
$sizes = ['Small', 'Medium', 'Large'];
$colors = ['Red', 'Green', 'Blue'];

foreach (Arr::cross_join($sizes, $colors) as $variant) {
    list($size, $color) = $variant;
    echo "Product: {$size} - {$color}\n";
}
```

<a id="arrdivide"></a>

### Arr::divide()

This method returns two arrays, one containing the keys, and the other
containing the values of the given array:

```php
list($keys, $values) = Arr::divide(['name' => 'Desk']);
// $keys: ['name']
// $values: ['Desk']
```

<a id="arrdot"></a>

### Arr::dot()

This method flattens a multi-dimensional array into a single array using "dot"
notation to indicate depth:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

return Arr::dot($array); // ['products.desk.price' => 100]
```

<a id="arrundot"></a>

### Arr::undot()

This method converts a single-dimension array with "dot" notation into a multi-dimensional array:

```php
$array = ['user.name' => 'Budi', 'user.age' => 28];

return Arr::undot($array);

// ['user' => ['name' => 'Budi', 'age' => 28]]
```

<a id="arrexcept"></a>

### Arr::except()

This method removes the given key / value pairs from an array:

```php
$array = ['name' => 'Desk', 'price' => 100];

return Arr::except($array, ['price']); // ['name' => 'Desk']
```

<a id="arrexists"></a>

### Arr::exists()

This method checks that the given key exists in an array:

```php
$array = ['name' => 'Agung', 'age' => 17];

return Arr::exists($array, 'name');   // true
return Arr::exists($array, 'salary'); // false
```

<a id="arrfirst"></a>

### Arr::first()

This method returns the first element of an array that passes the given truth test:

```php
$array = [100, 200, 300];

return Arr::first($array, function ($value, $key) {
    return $value >= 150;
});

// 200
```

A default value can also be given as the third parameter to this method. This value will be
returned if no value passes the truth test you provided:

```php
return Arr::first($array, $callback, $default);
```

<a id="arrflatten"></a>

### Arr::flatten()

This method flattens a multi-dimensional array into a single array:

```php
$array = ['name' => 'Dimas', 'languages' => ['PHP', 'Ruby']];

return Arr::flatten($array); // ['Dimas', 'PHP', 'Ruby']
```

<a id="arrforget"></a>

### Arr::forget()

This method removes a given key / value pair from an array using "dot" notation:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::forget($array, 'products.desk');

// return $array; // ['products' => []]
```

<a id="arrget"></a>

### Arr::get()

This method retrieves a value from an array using "dot" notation:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

return Arr::get($array, 'products.desk.price'); // 100
```

This method also accepts a default value, which will be returned if the requested key is not found:

```php
return Arr::get($array, 'products.desk.discount', 0); // 0
```

<a id="arrhas"></a>

### Arr::has()

This method checks whether a given item exists in the array using "dot" notation:

```php
$array = ['product' => ['name' => 'Desk', 'price' => 100]];

return Arr::has($array, 'product.name'); // true
return Arr::has($array, ['product.price', 'product.discount']); // false
```

<a id="arrhas_any"></a>

### Arr::has_any()

This method checks whether any of the given keys exist in an array using "dot" notation:

```php
$array = ['product' => ['name' => 'Desk', 'price' => 100]];

return Arr::has_any($array, 'product.name'); // true
return Arr::has_any($array, ['product.name', 'product.discount']); // true
return Arr::has_any($array, ['category', 'product.discount']); // false
```

<a id="arrassociative"></a>

### Arr::associative()

This method returns `TRUE` if the given array is an associative array. An array
will be considered "associative" if it does not have sequential numeric keys starting from zero:

```php
$array1 = ['product' => ['name' => 'Desk', 'price' => 100]];
$array2 = [1, 2, 3];

return Arr::associative($array1); // true
return Arr::associative($array2); // false
```

<a id="arrsequential"></a>

### Arr::sequential()

This method returns `TRUE` if the given array is a sequential (indexed) array.
An array is considered "sequential" if it has sequential numeric keys starting from zero:

```php
$array1 = [1, 2, 3];
$array2 = ['name' => 'Desk', 'price' => 100];
$array3 = [0 => 'a', 2 => 'b']; // Keys are not sequential

return Arr::sequential($array1); // true
return Arr::sequential($array2); // false
return Arr::sequential($array3); // false
```

**Difference with `Arr::associative()`:**

```php
$sequential = ['a', 'b', 'c']; // Keys: 0, 1, 2
$associative = ['first' => 'a', 'second' => 'b'];
$mixed = [0 => 'a', 'key' => 'b'];

Arr::sequential($sequential);    // true
Arr::associative($sequential);   // false

Arr::sequential($associative);   // false
Arr::associative($associative);  // true

Arr::sequential($mixed);         // false
Arr::associative($mixed);        // true
```

**Practical use case:**

```php
function process_data($data)
{
    if (Arr::sequential($data)) {
        // Process as list/array
        foreach ($data as $item) {
            echo $item . "\n";
        }
    } else {
        // Process as associative array
        foreach ($data as $key => $value) {
            echo "{$key}: {$value}\n";
        }
    }
}

process_data(['a', 'b', 'c']);           // List processing
process_data(['name' => 'John', 'age' => 30]); // Associative processing
```

<a id="arrlast"></a>

### Arr::last()

This method returns the last element of an array that passes the given truth test:

```php
$array = [100, 200, 300, 110];

return Arr::last($array, function ($value, $key) {
    return $value >= 150;
});

// 300
```

A default value can be added as the third parameter to this method.
This value will be returned if no value passes the truth test you provided:

```php
return Arr::last($array, $callback, $default);
```

<a id="arronly"></a>

### Arr::only()

This method returns only the specified key / value pairs from the given array:

```php
$array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];

return Arr::only($array, ['name', 'price']);
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrpluck"></a>

### Arr::pluck()

This method retrieves all values for a given key from an array:

```php
$array = [
    ['developer' => ['id' => 1, 'name' => 'Budi']],
    ['developer' => ['id' => 2, 'name' => 'Sarah']],
];

return Arr::pluck($array, 'developer.name');
// ['Budi', 'Sarah']
```

You can also specify how to shape the resulting array's keys:

```php
return Arr::pluck($array, 'developer.name', 'developer.id');
// [1 => 'Budi', 2 => 'Sarah']
```

<a id="arrprepend"></a>

### Arr::prepend()

This method will add an item to the beginning of an array:

```php
$array = ['one', 'two', 'three', 'four'];

$array = Arr::prepend($array, 'zero');
// ['zero', 'one', 'two', 'three', 'four']
```

If needed, you can also specify which key should be used for the item:

```php
$array = ['price' => 100];

$array = Arr::prepend($array, 'Desk', 'name');
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrpull"></a>

### Arr::pull()

This method returns and removes a key / value pair from an array:

```php
$array = ['name' => 'Desk', 'price' => 100];

$name = Arr::pull($array, 'name');
// $name: 'Desk'
// $array: ['price' => 100]
```

A default value can be given as the third parameter to this method. This value will be
returned if the key you want is not found:

```php
$value = Arr::pull($array, $key, $default);
```

<a id="arrrandom"></a>

### Arr::random()

This method returns a random value from an array:

```php
$array = [1, 2, 3, 4, 5];

return Arr::random($array); // 4 - (obtained randomly)
```

You can also specify how many items should be returned through the third parameter.
Note that if this option is used, the return value will always be an array.

```php
return Arr::random($array, 2); // [2, 5] - (obtained randomly)
```

<a id="arrset"></a>

### Arr::set()

This method is used to set a value in an array using "dot" notation:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::set($array, 'products.desk.price', 200);

// $array: ['products' => ['desk' => ['price' => 200]]]
```

<a id="arrshuffle"></a>

### Arr::shuffle()

This method shuffles the items in an array:

```php
return Arr::shuffle([1, 2, 3, 4, 5]);
// [3, 2, 5, 1, 4] - (made randomly)
```

<a id="arrsort"></a>

### Arr::sort()

This method sorts an array by its values:

```php
$array = ['Desk', 'Table', 'Chair'];

return Arr::sort($array);
// ['Chair', 'Desk', 'Table']
```

You can also sort an array using a Closure:

```php
$array = [
    ['name' => 'Desk'],
    ['name' => 'Table'],
    ['name' => 'Chair'],
];

return array_values(Arr::sort($array, function ($value) {
    return $value['name'];
}));

/**
    [
        ['name' => 'Chair'],
        ['name' => 'Desk'],
        ['name' => 'Table'],
    ]
*/
```

<a id="arrrecsort"></a>

### Arr::recsort()

This method sorts an array recursively using
the [sort](https://php.net/manual/en/function.sort.php) function
for numeric sub-arrays, and [ksort](https://php.net/manual/en/function.ksort.php)
for associative sub-arrays:

```php
$array = [
    ['Roman', 'Budi', 'Li'],
    ['PHP', 'Ruby', 'JavaScript'],
    ['one' => 1, 'two' => 2, 'three' => 3],
];

return Arr::recsort($array);

/**
    [
        ['JavaScript', 'PHP', 'Ruby'],
        ['one' => 1, 'three' => 3, 'two' => 2],
        ['Li', 'Roman', 'Budi'],
    ]
*/
```

<a id="arrwhere"></a>

### Arr::where()

This method is used to filter an array using a Closure:

```php
$array = [100, '200', 300, '400', 500];

return Arr::where($array, function ($value, $key) {
    return is_string($value);
});

// [1 => '200', 3 => '400']
```

<a id="arrwrap"></a>

### Arr::wrap()

This method wraps the given value in an array. If the given value
is already an array, it will not be changed:

```php
$string = 'Bakso';

return Arr::wrap($string); // ['Bakso']
```

If the given value is `NULL`, it will return an empty array:

```php
$empty = null;

return Arr::wrap($empty); // []
```

> Some additional helpers for array operations are also available on the [Helpers](/docs/id/helpers) page.
