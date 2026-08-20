# Markdown

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Render Markdown File to HTML](#render-markdown-file-to-html)
-   [Parse Markdown String to HTML](#parse-markdown-string-to-html)
-   [Rendering Options](#rendering-options)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

[Markdown](https://daringfireball.net/projects/markdown/) is a syntax for styling text on the web.
With markdown, you can control the display of documents, such as formatting words to be bold
or italic, adding images, creating lists, and more.

Generally, markdown is just plain text with the addition of some non-alphabetic characters, such as `#` or `*`.
The syntax and writing method of markdown can be learned through
[this guide](https://daringfireball.net/projects/markdown/syntax).

<a id="render-markdown-file-to-html"></a>

## Render Markdown File to HTML

To render a markdown file into an HTML string, simply pass its path like this:

```php
$file = 'path/to/file.md';

$html = Markdown::render($file);
```

<a id="parse-markdown-string-to-html"></a>

## Parse Markdown String to HTML

If you only need to render a markdown string, just use the `parse()` method like this:

```php
$string = '_lorem_ ipsum **dolor** sit amet';

$html = Markdown::parse($string);
```

> By default, both methods above do not filter malicious input from users.
> Using this library to render user input strings is highly discouraged.

<a id="rendering-options"></a>

## Rendering Options

`Markdown::factory()` returns the parser instance so you can change how it
renders before calling `translate()`. All option methods return the instance, so
they can be chained:

| Method              | Description                                                             |
| ------------------- | ----------------------------------------------------------------------- |
| `breaks($enable)`   | Turn a single newline into `<br>` (default: off)                        |
| `escaping($enable)` | Escape any HTML inside the markdown (default: off)                      |
| `safety($enable)`   | Block unsafe links and attributes, such as `javascript:` (default: off) |
| `linkify($enable)`  | Turn a bare URL into a link (default: on)                               |

```php
$html = Markdown::factory()
    ->escaping(true)
    ->safety(true)
    ->breaks(true)
    ->translate($string);
```

> If the string comes from a user, turn on `escaping()` and `safety()`.
> `Markdown::parse()` and `Markdown::render()` use the same instance, so any
> option set here stays active for the rest of the request.