# Language Translation

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Language Lines](#retrieving-language-lines)
-   [Placeholder & Replacement](#placeholder--replacement)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Lang` organizes and retrieves the text of an application translated into several
languages.

Language files live in `application/language/`, one folder per language. `en/` and
`id/` ship by default; add the others you need.

Each file returns an associative array, the same shape as a configuration file. For
example, `application/language/en/marketing.php`:

#### Creating a language file:

```php
return [

	'welcome' => 'Welcome to our website!',

];
```

The same file in `application/language/id/`:

```php
return [

	'welcome' => 'Selamat datang di situs kami!',

];
```

<a id="retrieving-language-lines"></a>

## Retrieving Language Lines

#### Retrieving a language line:

```php
echo Lang::line('marketing.welcome')->get();
```

#### Retrieving a language line using helper for shorter writing:

```php
echo trans('marketing.welcome');
```

The part before the dot is the file name, the rest is the key inside it.

#### Retrieving a line with default fallback:

```php
// If the line is not found, return the default value
echo Lang::line('marketing.nonexistent')->get(null, 'Default Text');
```

To read a line in another language, name it in `get()`:

#### Retrieving a line in a specific language:

```php
echo Lang::line('marketing.welcome')->get('fr');
```

#### Checking if a language line exists:

```php
// Check if the language line is available
if (Lang::has('marketing.welcome')) {
    echo 'Language line available';
}

// Check in a specific language
if (Lang::has('marketing.welcome', 'fr')) {
    echo 'Language line available in French';
}
```

<a id="placeholder--replacement"></a>

## Placeholder & Replacement

A line such as _"Welcome to our website!"_ is often too general, but writing one per
user is not an option. Instead, a line may carry _placeholders_, written with a
leading colon (`:`), that are filled in when the line is read.

#### Creating a language line with placeholders:

```php
'welcome' => 'Welcome to our website, :name!'
```

#### Retrieving a language line with replacement:

```php
echo Lang::line('marketing.welcome', ['name' => 'John'])->get();
```

#### Retrieving a language line with replacement using helper:

```php
echo trans('marketing.welcome', ['name' => 'John']);
```

#### Using multiple placeholders:

```php
// File: application/language/id/marketing.php
return [
    'order_summary' => 'Hello :name, your order for :count items with total :price',
];

// Usage:
echo trans('marketing.order_summary', [
    'name' => 'John',
    'count' => 5,
    'price' => 'Rp 150.000',
]);

// Output: "Hello John, your order for 5 items with total Rp 150.000"
```

#### Using nested keys:

```php
// File: application/language/id/messages.php
return [
    'user' => [
        'welcome' => 'Welcome back, :name!',
        'goodbye' => 'Goodbye, :name!',
    ],
];

// Usage with dot notation:
echo trans('messages.user.welcome', ['name' => 'John']);
```

> **Note:** The application's default language can be set in the `application/config/application.php` file under the `'language'` key. The list of available languages is set under the `'languages'` key.