# String

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [List Helper](#list-helper)
    -   [Str::after\(\)](#strafter)
    -   [Str::after_last\(\)](#strafter_last)
    -   [Str::before\(\)](#strbefore)
    -   [Str::before_last\(\)](#strbefore_last)
    -   [Str::camel\(\)](#strcamel)
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
    -   [Str::trim\(\)](#strtrim)
    -   [Str::substr\(\)](#strsubstr)
    -   [Str::classify\(\)](#strclassify)
    -   [Str::segments\(\)](#strsegments)
    -   [Str::plural_studly\(\)](#strplural_studly)
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
    -   [Str::snake\(\)](#strsnake)
    -   [Str::start\(\)](#strstart)
    -   [Str::starts_with\(\)](#strstarts_with)
    -   [Str::studly\(\)](#strstudly)
    -   [Str::title\(\)](#strtitle)
-   [Str::uuid\(\)](#struuid)
-   [Str::words\(\)](#strwords)

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

This method returns everything after the given value in a string.
The entire string will be returned if the value is not found in the string:

```php
$slice = Str::after('Rakit PHP framework', 'Rakit'); // ' PHP framework'

$slice = Str::after('Rakit PHP framework', 'Foo Bar'); // 'Rakit PHP framework'
```

<a id="strafter_last"></a>

### Str::after_last()

This method returns everything after the given value in a string.
The entire string will be returned if the value is not found in the string:

```php
$slice = Str::after_last('Foo\Bar', '\\'); // 'Bar'
```

<a id="strbefore"></a>

### Str::before()

This method returns everything before the given value in a string:

```php
$slice = Str::before('Rakit PHP framework', 'PHP framework'); // 'Rakit '
```

<a id="strbefore_last"></a>

### Str::before_last()

This method returns everything before the last occurrence of the given value in a string:

```php
$slice = Str::before_last('Rakit PHP framework', 'PHP'); // 'Rakit '
```

<a id="strcamel"></a>

### Str::camel()

This method converts the given string to camelCase:

```php
$converted = Str::camel('foo_bar'); // fooBar
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

<a id="strwords"></a>

### Str::words()

This method limits the number of words in a string:

```php
return Str::words('You know, I miss you so much.', 3, ' >>>');
// 'You know, I >>>'
```
