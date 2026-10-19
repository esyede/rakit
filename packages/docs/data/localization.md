# Language Translation

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Language Lines](#retrieving-language-lines)
-   [Placeholder & Replacement](#placeholder--replacement)
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
