# Markdown

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Converting to HTML](#converting-to-html)
-   [Rendering Options](#rendering-options)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

The `Markdown` component converts [Markdown](https://daringfireball.net/projects/markdown/syntax)
text to HTML.

<a id="converting-to-html"></a>
<a id="render-markdown-file-to-html"></a>
<a id="parse-markdown-string-to-html"></a>

## Converting to HTML

`render()` converts a file, `parse()` converts a string:

```php
$html = Markdown::render('path/to/file.md');

$html = Markdown::parse('_lorem_ ipsum **dolor** sit amet');
```

For inline markdown only (no paragraphs or other blocks), use `line()` on the
instance:

```php
$html = Markdown::factory()->line('**bold** text'); // <strong>bold</strong> text
```

> Neither method filters malicious input by default. See the options below before
> rendering anything a user wrote.

<a id="rendering-options"></a>

## Rendering Options

`Markdown::factory()` returns the shared parser instance. Set options on it, then
call `translate()`. Option methods are chainable:

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
> `parse()` and `render()` use the same instance, so options set here stay active
> for the rest of the request.
