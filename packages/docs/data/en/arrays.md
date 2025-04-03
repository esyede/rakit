# Array

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Helper List](#helper-list)
    -   [Arr::accessible\(\)](#arraccessible)
    -   [Arr::add\(\)](#arradd)
    -   [Arr::collapse\(\)](#arrcollapse)
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
    -   [Arr::associative\(\)](#arrassociative)
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

These components include various helpers to make your life easier when working with array.
This is the list of available helpers for you:

<a id="helper-list"></a>

## Helper List

This is the list of available helpers for this component:

<a id="arraccessible"></a>

### Arr::accessible()

This method check whether given value is an accessible array:

```php
return Arr::accessible(['a' => 1, 'b' => 2]); // true
return Arr::accessible('abc');                // false
return Arr::accessible(new \stdClass());      // false
```

<a id="arradd"></a>

### Arr::add()

This method add certain key/value pair to the array if given key is not exist in the array or set to `NULL`:

```php
return Arr::add(['name' => 'Desk'], 'price', 100);
// ['name' => 'Desk', 'price' => 100]

return Arr::add(['name' => 'Desk', 'price' => null], 'price', 100);
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrcollapse"></a>

### Arr::collapse()

This method shrink multi-dimension array into single array:

```php
return Arr::collapse([[1, 2, 3], [4, 5, 6], [7, 8, 9]]);
// [1, 2, 3, 4, 5, 6, 7, 8, 9]
```

<a id="arrdivide"></a>

### Arr::divide()

This method return two array, one contain the key, and the other contain the value of given array:

```php
list($keys, $values) = Arr::divide(['name' => 'Desk']);
// $keys: ['name']
// $values: ['Desk']
```

<a id="arrdot"></a>

### Arr::dot()

This method will shrink multi-dimension array into single array using "dot" notation to show array depth:

```php

$array = ['products' => ['desk' => ['price' => 100]]];

return Arr::dot($array); // ['products.desk.price' => 100]
```

<a id="arrexcept"></a>

### Arr::except()

This method remove certain key/value pair from the array:

```php
$array = ['name' => 'Desk', 'price' => 100];

return Arr::except($array, ['price']); // ['name' => 'Desk']
```

<a id="arrexists"></a>

### Arr::exists()

This method check whether given key exist in an array:

```php
$array = ['name' => 'Agung', 'age' => 17];

return Arr::exists($array, 'name');   // true
return Arr::exists($array, 'salary'); // false
```

<a id="arrfirst"></a>

### Arr::first()

This method return first element of an array which passed given validity test:

```php
$array = [100, 200, 300];

return Arr::first($array, function ($value, $key) {
    return $value >= 150;
});

// 200
```

You can pass default value as the third argument of this method.
This value will be returned if there is no value passed the given validity test:

```php
return Arr::first($array, $callback, $default);
```

<a id="arrflatten"></a>

### Arr::flatten()

This method flatten multi-dimension array into a single array:

```php
$array = ['name' => 'Dimas', 'languages' => ['PHP', 'Ruby']];

return Arr::flatten($array); // ['Dimas', 'PHP', 'Ruby']
```

<a id="arrforget"></a>

### Arr::forget()

This method remove certain key/value pair from an array using "dot" notation:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::forget($array, 'products.desk');

return $array; // ['products' => []]
```

<a id="arrget"></a>

### Arr::get()

This method take one value from an array using "dot" notation:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

return Arr::get($array, 'products.desk.price'); // 100
```

This method also accept a default value, which will be returned if expected key is not found:

```php
return Arr::get($array, 'products.desk.discount', 0); // 0
```

<a id="arrhas"></a>

### Arr::has()

This method check whether certain item exist in an array using "dot" notation:

```php

$array = ['product' => ['name' => 'Desk', 'price' => 100]];

return Arr::has($array, 'product.name'); // true
return Arr::has($array, ['product.price', 'product.discount']); // false
```

<a id="arrhas_any"></a>

### Arr::has_any()

This method checks whether any item in a given set exists in an array using "dot" notation:

```php
$array = ['product' => ['name' => 'Desk', 'price' => 100]];

return Arr::has_any($array, 'product.name'); // true
return Arr::has_any($array, ['product.name', 'product.discount']); // true
return Arr::has_any($array, ['category', 'product.discount']); // false
````

<a id="arrassociative"></a>

### Arr::associative()

This method return `TRUE` if given array is an associative array.
An array will be called "associative" it does not have sequential numeric key started with zero:

```php
$array1 = ['product' => ['name' => 'Desk', 'price' => 100]];
$array2 = [1, 2, 3];

return Arr::associative($array1); // true
return Arr::associative($array2); // false
```

<a id="arrlast"></a>

### Arr::last()

This method return the last element of an array if it passed given truth test:

```php
$array = [100, 200, 300, 110];

return Arr::last($array, function ($value, $key) {
    return $value >= 150;
});

// 300
```

Default value can be added as third parameter of this method.
This value will be returned if there's no value passed from given truth test:

```php
return Arr::last($array, $callback, $default);
```

<a id="arronly"></a>

### Arr::only()

This method only return key/value pair which is decided by the array given:

```php
$array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];

return Arr::only($array, ['name', 'price']);
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrpluck"></a>

### Arr::pluck()

This method take all values belongs to certain key from an array:

```php
$array = [
    ['developer' => ['id' => 1, 'name' => 'Budi']],
    ['developer' => ['id' => 2, 'name' => 'Sarah']],
];

return Arr::pluck($array, 'developer.name');
// ['Budi', 'Sarah']
```

You can also specify the array key output returned:

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

If needed, you can also specify which key will be used for the item:

```php
$array = ['price' => 100];

$array = Arr::prepend($array, 'Desk', 'name');
// ['name' => 'Desk', 'price' => 100]
```

<a id="arrpull"></a>

### Arr::pull()

This method will return and remove the key/value pair from an array:

```php
$array = ['name' => 'Desk', 'price' => 100];

$name = Arr::pull($array, 'name');
// $name: 'Desk'
// $array: ['price' => 100]
```

Default value can be given as the third parameter in this method.
This value will be returned if the your expected key is not found:

```php
$value = Arr::pull($array, $key, $default);
```

<a id="arrrandom"></a>

### Arr::random()

This method return a random value from an array:

```php
$array = [1, 2, 3, 4, 5];

return Arr::random($array); // 4 - (randomly obtained)
```

You can also specify how many items to be returned in the third parameter.

Please note that if you use this option, returned value will not always an array.

```php
return Arr::random($array, 2); // [2, 5] - (randomly obtained)
```

<a id="arrset"></a>

### Arr::set()

This method used to specify a value into an array using "dot" notation:

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::set($array, 'products.desk.price', 200);

// $array: ['products' => ['desk' => ['price' => 200]]]
```

<a id="arrshuffle"></a>

### Arr::shuffle()

This method randomize items belong to an array:

```php
return Arr::shuffle([1, 2, 3, 4, 5]);
// [3, 2, 5, 1, 4] - (randomly generated)
```

<a id="arrsort"></a>

### Arr::sort()

This method will sort an array based on its value:

```php
$array = ['Desk', 'Table', 'Chair'];

return Arr::sort($array);
// ['Chair', 'Desk', 'Table']
```

You can also sort the array using Closure:

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

This method will sort an array recursively with the help of [sort](https://php.net/manual/en/function.sort.php)
function for numeric sub-array, and [ksort](https://php.net/manual/en/function.ksort.php)
for associative sub-array:

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

This method used to filter an array using Closure:

```php
$array = [100, '200', 300, '400', 500];

return Arr::where($array, function ($value, $key) {
    return is_string($value);
});

// [1 => '200', 3 => '400']
```

<a id="arrwrap"></a>

### Arr::wrap()

This method wrap a given value in an array.
If the given value is an array, the value will not be modified:

```php
$string = 'Bakso';

return Arr::wrap($string); // ['Bakso']
```

If the given value is `NULL`, it will return `empty array`:

```php
$empty = null;

return Arr::wrap($empty); // []
```

> Some additional helpers for array operations also available in the [Helper](/docs/en/helpers) page
