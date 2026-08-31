# String

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [List Helper](#list-helper)
    -   [Str::after\(\)](#strafter)
    -   [Str::before\(\)](#strbefore)
    -   [Str::camel\(\)](#strcamel)
    -   [Str::censor\(\)](#strcensor)
    -   [Str::characterify\(\)](#strcharacterify)
    -   [Str::contains\(\)](#strcontains)
    -   [Str::contains_all\(\)](#strcontains_all)
    -   [Str::ends_with\(\)](#strends_with)
    -   [Str::finish\(\)](#strfinish)
    -   [Str::is\(\)](#stris)
    -   [Str::length\(\)](#strlength)
    -   [Str::lower\(\)](#strlower)
    -   [Str::upper\(\)](#strupper)
    -   [Str::ucfirst\(\)](#strucfirst)
    -   [Str::kebab\(\)](#strkebab)
    -   [Str::limit\(\)](#strlimit)
    -   [Str::lorem\(\)](#strlorem)
    -   [Str::trim\(\)](#strtrim)
    -   [Str::substr\(\)](#strsubstr)
    -   [Str::classify\(\)](#strclassify)
    -   [Str::segments\(\)](#strsegments)
    -   [Str::plural_studly\(\)](#strplural_studly)
    -   [Str::parse_callback\(\)](#strparse_callback)
    -   [Str::password\(\)](#strpassword)
    -   [Str::bytes\(\)](#strbytes)
    -   [Str::integers\(\)](#strintegers)
    -   [Str::plural\(\)](#strplural)
    -   [Str::random\(\)](#strrandom)
    -   [Str::replace_array\(\)](#strreplace_array)
    -   [Str::replace_first\(\)](#strreplace_first)
    -   [Str::replace_last\(\)](#strreplace_last)
    -   [Str::singular\(\)](#strsingular)
    -   [Str::slug\(\)](#strslug)
    -   [Str::accentless\(\)](#straccentless)
    -   [Str::snake\(\)](#strsnake)
    -   [Str::start\(\)](#strstart)
    -   [Str::starts_with\(\)](#strstarts_with)
    -   [Str::studly\(\)](#strstudly)
    -   [Str::title\(\)](#strtitle)
    -   [Str::uuid\(\)](#struuid)
    -   [Str::ulid\(\)](#strulid)
    -   [Str::cuid\(\)](#strcuid)
    -   [Str::nanoid\(\)](#strnanoid)
    -   [Str::words\(\)](#strwords)
-   [Fluent Strings](#fluent-strings)
-   [Macro](#macro)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

In the web development process, you will often manipulate strings.
For example, when you want to make a string URL-friendly or when
you want to truncate a string.

This component provides a set of methods to help make string manipulation
work easier and simpler. Let's see what's available:

<a id="list-helper"></a>

## List Helper

Here is the list of helpers available for this component:

<a id="strafter"></a>

### Str::after()

Returns everything after the first occurrence of the given value.
If the value is not found, the whole string is returned:

```php
Str::after('Rakit PHP framework', 'Rakit');   // ' PHP framework'
Str::after('Rakit PHP framework', 'Foo Bar'); // 'Rakit PHP framework'
```

<a id="strbefore"></a>

### Str::before()

Returns everything before the first occurrence of the given value.
If the value is not found, the whole string is returned:

```php
Str::before('Rakit PHP framework', 'PHP framework'); // 'Rakit '
Str::before('Rakit PHP framework', 'Foo Bar');       // 'Rakit PHP framework'
```

<a id="strcamel"></a>

### Str::camel()

This method converts the given string to camelCase:

```php
$converted = Str::camel('foo_bar'); // fooBar
```

<a id="strcensor"></a>

### Str::censor()

This method masks the middle part of a string, useful for hiding part of a
phone number or an email address:

```php
Str::censor('08123456789');   // '081******89'
Str::censor('budi@site.com'); // 'bud*******com'
Str::censor('rakit', '#');    // 'r###t'
```

<a id="strcharacterify"></a>

### Str::characterify()

This method converts an integer to its character according to the ctype rules.
Non-integer values are returned as-is:

```php
Str::characterify(65);    // 'A'
Str::characterify('foo'); // 'foo'
```

<a id="strcontains"></a>

### Str::contains()

This method checks if a string contains the given value (case sensitive):

```php
$contains = Str::contains('Rakit PHP framework', 'PHP'); // true

$contains = Str::contains('Rakit PHP framework', 'php'); // false

$contains = Str::contains('Rakit PHP framework', 'foo'); // false
```

You can also pass an array to check if the given string contains any of the values:

```php
$contains = Str::contains('Rakit PHP framework', ['framework', 'foo']); // true
```

<a id="strcontains_all"></a>

### Str::contains_all()

This method checks if the given string contains all of its values:

```php
$contains_all = Str::contains_all('Rakit PHP framework', ['Rakit', 'PHP']); // true

$contains_all = Str::contains_all('Rakit PHP framework', ['Rakit', 'foo']); // false
```

<a id="strends_with"></a>

### Str::ends_with()

This method checks if a string ends with the given value:

```php
$result = Str::ends_with('Rakit PHP framework', 'framework'); // true
```

You can also pass an array to check if a string ends with any of its values:

```php
$result = Str::ends_with('Rakit PHP framework', ['framework', 'foo']); // true

$result = Str::ends_with('Rakit PHP framework', ['php', 'foo']); // false
```

<a id="strfinish"></a>

### Str::finish()

This method adds the value to the end of the string if the string is not already ended with that value:

```php
$adjusted = Str::finish('this/string', '/');  // this/string/

$adjusted = Str::finish('this/string/', '/'); // this/string/
```

<a id="stris"></a>

### Str::is()

This method checks if a string matches the given pattern. The `*` (asterisk)
can be used for wildcard:

```php
$matches = Str::is('foo*', 'foobar'); // true

$matches = Str::is('baz*', 'foobar'); // false
```

<a id="strucfirst"></a>

### Str::ucfirst()

This method returns the given string with the first character capitalized:

```php
$string = Str::ucfirst('foo bar'); // Foo bar
```

<a id="strlength"></a>

### Str::length()

This method counts the length of the string (with UTF-8 support):

```php
$length = Str::length('Hello'); // 5

$length = Str::length('Halo Dunia'); // 10

// Support UTF-8
$length = Str::length('こんにちは'); // 5
```

<a id="strlower"></a>

### Str::lower()

This method converts the string to lowercase:

```php
$lowercased = Str::lower('HELLO WORLD'); // hello world

$lowercased = Str::lower('Laravel'); // laravel
```

<a id="strupper"></a>

### Str::upper()

This method converts the string to uppercase:

```php
$uppercased = Str::upper('hello world'); // HELLO WORLD

$uppercased = Str::upper('Laravel'); // LARAVEL
```

<a id="strkebab"></a>

### Str::kebab()

This method converts the given string to kebab-case:

```php
$converted = Str::kebab('fooBar'); // foo-bar
```

<a id="strlimit"></a>

### Str::limit()

This method truncates the string to the specified length:

```php
$truncated = Str::limit('The quick brown fox jumps over the lazy dog', 20);
// The quick brown fox...
```

You can also pass a third parameter to change the ending string:

```php
$truncated = Str::limit('The quick brown fox jumps over the lazy dog', 20, ' (...)');
// The quick brown fox (...)
```

<a id="strlorem"></a>

### Str::lorem()

This method generates dummy lorem-ipsum text. The first parameter is the number
of sentences, the second is the maximum number of words per sentence (minimum `4`),
and the third decides whether the first sentence uses the standard
`Lorem ipsum dolor sit amet..` opening:

```php
$dummy = Str::lorem();             // only the standard opening sentence
$dummy = Str::lorem(5, 15);        // 5 sentences, at most 15 words each
$dummy = Str::lorem(3, 20, false); // 3 random sentences, without the standard opening
```

<a id="strtrim"></a>

### Str::trim()

This method removes whitespace and control characters from the beginning and end of the string:

```php
$trimmed = Str::trim('  hello world  '); // 'hello world'

$trimmed = Str::trim("\n\t Hello \r\n"); // 'Hello'
```

<a id="strsubstr"></a>

### Str::substr()

This method takes a substring from the string (with UTF-8 support):

```php
$substring = Str::substr('Hello World', 0, 5); // 'Hello'

$substring = Str::substr('Hello World', 6); // 'World'

$substring = Str::substr('Hello World', -5); // 'World'

// Support UTF-8
$substring = Str::substr('こんにちは世界', 0, 5); // 'こんにちは'
```

<a id="strclassify"></a>

### Str::classify()

This method converts the string to class name format (PascalCase with underscores):

```php
$classified = Str::classify('user_profile'); // User_Profile

$classified = Str::classify('app-settings'); // App_Settings

$classified = Str::classify('my.awesome.class'); // My_Awesome_Class
```

<a id="strsegments"></a>

### Str::segments()

This method splits URI/path into array segments:

```php
$segments = Str::segments('user/profile/edit');
// ['user', 'profile', 'edit']

$segments = Str::segments('/admin/posts/123/');
// ['admin', 'posts', '123']

$segments = Str::segments('///multiple///slashes///');
// ['multiple', 'slashes']
```

<a id="strplural_studly"></a>

### Str::plural_studly()

This method converts the last word in a StudlyCase string to plural form:

```php
$plural = Str::plural_studly('UserProfile'); // UserProfiles

$plural = Str::plural_studly('ChildCategory'); // ChildCategories

$plural = Str::plural_studly('PersonAddress', 1); // PersonAddress (count = 1)
```

<a id="strparse_callback"></a>

### Str::parse_callback()

This method splits a `Class@method` string into a `[class, method]` array. If the
string has no `@`, the second element falls back to the given default value:

```php
Str::parse_callback('Home_Controller@index');           // ['Home_Controller', 'index']
Str::parse_callback('Home_Controller', 'index');        // ['Home_Controller', 'index']
Str::parse_callback('Home_Controller');                 // ['Home_Controller', null]
```

<a id="strpassword"></a>

### Str::password()

This method generates a secure random password:

```php
// Password 32 characters (default)
$password = Str::password(); // 'aB3$xY9#...'

// Password with custom length
$password = Str::password(16); // 16 characters

// Password with letters and numbers only
$password = Str::password(20, true, true, false, false);
// Parameters: length, letters, numbers, symbols, spaces

// Password with letters, numbers, and symbols
$password = Str::password(24, true, true, true, false);
```

<a id="strbytes"></a>

### Str::bytes()

This method generates cryptographically secure random bytes:

```php
$bytes = Str::bytes(16); // 16 bytes random data

$bytes = Str::bytes(32); // 32 bytes random data

// Use for generating key/token
$token = bin2hex(Str::bytes(32)); // 64 character hex string
```

<a id="strintegers"></a>

### Str::integers()

This method generates cryptographically secure random integers:

```php
$random = Str::integers(1, 100); // Random integer between 1-100

$random = Str::integers(1000, 9999); // Random 4 digit integer

// For OTP/PIN
$otp = Str::integers(100000, 999999); // 6 digit OTP
```

<a id="strplural"></a>

### Str::plural()

This method converts a singular word string to its plural form. It only supports English:

```php
$plural = Str::plural('car');   // cars

$plural = Str::plural('child'); // children
```

<a id="strrandom"></a>

### Str::random()

This method generates a random string with the specified length:

```php
$random = Str::random(16); // 'VvhHyKNIp4qUTfmK ' (randomly generated)
```

<a id="strreplace_array"></a>

### Str::replace_array()

This method replaces values in the string sequentially using an array:

```php
$string = 'Airs every day at ? and ? WIB';

$replaced = Str::replace_array('?', ['8:30', '21:00'], $string);
// Airs every day at 8:30 and 21:00 WIB
```

<a id="strreplace_first"></a>

### Str::replace_first()

This method replaces the first occurrence of the value in the string:

```php
$replaced = Str::replace_first('the', 'a', 'the quick brown fox jumps over the lazy dog');
// a quick brown fox jumps over the lazy dog
```

<a id="strreplace_last"></a>

### Str::replace_last()

This method replaces the last occurrence of the value in the string:

```php
$replaced = Str::replace_last('the', 'a', 'the quick brown fox jumps over the lazy dog');
// the quick brown fox jumps over a lazy dog
```

<a id="strsingular"></a>

### Str::singular()

This method converts the string to singular form. It only supports English:

```php
$singular = Str::singular('cars'); // car

$singular = Str::singular('children'); // child
```

<a id="strslug"></a>

### Str::slug()

This method converts the given string to a URL-friendly string:

```php
$slug = Str::slug('Hello World', '-'); // hello-world
```

<a id="straccentless"></a>

### Str::accentless()

This method removes accents from the given string:

```php
Str::accentless('ÀÂÄÈÊËÎÏÔŒÙÛÜŸ'); // AAAeEEEIIOOEUUUeY
Str::accentless('á é í ó ú ñ ü'); // a e i o u n ue
```

<a id="strsnake"></a>

### Str::snake()

This method converts the given string to snake_case:

```php
$converted = Str::snake('fooBar'); // foo_bar
```

<a id="strstart"></a>

### Str::start()

This method adds the value to the beginning of the string if the string is not already started with that value:

```php
$adjusted = Str::start('this/string', '/'); // /this/string

$adjusted = Str::start('/this/string', '/'); // /this/string
```

<a id="strstarts_with"></a>

### Str::starts_with()

This method checks if a string starts with the given value:

```php
$result = Str::starts_with('Rakit PHP framework', 'Rakit'); // true
```

<a id="strstudly"></a>

### Str::studly()

This method converts the given string to StudlyCase:

```php
$converted = Str::studly('foo_bar'); // FooBar
```

<a id="strtitle"></a>

### Str::title()

This method converts the given string to Title Case:

```php
$converted = Str::title('selamat pagi indonesia');
// Selamat Pagi Indonesia
```

<a id="struuid"></a>

### Str::uuid()

This method generates a UUID string (version 4):

```php
return Str::uuid(); // a0a2a2d2-0b87-4a18-83f2-2529882be2de (randomly generated)
```

<a id="strulid"></a>

### Str::ulid()

This method generates a 26 character ULID. Unlike a UUID, a ULID starts with a
timestamp so it is naturally sortable by creation time. Pass `true` to get it in
lowercase:

```php
Str::ulid();     // 01J8ZQ3K7WGV8QHRZ2S4T6M1XA
Str::ulid(true); // 01j8zq3k7wgv8qhrz2s4t6m1xa
```

<a id="strcuid"></a>

### Str::cuid()

This method generates a CUID, a collision-resistant id that is also sortable by
creation time:

```php
Str::cuid(); // cmt1f4uy20001fq9n1b000lx4
```

<a id="strnanoid"></a>

### Str::nanoid()

This method generates a NanoID, a short URL-friendly random id. The size must be
between `8` and `21` (default `21`), and you may supply your own character pool:

```php
Str::nanoid();                    // 'V1StGXR8_Z5jdHi6B-myT'
Str::nanoid(10);                  // 'IRFa-VaY2b'
Str::nanoid(12, '0123456789abc'); // only uses the given characters
```

> Size outside the `8` to `21` range throws an exception.

<a id="strwords"></a>

### Str::words()

This method limits the number of words in a string:

```php
return Str::words('You know, I miss you so much.', 3, ' >>>');
// 'You know, I >>>'
```

<a id="fluent-strings"></a>

## Fluent Strings

`Str::of()` wraps a string so the methods above can be chained instead of nested:

```php
$slug = Str::of('  Learning Rakit Together  ')->trim()->lower()->slug();
// 'learning-rakit-together'
```

The wrapper never changes the string it was given; every method hands back a new
one. Echo it, or ask for `value()`, to get the plain string back:

```php
$title = Str::of('hello world');

echo $title->upper();     // 'HALO DUNIA'
echo $title;              // 'halo dunia', the original is untouched

$plain = $title->value(); // a plain string
```

Methods that answer with something other than a string — `length()`, `is()`,
`contains()`, `contains_all()`, `starts_with()`, `ends_with()`, `segments()`,
`parse_callback()` and `explode()` — end the chain and give you that answer:

```php
Str::of('halo dunia')->length();          // 10
Str::of('halo dunia')->contains('dunia'); // TRUE
Str::of('a,b,c')->explode(',');           // ['a', 'b', 'c']
```

### Methods of Its Own

A few methods exist only on the fluent string:

| Method | What it does |
| --- | --- |
| `append($a, $b, ..)` | Add the values to the end |
| `prepend($a, $b, ..)` | Add the values to the front |
| `replace($search, $replace)` | Replace every occurrence |
| `explode($delimiter, $limit)` | Split into an array |
| `is_empty()` / `is_not_empty()` | Whether the string is empty |
| `when($condition, $callback, $default)` | Run the callback when the condition holds |
| `unless($condition, $callback, $default)` | Run it when the condition does not |
| `pipe($callback)` | Keep whatever the callback answers |
| `tap($callback)` | Hand the string over, carry on with the string |
| `value()` | The plain string |

`when()` and `unless()` are how a chain makes up its mind without breaking apart:

```php
$name = Str::of($input)
    ->trim()
    ->when($formal, function ($string) {
        return $string->title();
    })
    ->finish('.');
```

The callback is handed the fluent string and the condition. Whatever it answers
takes the place of the string, and answering nothing leaves the string alone. The
condition may be a closure, which is handed the string as well.

> `pipe()` wraps what the callback answers, so the chain carries on. `when()`
> hands it back as it is, so a callback that answers with a plain string ends the
> chain there.

### Do Not Test It for Truth

A fluent string is an object, so it is always true, even when the string inside
it is empty. Ask it instead:

```php
if (Str::of($value)->is_not_empty()) {
    // ..
}
```

<a id="macro"></a>

## Macro

You may add your own method to the `Str` component with `Str::macro()`.
Register it once (for example in `application/boot.php`), then call it like any
other method:

```php
Str::macro('shout', function ($value) {
    return strtoupper($value) . '!';
});

Str::shout('rakit'); // 'RAKIT!'
```

A macro is reachable from the fluent string as well, with the string handed to it
as the first argument. One that answers with a string keeps the chain going:

```php
Str::of('rakit')->shout();          // 'RAKIT!'
Str::of('rakit')->shout()->limit(4); // 'RAKI...'
```

> Macro names may not override existing `Str` methods, nor the methods of the
> fluent string, since the same name would otherwise mean one thing for
> `Str::foo()` and another for `Str::of()->foo()`. Trying to do so throws an
> exception.
