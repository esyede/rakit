# Markdown

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Render File Markdown Ke HTML](#render-file-markdown-ke-html)
- [Parse String Markdown Ke HTML](#parse-string-markdown-ke-html)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

[Markdown](http://daringfireball.net/projects/markdown/) adalah sintaks untuk mengatur gaya teks di web.
Dengan markdown, anda dapat mengontrol tampilan dokumen, seperti memformat kata-kata menjadi tebal
atau miring, menambahkan gambar, membuat listing dan lainnya.

Umumnya, markdown hanyalah teks biasa dengan penambahan beberapa karakter non-alfabet, seperti `#` atau `*`.
Sintaks dan cara penulisan markdown dapat dipelajari melalui
[panduan ini](daringfireball.net/projects/markdown/syntax).


<a id="render-file-markdown-ke-html"></a>
## Render File Markdown Ke HTML

Untuk me-render file markdown menjadi string HTML, cukup oper path filenya seperti ini:

```php
$file = 'path/to/file.md';

$html = Markdown::render($file);
```


<a id="parse-string-markdown-ke-html"></a>
## Parse String Markdown Ke HTML

Jika anda hanya perlu me-render string markdown, cukup gunakan method `parse()` seperti ini:

```php
$string = '_lorem_ ipsum **dolor** sit amet';

$html = Markdown::parse($string);
```

> Secara default, kedua method diatas tidak menyaring input nakal dari user.
  Penggunaan library ini untuk me-render string inputan user sangat tidak disarankan.
