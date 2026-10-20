# Macroable

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Adding Macros](#adding-macros)
-   [Accessing the Instance](#accessing-the-instance)
-   [Mixins](#mixins)
-   [Checking a Macro](#checking-a-macro)
-   [Macros on Str](#macros-on-str)
-   [Where to Register](#where-to-register)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>
<a id="using-trait"></a>

## Introduction

The `System\Macroable` trait lets you add methods to a class at runtime. `Response`,
`Collection` and `Request` use it, and so can your own classes:

```php
Collection::macro('evens', function () {
    return $this->filter(function ($value) {
        return 0 === $value % 2;
    })->values();
});

Response::macro('csv', function ($rows, $name) {
    return Response::make(implode("\n", $rows), 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="'.$name.'"',
    ]);
});

Request::macro('is_api', function () {
    return Str::starts_with(URI::current(), 'api');
});
```

```php
use System\Macroable;

class Text
{
    use Macroable;
}
```

> The `Str` component has its own, stricter macro support. See [Macros on Str](#macros-on-str).

<a id="adding-macros"></a>
<a id="adding-macro"></a>
<a id="calling-macro"></a>

## Adding Macros

`macro()` takes the method name and a callable. The macro can then be called
statically or on an instance:

```php
Text::macro('wrap', function ($text, $before = '[', $after = ']') {
    return $before.$text.$after;
});

echo Text::wrap('Hello');           // [Hello]
echo Text::wrap('Hello', '<', '>'); // <Hello>
echo (new Text())->wrap('Hello');   // [Hello]
```

Calling a macro that was never registered throws a `BadMethodCallException`.

<a id="accessing-the-instance"></a>
<a id="binding-context"></a>

## Accessing the Instance

When a closure macro is called on an instance, `$this` is bound to that instance,
so it can read and change protected state:

```php
class Calculator
{
    use Macroable;

    protected $value = 0;

    public function __construct($value = 0)
    {
        $this->value = $value;
    }
}

Calculator::macro('multiply', function ($number) {
    $this->value *= $number;
    return $this;
});

Calculator::macro('result', function () {
    return $this->value;
});

echo (new Calculator(5))->multiply(2)->result(); // 10
```

<a id="mixins"></a>
<a id="using-mixin"></a>

## Mixins

`mixin()` registers every public and protected method of an object as a macro.
Each method must **return a closure**, which becomes the macro body:

```php
class Text_Mixin
{
    public function reverse()
    {
        return function ($text) {
            return strrev($text);
        };
    }
}

Text::mixin(new Text_Mixin());

echo Text::reverse('hello'); // olleh
```

Existing macros with the same name are overwritten. Pass `false` as the second
argument to keep them:

```php
Text::mixin(new Text_Mixin(), false);
```

<a id="checking-a-macro"></a>
<a id="checking-macro"></a>

## Checking a Macro

```php
if (! Text::has_macro('whisper')) {
    Text::macro('whisper', function ($text) {
        return strtolower($text).'...';
    });
}
```

<a id="macros-on-str"></a>
<a id="macro-on-the-str-component"></a>

## Macros on Str

`Str` does not use the trait, but has its own `Str::macro()`:

```php
Str::macro('shout', function ($value) {
    return strtoupper($value).'!';
});

echo Str::shout('rakit'); // RAKIT!
```

Differences from the trait:

- The handler **must** be a `Closure`.
- The name may not clash with an existing `Str` method (e.g.
  [Str::slug()](/docs/strings#strslug)) or a fluent string method; doing so throws
  an exception.
- The macro also works on [Str::of()](/docs/strings#fluent-strings), receiving the
  string as its first argument.
- Only `macro()` exists; there is no `mixin()` or `has_macro()`.

<a id="where-to-register"></a>

## Where to Register

Register macros before they are called, in a file that always loads, such as
`application/boot.php`. For many macros, move them into their own file:

```php
// application/boot.php
require_once path('app').'macros.php';
```
