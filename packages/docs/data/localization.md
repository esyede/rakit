# Language Translation

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Language Lines](#retrieving-language-lines)
-   [Placeholder & Replacement](#placeholder--replacement)
-   [Pluralization](#pluralization)
-   [Locale From The URI](#locale-from-the-uri)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `Lang` class retrieves your application's text in several languages. Language
files live in `application/language/`, one folder per language (`en/` and `id/`
ship by default). Each file returns an array, like a config file:

```php
// application/language/en/marketing.php
return [
    'welcome' => 'Welcome to our website!',
];

// application/language/id/marketing.php
return [
    'welcome' => 'Selamat datang di situs kami!',
];
```

The default language is the `language` option in
`application/config/application.php`; the `languages` option lists the other
supported languages.

<a id="retrieving-language-lines"></a>

## Retrieving Language Lines

The part before the first dot is the file name, the rest is the key (nested keys
use more dots, e.g. `messages.user.welcome`):

```php
echo Lang::line('marketing.welcome')->get();

// Shorter, with the trans() helper (or its alias __())
echo trans('marketing.welcome');
```

`trans()` and `__()` return the translated string (like Laravel). `Lang::line()` returns
a `Lang` object that turns into a string when echoed; call `get()` when you need the
string itself. `get()` takes an optional
language and a default for a missing line (otherwise the key is returned):

```php
echo Lang::line('marketing.welcome')->get('fr');

echo Lang::line('marketing.nonexistent')->get(null, 'Default Text');
```

Check whether a line exists, optionally in a given language:

```php
if (Lang::has('marketing.welcome')) {
    // ..
}

if (Lang::has('marketing.welcome', 'fr')) {
    // ..
}
```

Package language files are reached with the `package::` prefix, e.g.
`Lang::line('admin::themes.default_theme')`.

<a id="placeholder--replacement"></a>

## Placeholder & Replacement

A line may contain placeholders prefixed with a colon, filled in when the line is
read:

```php
// application/language/en/marketing.php
return [
    'welcome' => 'Welcome to our website, :name!',
    'order_summary' => 'Hello :name, your order for :count items with total :price',
];
```

```php
echo Lang::line('marketing.welcome', ['name' => 'John'])->get();

echo trans('marketing.order_summary', [
    'name' => 'John',
    'count' => 5,
    'price' => 'Rp 150.000',
]);
// Hello John, your order for 5 items with total Rp 150.000
```

`trans()` also accepts the language as its third argument.

<a id="pluralization"></a>

## Pluralization

Separate the forms of a line with `|`. A form may start with an exact count `{n}` or a range
`[min,max]`, where `*` means no bound:

```php
// application/language/en/cart.php
return [
    'apples' => '{0} No apples|{1} One apple|[2,9] :count apples|[10,*] Many apples',
    'items' => 'One item|:count items',
];
```

`Lang::choice()` (or the `trans_choice()` helper) picks the form for a count and fills `:count`:

```php
echo trans_choice('cart.apples', 0);  // No apples
echo trans_choice('cart.apples', 5);  // 5 apples
echo trans_choice('cart.items', 1);   // One item
echo Lang::choice('cart.items', 3, ['name' => 'Budi'], 'en'); // 3 items
```

Without a range, the first form is the singular and the second the plural. Languages that have
no plural form (Indonesian, Malay, Japanese, Korean, Chinese, Thai, Vietnamese, Turkish ...) use
the first form for every count, so a single form is enough: `':count barang'`.

<a id="locale-from-the-uri"></a>

## Locale From The URI

When the request URI starts with a language from the `languages` option (or the
default `language`), that language is used for the request and the segment is
removed from the URI: `/id/welcome` runs the `welcome` route in `id`. Workers do
the same for every request they serve.

The change fires the `rakit.locale` event:

```php
Hook::listen('rakit.locale', function ($locale) {
    // $locale holds the language now in use
});
```
