# Markdown

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Rendering File To HTML](#render-file-markdown-ke-html)
- [Rendering String To HTML](#parse-string-markdown-ke-html)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

[Markdown](http://daringfireball.net/projects/markdown/) is a syntax for styling text on the web.
With markdown, you can control the appearance of the document, such as formatting words in bold
or italics, add images, create listings and more.


Generally, markdown is just plain text with the addition of a few non-alphabet characters,
such as `#` or `*`. The syntax and how to write markdown can be learned through
[this guide](onlinefireball.net/projects/markdown/syntax).



<a id="render-file-markdown-ke-html"></a>
## Rendering File To HTML

To render a markdown file as an HTML string, simply pass the file path like this:


```php
$file = 'path/to/file.md';

$html = Markdown::render($file);
```


<a id="parse-string-markdown-ke-html"></a>
## Rendering String To HTML

If you only need to render the markdown string, just use the `parse()` method like this:


```php
$string = '_lorem_ ipsum **dolor** sit amet';

$html = Markdown::parse($string);
```

> By default, the two methods above do not filter rogue input from the user.
  Using this library to render user input strings is strongly discouraged.

