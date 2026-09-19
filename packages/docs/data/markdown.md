# Markdown

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Render Markdown File to HTML](#render-markdown-file-to-html)
-   [Parse Markdown String to HTML](#parse-markdown-string-to-html)
-   [Rendering Options](#rendering-options)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

[Markdown](https://daringfireball.net/projects/markdown/) is a plain-text syntax for
styling text: bold, italics, images, lists and more. The full syntax is in
[this guide](https://daringfireball.net/projects/markdown/syntax).

<a id="render-markdown-file-to-html"></a>

## Render Markdown File to HTML

Pass the file path:

```php
$file = 'path/to/file.md';

$html = Markdown::render($file);
```

<a id="parse-markdown-string-to-html"></a>

## Parse Markdown String to HTML

For a string in hand, use `parse()`:

```php
$string = '_lorem_ ipsum **dolor** sit amet';

$html = Markdown::parse($string);
```

> Neither method filters malicious input by default. See the options below before
> rendering anything a user wrote.

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