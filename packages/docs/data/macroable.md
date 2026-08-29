# Macroable

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Using Trait](#using-trait)
- [Adding Macro](#adding-macro)
- [Calling Macro](#calling-macro)
- [Binding Context](#binding-context)
- [Using Mixin](#using-mixin)
- [Checking Macro](#checking-macro)
- [Macro on the Str Component](#macro-on-the-str-component)
- [Where to Register](#where-to-register)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Macroable` trait lets you add new methods to a class at runtime without
touching the original class. It is handy for grouping your own helpers into one
place, or for extending a class you do not want to subclass.

> This trait is meant for **your own classes**. No framework component uses it.
> The `Str` component has its own macro support, which is explained
> [at the end of this page](#macro-on-the-str-component).

<a id="using-trait"></a>
## Using Trait

Use the trait in the class you want to make extendable:

```php
use System\Macroable;

class Text
{
    use Macroable;

    public function existing_method()
    {
        return 'Original method';
    }
}
```

<a id="adding-macro"></a>
## Adding Macro

Register a macro with `macro()`. The name is the method name, and the handler is
the closure that runs when it is called:

```php
Text::macro('shout', function ($text) {
    return strtoupper($text) . '!!!';
});

Text::macro('wrap', function ($text, $before = '[', $after = ']') {
    return $before . $text . $after;
});
```

<a id="calling-macro"></a>
## Calling Macro

Macros can be called statically or from an instance:

```php
echo Text::shout('hello');          // HELLO!!!
echo Text::wrap('Hello');           // [Hello]
echo Text::wrap('Hello', '<', '>'); // <Hello>

$text = new Text();
echo $text->shout('hello world');   // HELLO WORLD!!!
```

> Calling a macro that was never registered throws a `BadMethodCallException`.

<a id="binding-context"></a>
## Binding Context

When a macro is called from an instance, `$this` inside the closure refers to
that instance, so the macro can read and change the object state, including its
protected properties:

```php
class Calculator
{
    use Macroable;

    protected $value = 0;

    public function __construct($value = 0)
    {
        $this->value = $value;
    }

    public function add($number)
    {
        $this->value += $number;
        return $this;
    }
}

Calculator::macro('multiply', function ($number) {
    $this->value *= $number;
    return $this;
});

Calculator::macro('result', function () {
    return $this->value;
});

$calc = new Calculator(5);
echo $calc->add(3)->multiply(2)->result(); // 16
```

<a id="using-mixin"></a>
## Using Mixin

A mixin registers several macros at once. Every method of the mixin class must
**return a closure**, and that closure becomes the macro body:

```php
class Text_Mixin
{
    public function reverse()
    {
        return function ($text) {
            return strrev($text);
        };
    }

    public function truncate()
    {
        return function ($text, $length = 100, $suffix = '...') {
            return (strlen($text) <= $length) ? $text : substr($text, 0, $length) . $suffix;
        };
    }
}

Text::mixin(new Text_Mixin());

echo Text::reverse('hello');                      // olleh
echo Text::truncate('Lorem ipsum dolor sit', 11); // Lorem ipsum...
```

The second parameter decides whether existing macros are overwritten. It
defaults to `true`, so pass `false` to keep the macros you already registered:

```php
Text::mixin(new Text_Mixin(), false);
```

<a id="checking-macro"></a>
## Checking Macro

`has_macro()` tells you whether a macro is already registered. It is useful to
avoid registering the same macro twice:

```php
if (!Text::has_macro('whisper')) {
    Text::macro('whisper', function ($text) {
        return strtolower($text) . '...';
    });
}
```

<a id="macro-on-the-str-component"></a>
## Macro on the Str Component

The `Str` component does not use this trait, but it accepts macros through its
own `Str::macro()` method:

```php
Str::macro('shout', function ($value) {
    return strtoupper($value) . '!';
});

echo Str::shout('rakit'); // RAKIT!
```

Three differences from the trait:

- The handler **must** be a `Closure`.
- The name may not clash with an existing `Str` method. `Str::macro('slug', ..)`
  throws an exception because [Str::slug()](/docs/strings#strslug) already exists.
  Nor may it clash with a method of the fluent string, since the same name would
  otherwise mean one thing for `Str::foo()` and another for `Str::of()->foo()`.
  A macro is reachable from [Str::of()](/docs/strings#fluent-strings) as well,
  with the string handed to it as the first argument.
- Only `macro()` is available, there is no `mixin()` or `has_macro()`.

<a id="where-to-register"></a>
## Where to Register

A macro must be registered before it is called, so put it in a file that is
always loaded, such as `application/boot.php`:

```php
// application/boot.php
Str::macro('shout', function ($value) {
    return strtoupper($value) . '!';
});
```

For a large number of macros, move them into their own file and require it:

```php
// application/boot.php
require_once path('app') . 'macros.php';
```
