# Localization

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Retrieving A Language Line](#mengambil-baris-bahasa)
-   [Placeholder & Replacement](#placeholder--replacement)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

Localization is the process of translating your application into different languages.
The Lang class provides a simple mechanism to help you
organize and retrieve the text of your multilingual application.

All of the language files for your application live under the `application/language directory`.

Within the application/language directory, you should create a directory
for each language your application speaks.

So, for example, if your application speaks English and Indonesian,
you might create `en/` and `id/` directories under the `language/` directory.

Each language directory may contain many different language files.
Each language file is simply an array of string values in that language.

In fact, language files are structured identically to configuration files.

For example, within the `application/language/en/` directory,
you could create a marketing.php file that looks like this:

#### Membuat sebuah file bahasa:

```php
return [

	'welcome' => 'Welcome to our website!',

];
```

Next, you should create a corresponding `marketing.php` file within
the `application/language/id/` directory. The file would look something like this:

```php
return [

	'welcome' => 'Selamat datang di situs kami!',

];
```

Nice! Now you know how to get started setting up your language files and directories.
Let's keep localizing!

<a id="mengambil-baris-bahasa"></a>

## Retrieving A Language Line

#### Retrieving a language line:

```php
echo Lang::line('marketing.welcome')->get();
```

#### Retrieving a language line using the helper:

```php
echo trans('marketing.welcome');
```

Notice how a dot was used to separate `'marketing'` and `'welcome'`?
The text before the dot corresponds to the language file,
while the text after the dot corresponds to a specific string within that file.

Need to retrieve the line in a language other than your default?
Not a problem. Just mention the language to the get method:

Getting a language line in a given language:

```php
echo Lang::line('marketing.welcome')->get('fr');
```

<a id="placeholder--replacement"></a>

## Placeholder & Replacement

Now, let's work on our welcome message. _"Selamat datang di situs kami!"_ is a pretty generic message.
It would be helpful to be able to specify the name of the person we are welcoming.

But, creating a language line for each user of our application would be time-consuming and ridiculous.
Thankfully, you don't have to. You can specify _"placeholders"_ within your language lines.
Place-holders are preceded by a colon:

#### Creating a language line with place-holders:

```php
'welcome' => 'Selamat datang di situs kami, :name!'
```

#### Retrieving a language line with replacements:

```php
echo Lang::line('marketing.welcome', ['name' => 'Budi'])->get();
```

#### Retrieving a language line with replacements using helper:

```php
echo trans('marketing.welcome', ['name' => 'Budi']);
```
