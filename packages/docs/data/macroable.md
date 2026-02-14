# Macroable

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Using Trait](#using-trait)
- [Adding Macro](#adding-macro)
- [Calling Macro](#calling-macro)
  - [Static Call](#static-call)
  - [Instance Call](#instance-call)
- [Macro With Parameters](#macro-with-parameters)
- [Using Mixin](#using-mixin)
- [Checking Macro](#checking-macro)
- [Binding Context](#binding-context)
- [Usage Examples](#usage-examples)
  - [Extending String Helper](#extending-string-helper)
  - [Extending Collection](#extending-collection)
  - [Custom Helper Methods](#custom-helper-methods)
  - [Reusable Logic](#reusable-logic)
- [Best Practices](#best-practices)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Macroable` trait allows you to add new methods to a class dynamically at runtime without modifying the original class. This is very useful for extending the functionality of existing classes or creating custom helper methods.

With this trait, you can:
- Add new methods to existing classes
- Create reusable custom helpers
- Extend classes without inheritance
- Add multiple methods at once with mixins

<a id="using-trait"></a>
## Using Trait

To make your class macroable, simply use the `Macroable` trait:

```php
class MyClass
{
    use Macroable;

    public function existing_method()
    {
        return 'Original method';
    }
}
```

Now the `MyClass` can accept new macros.

<a id="adding-macro"></a>
## Adding Macro

Use the static `macro()` method to add a new method:

```php
class Str
{
    use Macroable;
}

// Add 'shout' macro
Str::macro('shout', function ($text) {
    return strtoupper($text) . '!!!';
});
```

<a id="calling-macro"></a>
## Calling Macro

<a id="static-call"></a>
### Static Call

After the macro is added, you can call it like a regular static method:

```php
echo Str::shout('hello');
// Output: HELLO!!!
```

<a id="instance-call"></a>
### Instance Call

Macros can also be called from object instances:

```php
$str = new Str();
echo $str->shout('hello world');
// Output: HELLO WORLD!!!
```

<a id="macro-with-parameters"></a>
## Macro With Parameters

Macros can accept parameters like regular methods:

```php
Str::macro('wrap', function ($text, $before = '[', $after = ']') {
    return $before . $text . $after;
});

echo Str::wrap('Hello');
// Output: [Hello]

echo Str::wrap('Hello', '<', '>');
// Output: <Hello>

echo Str::wrap('Hello', '**', '**');
// Output: **Hello**
```

Multiple parameters:

```php
Str::macro('format_name', function ($first, $last, $middle = '') {
    $name = $first;

    if ($middle) {
        $name .= ' ' . $middle;
    }

    $name .= ' ' . $last;

    return $name;
});

echo Str::format_name('John', 'Doe');
// Output: John Doe

echo Str::format_name('John', 'Doe', 'Smith');
// Output: John Smith Doe
```

<a id="using-mixin"></a>
## Using Mixin

Mixin allows you to add multiple macros at once from a class:

```php
// Class containing methods to be mixed in
class StringMixin
{
    public function reverse()
    {
        return function ($text) {
            return strrev($text);
        };
    }

    public function shuffle()
    {
        return function ($text) {
            return str_shuffle($text);
        };
    }

    public function truncate()
    {
        return function ($text, $length = 100, $suffix = '...') {
            if (strlen($text) <= $length) {
                return $text;
            }

            return substr($text, 0, $length) . $suffix;
        };
    }
}

// Add all methods from mixin
Str::mixin(new StringMixin());

// Now all methods are available
echo Str::reverse('hello');
// Output: olleh

echo Str::shuffle('hello');
// Output: lohel (random)

echo Str::truncate('Lorem ipsum dolor sit amet', 10);
// Output: Lorem ipsu...
```

Mixin with replace flag:

```php
// Don't replace existing macros
Str::mixin(new StringMixin(), false);

// Replace existing macros (default: true)
Str::mixin(new StringMixin(), true);
```

<a id="checking-macro"></a>
## Checking Macro

Use the `has_macro()` method to check if a macro is registered:

```php
if (Str::has_macro('shout')) {
    echo 'Macro "shout" is available';
}

if (!Str::has_macro('whisper')) {
    // Add new macro
    Str::macro('whisper', function ($text) {
        return strtolower($text) . '...';
    });
}
```

<a id="binding-context"></a>
## Binding Context

When a macro is called from an instance, `$this` inside the closure will refer to that instance:

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

    public function get_value()
    {
        return $this->value;
    }
}

// Add macro that accesses $this
Calculator::macro('multiply', function ($number) {
    $this->value *= $number;
    return $this;
});

Calculator::macro('result', function () {
    return $this->value;
});

// Use from instance
$calc = new Calculator(5);
$result = $calc->add(3)->multiply(2)->result();

echo $result;
// Output: 16
```

<a id="usage-examples"></a>
## Usage Examples

<a id="extending-string-helper"></a>
### Extending String Helper

Add custom string methods:

```php
use System\Str;

// Add method to generate slug
Str::macro('slug', function ($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
});

echo Str::slug('Hello World! This is a Test');
// Output: hello-world-this-is-a-test

// Add method to mask string
Str::macro('mask', function ($text, $char = '*', $start = 0, $length = null) {
    if ($length === null) {
        $length = strlen($text) - $start;
    }

    return substr($text, 0, $start)
        . str_repeat($char, $length)
        . substr($text, $start + $length);
});

echo Str::mask('1234567890', '*', 4, 4);
// Output: 1234****90

echo Str::mask('johndoe@email.com', '*', 0, strpos('johndoe@email.com', '@'));
// Output: *******@email.com
```

<a id="extending-collection"></a>
### Extending Collection

Add custom methods to Collection:

```php
// Add method to extract values
Collection::macro('to_select_options', function ($value, $label) {
    return $this->map(function ($item) use ($value, $label) {
        return [
            'value' => is_array($item) ? $item[$value] : $item->$value,
            'label' => is_array($item) ? $item[$label] : $item->$label,
        ];
    });
});

$users = Collection::make([
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane'],
    ['id' => 3, 'name' => 'Bob'],
]);

$options = $users->to_select_options('id', 'name');

/*
[
    ['value' => 1, 'label' => 'John'],
    ['value' => 2, 'label' => 'Jane'],
    ['value' => 3, 'label' => 'Bob'],
]
*/
```

<a id="custom-helper-methods"></a>
### Custom Helper Methods

Create utility methods for your application:

```php
class Helper
{
    use Macroable;
}

// Format currency
Helper::macro('currency', function ($amount, $currency = 'IDR') {
    return $currency . ' ' . number_format($amount, 0, ',', '.');
});

// Format date
Helper::macro('human_date', function ($date) {
    return date('d F Y', strtotime($date));
});

// Generate random string
Helper::macro('random_string', function ($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $string = '';

    for ($i = 0; $i < $length; $i++) {
        $string .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $string;
});

// Use
echo Helper::currency(1000000);
// Output: IDR 1.000.000

echo Helper::human_date('2024-01-15');
// Output: 15 January 2024

echo Helper::random_string(8);
// Output: aB3xY9mK (random)
```

<a id="reusable-logic"></a>
### Reusable Logic

Create logic that can be used in various places:

```php
// In bootstrap file or service provider
Response::macro('success', function ($data, $message = 'Success') {
    return Response::json([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
});

Response::macro('error', function ($message, $code = 400) {
    return Response::json([
        'success' => false,
        'message' => $message,
        'data' => null,
    ], $code);
});

// Use in controller
Route::get('api/users', function () {
    $users = User::all();
    return Response::success($users, 'Users retrieved successfully');
});

Route::post('api/login', function () {
    if (!Auth::attempt(Input::only('email', 'password'))) {
        return Response::error('Invalid credentials', 401);
    }

    return Response::success(['token' => Auth::user()->token]);
});
```

<a id="best-practices"></a>
## Best Practices

1. **Register macros in the right place**
   ```php
   // In boot.php file or service provider
   require_once path('app') . 'macros.php';
   ```

2. **Use clear naming**
   ```php
   // Good
   Str::macro('to_slug', function ($text) { ... });

   // Bad
   Str::macro('ts', function ($text) { ... });
   ```

3. **Document your macros**
   ```php
   /**
    * Convert text to URL-friendly slug
    *
    * @param string $text
    * @return string
    */
   Str::macro('to_slug', function ($text) { ... });
   ```

4. **Check before re-registering**
   ```php
   if (!Str::has_macro('custom_method')) {
       Str::macro('custom_method', function () { ... });
   }
   ```

5. **Use mixins for grouping**
   ```php
   // Better to group related methods in mixins
   Str::mixin(new SlugMixin());
   Str::mixin(new FormatterMixin());
   ```

6. **Type hint for IDE support**
   ```php
   /**
    * @method static string to_slug(string $text)
    * @method static string mask(string $text, string $char = '*')
    */
   class Str
   {
       use Macroable;
   }
   ```
