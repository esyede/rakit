# Markdown

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Render Markdown File to HTML](#render-markdown-file-to-html)
-   [Parse Markdown String to HTML](#parse-markdown-string-to-html)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

[Markdown](http://daringfireball.net/projects/markdown/) is a syntax for styling text on the web.
With markdown, you can control the display of documents, such as formatting words to be bold
or italic, adding images, creating lists, and more.

Generally, markdown is just plain text with the addition of some non-alphabetic characters, such as `#` or `*`.
The syntax and writing method of markdown can be learned through
[this guide](daringfireball.net/projects/markdown/syntax).

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