# Language Translation

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Language Lines](#retrieving-language-lines)
-   [Placeholder & Replacement](#placeholder--replacement)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Language translation (localization) is the process of translating your application into various languages. The `Lang` component provides a simple mechanism to help you organize and retrieve multilingual text.

All language files for your application are located in the `application/language/` directory. Within that directory, you should create folders for each language your application supports.

For example, if your application supports English and Indonesian, you can create `en/` and `id/` directories within the `language/` directory. By default, these two languages are already provided. You can add other languages as needed.

Each language file is an associative array containing strings in the respective language. Language files have the same structure as configuration files. For example, in the `application/language/en/` directory, you can create a `marketing.php` file that looks like this:

#### Creating a language file:

```php
return [

	'welcome' => 'Welcome to our website!',

];
```

Next, you should create the same `marketing.php` file in the `application/language/id/` directory. That file will look like this:

```php
return [

	'welcome' => 'Selamat datang di situs kami!',

];
```

Great! Now you know how to create language files. It's very easy, isn't it?

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

Notice how the dot (period) is used to separate `marketing` and `welcome`? The text before the dot refers to the language file name, while the text after the dot refers to a specific key within that file.

#### Retrieving a line with default fallback:

```php
// If the line is not found, return the default value
echo Lang::line('marketing.nonexistent')->get(null, 'Default Text');
```

If you want to retrieve a line in a language other than the default, no problem. Just specify the desired language to the `get()` method:

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

The `has()` method is useful for checking the availability of a language line before using it.

<a id="placeholder--replacement"></a>

## Placeholder & Replacement

Now, let's create a more specific welcome message. _"Welcome to our website!"_ is too general. It would be better if you can mention the person's name.

However, creating language lines for every user in your application would be time-consuming and inefficient. Fortunately, you don't need to do that. You can define _placeholders_ in language lines. Placeholders are prefixed with a colon (`:`)

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