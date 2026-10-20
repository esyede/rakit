# String

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Available Methods](#available-methods)
    -   [Str::accentless\(\)](#straccentless)
    -   [Str::after\(\)](#strafter)
    -   [Str::before\(\)](#strbefore)
    -   [Str::bytes\(\)](#strbytes)
    -   [Str::camel\(\)](#strcamel)
    -   [Str::censor\(\)](#strcensor)
    -   [Str::characterify\(\)](#strcharacterify)
    -   [Str::classify\(\)](#strclassify)
    -   [Str::contains\(\)](#strcontains)
    -   [Str::contains_all\(\)](#strcontains_all)
    -   [Str::cuid\(\)](#strcuid)
    -   [Str::ends_with\(\)](#strends_with)
    -   [Str::finish\(\)](#strfinish)
    -   [Str::integers\(\)](#strintegers)
    -   [Str::is\(\)](#stris)
    -   [Str::kebab\(\)](#strkebab)
    -   [Str::length\(\)](#strlength)
    -   [Str::limit\(\)](#strlimit)
    -   [Str::lorem\(\)](#strlorem)
    -   [Str::lower\(\)](#strlower)
    -   [Str::nanoid\(\)](#strnanoid)
    -   [Str::parse_callback\(\)](#strparse_callback)
    -   [Str::password\(\)](#strpassword)
    -   [Str::plural\(\)](#strplural)
    -   [Str::plural_studly\(\)](#strplural_studly)
    -   [Str::random\(\)](#strrandom)
    -   [Str::replace_array\(\)](#strreplace_array)
    -   [Str::replace_first\(\)](#strreplace_first)
    -   [Str::replace_last\(\)](#strreplace_last)
    -   [Str::segments\(\)](#strsegments)
    -   [Str::singular\(\)](#strsingular)
    -   [Str::slug\(\)](#strslug)
    -   [Str::snake\(\)](#strsnake)
    -   [Str::start\(\)](#strstart)
    -   [Str::starts_with\(\)](#strstarts_with)
    -   [Str::studly\(\)](#strstudly)
    -   [Str::substr\(\)](#strsubstr)
    -   [Str::title\(\)](#strtitle)
    -   [Str::trim\(\)](#strtrim)
    -   [Str::ucfirst\(\)](#strucfirst)
    -   [Str::ulid\(\)](#strulid)
    -   [Str::upper\(\)](#strupper)
    -   [Str::uuid\(\)](#struuid)
    -   [Str::words\(\)](#strwords)
    -   [More Helpers](#more-helpers)
-   [Fluent Strings](#fluent-strings)
    -   [Fluent-Only Methods](#fluent-only-methods)
    -   [Truthiness](#truthiness)
-   [Macro](#macro)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

The `Str` class holds static helpers for working with strings. They are UTF-8 aware unless noted.

<a id="available-methods"></a>
<a id="list-helper"></a>

## Available Methods

<a id="straccentless"></a>

### Str::accentless()

Removes accents.

```php
Str::accentless('á é í ó ú ñ ü'); // 'a e i o u n ue'
```

<a id="strafter"></a>

### Str::after()

Returns everything after the first occurrence of a value, or the whole string if it is not found.

```php
Str::after('Rakit PHP framework', 'Rakit'); // ' PHP framework'
```

<a id="strbefore"></a>

### Str::before()

Returns everything before the first occurrence of a value, or the whole string if it is not found.

```php
Str::before('Rakit PHP framework', 'PHP'); // 'Rakit '
```

<a id="strbytes"></a>

### Str::bytes()

Returns cryptographically secure random bytes.

```php
bin2hex(Str::bytes(32)); // 64 character hex token
```

<a id="strcamel"></a>

### Str::camel()

Converts to camelCase.

```php
Str::camel('foo_bar'); // 'fooBar'
```

<a id="strcensor"></a>

### Str::censor()

Masks the middle of a string, e.g. a phone number or email. The second argument is the mask character.

```php
Str::censor('08123456789');   // '081******89'
Str::censor('budi@site.com'); // 'bud*******com'
Str::censor('rakit', '#');    // 'r###t'
```

<a id="strcharacterify"></a>

### Str::characterify()

Converts an integer from `-128` to `255` to its character (negatives wrap by 256). Other integers come back as a string, non-integers unchanged.

```php
Str::characterify(65);    // 'A'
Str::characterify('foo'); // 'foo'
```

<a id="strclassify"></a>

### Str::classify()

Converts to the underscored class name style.

```php
Str::classify('user_profile');     // 'User_Profile'
Str::classify('my.awesome.class'); // 'My_Awesome_Class'
```

<a id="strcontains"></a>

### Str::contains()

Checks whether a string contains a value (case-sensitive). Pass an array to match any of them.

```php
Str::contains('Rakit PHP framework', 'php');              // false
Str::contains('Rakit PHP framework', ['framework', 'x']); // true
```

<a id="strcontains_all"></a>

### Str::contains_all()

Checks whether a string contains every given value.

```php
Str::contains_all('Rakit PHP framework', ['Rakit', 'PHP']); // true
```

<a id="strcuid"></a>

### Str::cuid()

Generates a CUID, a collision-resistant id sortable by creation time.

```php
Str::cuid(); // 'cmt1f4uy20001fq9n1b000lx4'
```

<a id="strends_with"></a>

### Str::ends_with()

Checks whether a string ends with a value.

```php
Str::ends_with('Rakit PHP framework', 'framework'); // true
```

<a id="strfinish"></a>

### Str::finish()

Ends a string with a single instance of a value.

```php
Str::finish('this/string', '/');  // 'this/string/'
Str::finish('this/string/', '/'); // 'this/string/'
```

<a id="strintegers"></a>

### Str::integers()

Returns a cryptographically secure random integer between two values (inclusive).

```php
Str::integers(100000, 999999); // 6 digit OTP
```

<a id="stris"></a>

### Str::is()

Checks whether a string matches a pattern, where `*` is a wildcard. Accepts an array of patterns.

```php
Str::is('foo*', 'foobar'); // true
Str::is('baz*', 'foobar'); // false
```

<a id="strkebab"></a>

### Str::kebab()

Converts to kebab-case.

```php
Str::kebab('fooBar'); // 'foo-bar'
```

<a id="strlength"></a>

### Str::length()

Returns the length in characters.

```php
Str::length('こんにちは'); // 5
```

<a id="strlimit"></a>

### Str::limit()

Truncates to the given length (default `100`). The third argument replaces the `...` ending.

```php
Str::limit('The quick brown fox jumps over the lazy dog', 20);
// 'The quick brown fox...'
```

<a id="strlorem"></a>

### Str::lorem()

Generates lorem-ipsum text. Arguments: number of sentences, max words per sentence (minimum `4`), and whether to start with the standard `Lorem ipsum dolor sit amet...` sentence.

```php
Str::lorem();             // only the standard sentence
Str::lorem(5, 15);        // 5 sentences, up to 15 words each
Str::lorem(3, 20, false); // 3 random sentences
```

<a id="strlower"></a>

### Str::lower()

Converts to lowercase.

```php
Str::lower('Rakit'); // 'rakit'
```

<a id="strnanoid"></a>

### Str::nanoid()

Generates a short, URL-friendly random id. The size must be `8` to `21` (default `21`), otherwise an exception is thrown. The second argument is a custom character pool.

```php
Str::nanoid();                    // 'V1StGXR8_Z5jdHi6B-myT'
Str::nanoid(12, '0123456789abc'); // only these characters
```

<a id="strparse_callback"></a>

### Str::parse_callback()

Splits `Class@method` into `[class, method]`. Without `@`, the method is the given default.

```php
Str::parse_callback('Home_Controller@index');    // ['Home_Controller', 'index']
Str::parse_callback('Home_Controller', 'index'); // ['Home_Controller', 'index']
Str::parse_callback('Home_Controller');          // ['Home_Controller', null]
```

<a id="strpassword"></a>

### Str::password()

Generates a secure random password. Arguments: `$length = 32, $letters = true, $numbers = true, $symbols = true, $spaces = false`.

```php
Str::password();                     // 32 chars: letters, numbers, symbols
Str::password(20, true, true, false); // letters and numbers only
```

<a id="strplural"></a>

### Str::plural()

Returns the English plural form. The optional count returns the word unchanged when it is `1`.

```php
Str::plural('child');    // 'children'
Str::plural('car', 1);   // 'car'
```

<a id="strplural_studly"></a>

### Str::plural_studly()

Pluralizes the last word of a StudlyCase string. Takes an optional count like `plural()`.

```php
Str::plural_studly('ChildCategory');    // 'ChildCategories'
Str::plural_studly('PersonAddress', 1); // 'PersonAddress'
```

<a id="strrandom"></a>

### Str::random()

Generates a random alphanumeric string (default length `16`).

```php
Str::random(16); // 'VvhHyKNIp4qUTfmK'
```

<a id="strreplace_array"></a>

### Str::replace_array()

Replaces each occurrence of a value with the next item of an array.

```php
Str::replace_array('?', ['8:30', '21:00'], 'Airs at ? and ? WIB');
// 'Airs at 8:30 and 21:00 WIB'
```

<a id="strreplace_first"></a>

### Str::replace_first()

Replaces the first occurrence of a value.

```php
Str::replace_first('the', 'a', 'the fox and the dog'); // 'a fox and the dog'
```

<a id="strreplace_last"></a>

### Str::replace_last()

Replaces the last occurrence of a value.

```php
Str::replace_last('the', 'a', 'the fox and the dog'); // 'the fox and a dog'
```

<a id="strsegments"></a>

### Str::segments()

Splits a URI/path into its non-empty segments.

```php
Str::segments('/admin//posts/123/'); // ['admin', 'posts', '123']
```

<a id="strsingular"></a>

### Str::singular()

Returns the English singular form.

```php
Str::singular('children'); // 'child'
```

<a id="strslug"></a>

### Str::slug()

Makes a URL-friendly slug. The second argument is the separator (default `-`).

```php
Str::slug('Hello World'); // 'hello-world'
```

<a id="strsnake"></a>

### Str::snake()

Converts to snake_case. The second argument is the delimiter (default `_`).

```php
Str::snake('fooBar'); // 'foo_bar'
```

<a id="strstart"></a>

### Str::start()

Begins a string with a single instance of a value.

```php
Str::start('this/string', '/');  // '/this/string'
Str::start('/this/string', '/'); // '/this/string'
```

<a id="strstarts_with"></a>

### Str::starts_with()

Checks whether a string starts with a value.

```php
Str::starts_with('Rakit PHP framework', 'Rakit'); // true
```

<a id="strstudly"></a>

### Str::studly()

Converts to StudlyCase.

```php
Str::studly('foo_bar'); // 'FooBar'
```

<a id="strsubstr"></a>

### Str::substr()

Returns part of a string, like `mb_substr()`.

```php
Str::substr('Hello World', 0, 5); // 'Hello'
Str::substr('Hello World', -5);   // 'World'
```

<a id="strtitle"></a>

### Str::title()

Converts to Title Case.

```php
Str::title('selamat pagi indonesia'); // 'Selamat Pagi Indonesia'
```

<a id="strtrim"></a>

### Str::trim()

Removes whitespace and control characters from both ends.

```php
Str::trim("\n\t Hello \r\n"); // 'Hello'
```

<a id="strucfirst"></a>

### Str::ucfirst()

Uppercases the first character.

```php
Str::ucfirst('foo bar'); // 'Foo bar'
```

<a id="strulid"></a>

### Str::ulid()

Generates a 26 character ULID. It starts with a timestamp, so it sorts by creation time. Pass `true` for lowercase.

```php
Str::ulid();     // '01J8ZQ3K7WGV8QHRZ2S4T6M1XA'
Str::ulid(true); // '01j8zq3k7wgv8qhrz2s4t6m1xa'
```

<a id="strupper"></a>

### Str::upper()

Converts to uppercase.

```php
Str::upper('Rakit'); // 'RAKIT'
```

<a id="struuid"></a>

### Str::uuid()

Generates a random version 4 UUID.

```php
Str::uuid(); // 'a0a2a2d2-0b87-4a18-83f2-2529882be2de'
```

<a id="strwords"></a>

### Str::words()

Limits the number of words (default `100`). The third argument replaces the `...` ending.

```php
Str::words('You know, I miss you so much.', 3, ' >>>'); // 'You know, I >>>'
```

<a id="more-helpers"></a>

### More Helpers

| Method | Example | Result |
| --- | --- | --- |
| `Str::mask($s, $char, $index, $length = null)` | `Str::mask('budi@example.com', '*', 2, 5)` | `bu*****ample.com` |
| `Str::squish($s)` | `Str::squish("  a \n  b ")` | `a b` |
| `Str::headline($s)` | `Str::headline('user_profileUpdated')` | `User Profile Updated` |
| `Str::excerpt($text, $phrase, $options)` | `Str::excerpt('This is a beautiful morning', 'beautiful', ['radius' => 5])` | `...is a beautiful morn...` |
| `Str::between($s, $from, $to)` | `Str::between('a[b][c]', '[', ']')` | `b][c` (first to last) |
| `Str::between_first($s, $from, $to)` | `Str::between_first('a[b][c]', '[', ']')` | `b` |
| `Str::after_last($s, $search)` | `Str::after_last('file.tar.gz', '.')` | `gz` |
| `Str::before_last($s, $search)` | `Str::before_last('file.tar.gz', '.')` | `file.tar` |
| `Str::wrap($s, $before, $after = null)` | `Str::wrap('x', '"')` | `"x"` |
| `Str::unwrap($s, $before, $after = null)` | `Str::unwrap('[x]', '[', ']')` | `x` |
| `Str::pad_left/pad_right/pad_both($s, $length, $pad = ' ')` | `Str::pad_left('42', 5, '0')` | `00042` |
| `Str::is_url($s, $protocols = [])` | `Str::is_url('ftp://x.co', ['https'])` | `false` |
| `Str::is_json($s)` / `Str::is_ascii($s)` / `Str::is_uuid($s)` | `Str::is_json('{"a":1}')` | `true` |
| `Str::swap($map, $s)` | `Str::swap(['Tacos' => 'Burritos'], 'Tacos!')` | `Burritos!` |
| `Str::take($s, $limit)` | `Str::take('héllo', -2)` | `lo` |
| `Str::reverse($s)` | `Str::reverse('héllo')` | `olléh` |
| `Str::position($s, $needle, $offset = 0)` | `Str::position('héllo', 'l')` | `2` |
| `Str::deduplicate($s, $char = ' ')` | `Str::deduplicate('a   b')` | `a b` |
| `Str::ucsplit($s)` | `Str::ucsplit('FooBar')` | `['Foo', 'Bar']` |

Lengths and positions count characters, not bytes. Every `Str` method that takes the string
first is also available on fluent strings: `Str::of(' hello_world ')->squish()->headline()`.

<a id="fluent-strings"></a>

## Fluent Strings

`Str::of()` wraps a string so its methods can be chained. Every string-transforming method above is available, without the string argument; the generators (`random`, `password`, `bytes`, `integers`, `uuid`, `ulid`, `cuid`, `nanoid`, `lorem`) and `characterify` are not.

```php
echo Str::of('  Learning Rakit Together  ')->trim()->lower()->slug();
// 'learning-rakit-together'
```

Each call returns a new object, so the original is never changed. Echo it or call `value()` to get the plain string.

```php
$title = Str::of('hello world');

echo $title->upper(); // 'HELLO WORLD'
echo $title;          // 'hello world'
$title->value();      // plain string
```

Methods that return something other than a string end the chain: `length()`, `is()`, `contains()`, `contains_all()`, `starts_with()`, `ends_with()`, `segments()`, `parse_callback()` and `explode()`.

```php
Str::of('halo dunia')->length(); // 10
Str::of('a,b,c')->explode(',');  // ['a', 'b', 'c']
```

<a id="fluent-only-methods"></a>
<a id="methods-of-its-own"></a>

### Fluent-Only Methods

| Method | Description |
| --- | --- |
| `append($a, $b, ...)` | Add values to the end |
| `prepend($a, $b, ...)` | Add values to the front |
| `replace($search, $replace)` | Replace every occurrence |
| `explode($delimiter, $limit = null)` | Split into an array (empty delimiter throws) |
| `is_empty()` / `is_not_empty()` | Whether the string is empty |
| `when($condition, $callback, $default = null)` | Run the callback when the condition is truthy |
| `unless($condition, $callback, $default = null)` | Run the callback when it is not |
| `pipe($callback)` | Replace the string with the callback's result |
| `tap($callback)` | Pass the string to the callback, keep the string |
| `value()` | Return the plain string |

`when()` / `unless()` callbacks receive the fluent string and the condition. Their return value replaces the string; returning nothing keeps it. The condition may be a closure that receives the string.

```php
$name = Str::of($input)
    ->trim()
    ->when($formal, function ($string) {
        return $string->title();
    })
    ->finish('.');
```

> `pipe()` wraps the callback's result so the chain continues. `when()` returns it as-is, so a callback returning a plain string ends the chain.

<a id="truthiness"></a>
<a id="do-not-test-it-for-truth"></a>

### Truthiness

A fluent string is an object, so it is always truthy, even when empty. Use `is_not_empty()` instead:

```php
if (Str::of($value)->is_not_empty()) {
    // ...
}
```

<a id="macro"></a>

## Macro

Add your own `Str` method with `Str::macro()`, e.g. in `application/boot.php`:

```php
Str::macro('shout', function ($value) {
    return strtoupper($value) . '!';
});

Str::shout('rakit'); // 'RAKIT!'
```

Macros also work on fluent strings, with the string passed as the first argument. A macro that returns a string keeps the chain going.

```php
Str::of('rakit')->shout()->limit(4); // 'RAKI...'
```

> A macro may not reuse the name of an existing `Str` or fluent string method; that throws an exception.
