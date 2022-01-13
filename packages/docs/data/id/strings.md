# String

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [List Helper](#list-helper)
    - [Str::after\(\)](#strafter)
    - [Str::after_last\(\)](#strafter_last)
    - [Str::before\(\)](#strbefore)
    - [Str::before_last\(\)](#strbefore_last)
    - [Str::camel\(\)](#strcamel)
    - [Str::contains\(\)](#strcontains)
    - [Str::contains_all\(\)](#strcontains_all)
    - [Str::ends_with\(\)](#strends_with)
    - [Str::finish\(\)](#strfinish)
    - [Str::is\(\)](#stris)
    - [Str::ucfirst\(\)](#strucfirst)
    - [Str::kebab\(\)](#strkebab)
    - [Str::limit\(\)](#strlimit)
    - [Str::plural\(\)](#strplural)
    - [Str::random\(\)](#strrandom)
    - [Str::replace_array\(\)](#strreplace_array)
    - [Str::replace_first\(\)](#strreplace_first)
    - [Str::replace_last\(\)](#strreplace_last)
    - [Str::singular\(\)](#strsingular)
    - [Str::slug\(\)](#strslug)
    - [Str::snake\(\)](#strsnake)
    - [Str::start\(\)](#strstart)
    - [Str::starts_with\(\)](#strstarts_with)
    - [Str::studly\(\)](#strstudly)
    - [Str::title\(\)](#strtitle)
    - [Str::uuid\(\)](#struuid)
    - [Str::words\(\)](#strwords)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Dalam proses pengembangan web, tentu anda kan sangat sering memanipulasi string.
Contohnya ketika anda ingin mengubah sebuah string agar ia ramah URL atau ketika
anda ingin memotong sebuah string.

Komponen ini menyediakan sekumpulan method untuk mebantu pekerjaan manipulasi string
menjadi lebih muah dan sederhana. Mari kita lihat apa saja yang tersedia:



<a id="list-helper"></a>
## List Helper

Berikut dalah daftar helper yang tersedia untuk komponen ini:


<a id="strafter"></a>
### Str::after()

Method ini mereturn semuanya setelah value yang diberikan dalam sebuah string.
Seluruh string akan direturn jika valuenya tidak ada di dalam string:

```php
$slice = Str::after('Rakit PHP framework', 'Rakit'); // ' PHP framework'

$slice = Str::after('Rakit PHP framework', 'Foo Bar'); // 'Rakit PHP framework'
```


<a id="strafter_last"></a>
### Str::after_last()

Method ini mereturn semuanya setelah value yang diberikan dalam sebuah string.
Seluruh string akan direturn jika valuenya tidak ada di dalam string:

```php
$slice = Str::after_last('Foo\Bar', '\\'); // 'Bar'
```


<a id="strbefore"></a>
### Str::before()

Methode ini mereturn semuanya sebelum value yang diberikan dalam sebuah string:

```php
$slice = Str::before('Rakit PHP framework', 'PHP framework'); // 'Rakit '
```


<a id="strbefore_last"></a>
### Str::before_last()

Method ini mereturn semuanya sebelum kemunculan terakhir dari value yang diberikan dalam sebuah string:

```php
$slice = Str::before_last('Rakit PHP framework', 'PHP'); // 'Rakit '
```


<a id="strcamel"></a>
### Str::camel()

Method ini mengubah string yang diberikan menjadi camelCase:

```php
$converted = Str::camel('foo_bar'); // fooBar
```


<a id="strcontains"></a>
### Str::contains()

Method ini memeriksa apakah sebuah string berisi value yang diberikan (case sensitive):

```php
$contains = Str::contains('Rakit PHP framework', 'PHP'); // true

$contains = Str::contains('Rakit PHP framework', 'php'); // false

$contains = Str::contains('Rakit PHP framework', 'foo'); // false
```

Anda juga dapat mengoper array untuk memeriksa apakah string yang diberikan berisi salah satu value:

```php
$contains = Str::contains('Rakit PHP framework', ['framework', 'foo']); // true
```


<a id="strcontains_all"></a>
### Str::contains_all()

Method ini memeriksa apakah string yang diberikan berisi semua valuenya:

```php
$contains_all = Str::contains_all('Rakit PHP framework', ['Rakit', 'PHP']); // true

$contains_all = Str::contains_all('Rakit PHP framework', ['Rakit', 'foo']); // false
```


<a id="strends_with"></a>
### Str::ends_with()

Method ini memeriksa apakah sebuah string diakhiri dengan value yang diberikan:

```php
$result = Str::ends_with('Rakit PHP framework', 'framework'); // true
```

Anda juga dapat mengoper array untuk memeriksa apakah sebuah string diakhiri dengan salah satu valuenya:

```php
$result = Str::ends_with('Rakit PHP framework', ['framework', 'foo']); // true

$result = Str::ends_with('Rakit PHP framework', ['php', 'foo']); // false
```


<a id="strfinish"></a>
### Str::finish()

Method ini menambahkan value ke akhir string jika si string belum diakhiri dengan value tersebut:

```php
$adjusted = Str::finish('this/string', '/');  // this/string/

$adjusted = Str::finish('this/string/', '/'); // this/string/
```


<a id="stris"></a>
### Str::is()

Method ini memeriksa apakah sebuah string cocok dengan pola yang diberikan. Tanda `*` (asterisk)
dapat digunakan untuk wildcard:

```php
$matches = Str::is('foo*', 'foobar'); // true

$matches = Str::is('baz*', 'foobar'); // false
```


<a id="strucfirst"></a>
### Str::ucfirst()

Method ini mereturn string yang diberikan dengan karakter pertama yang dikapitalisasi:

```php
$string = Str::ucfirst('foo bar'); // Foo bar
```


<a id="strkebab"></a>
### Str::kebab()

Method ini mengubah string yang diberikan menjadi kebab-case:

```php
$converted = Str::kebab('fooBar'); // foo-bar
```


<a id="strlimit"></a>
### Str::limit()

Method ini memotong string sebanyak panjang yang ditentukan:

```php
$truncated = Str::limit('The quick brown fox jumps over the lazy dog', 20);
// The quick brown fox...
```

Anda juga dapat mengoper parameter ketiga untuk mengubah string akhiran:

```php
$truncated = Str::limit('The quick brown fox jumps over the lazy dog', 20, ' (...)');
// The quick brown fox (...)
```


<a id="strplural"></a>
### Str::plural()

Method ini mengubah string kata tunggal menjadi bentuk jamaknya. Ini hanya mendukung bahasa Inggris:
```php
$plural = Str::plural('car');   // cars

$plural = Str::plural('child'); // children
```


<a id="strrandom"></a>
### Str::random()

Method ini menghasilkan string acak dengan panjang yang ditentukan:

```php
$random = Str::random(16); // 'VvhHyKNIp4qUTfmK ' (dibuat secara acak)
```


<a id="strreplace_array"></a>
### Str::replace_array()

Method ini mengganti value dalam string secara berurutan menggunakan array:

```php
$string = 'Tayang setiap hari pukul ? dan ? WIB';

$replaced = Str::replace_array('?', ['8:30', '21:00'], $string);
// Tayang setiap hari pukul 8:30 dan 21:00 WIB
```


<a id="strreplace_first"></a>
### Str::replace_first()

Metode ini mengganti kemunculan pertama value pada string:

```php
$replaced = Str::replace_first('the', 'a', 'the quick brown fox jumps over the lazy dog');
// a quick brown fox jumps over the lazy dog
```


<a id="strreplace_last"></a>
### Str::replace_last()

Metode ini mengganti kemunculan terakhir value pada string:

```php
$replaced = Str::replace_last('the', 'a', 'the quick brown fox jumps over the lazy dog');
// the quick brown fox jumps over a lazy dog
```


<a id="strsingular"></a>
### Str::singular()

Method ini mengubah string menjadi bentuk kata tunggal. Ini hanya mendukung bahasa Inggris:

```php
$singular = Str::singular('cars'); // car

$singular = Str::singular('children'); // child
```


<a id="strslug"></a>
### Str::slug()

Method ini mengubah string yang diberikan menjadi string yang ramah URL:

```php
$slug = Str::slug('Hello World', '-'); // hello-world
```


<a id="strsnake"></a>
### Str::snake()

Method ini mengubah string yang diberikan menjadi snake_case:

```php
$converted = Str::snake('fooBar'); // foo_bar
```


<a id="strstart"></a>
### Str::start()

Method ini menambahkan value ke awal string jika si string belum diawali dengan value tersebut:

```php
$adjusted = Str::start('this/string', '/'); // /this/string

$adjusted = Str::start('/this/string', '/'); // /this/string
```


<a id="strstarts_with"></a>
### Str::starts_with()

Method ini memeriksa apakah sebuah string diawali dengan value yang diberikan:

```php
$result = Str::starts_with('Rakit PHP framework', 'Rakit'); // true
```


<a id="strstudly"></a>
### Str::studly()

Method ini mengubah string yang diberikan menjadi StudlyCase:

```php
$converted = Str::studly('foo_bar'); // FooBar
```


<a id="strtitle"></a>
### Str::title()

Method ini mengubah string yang diberikan menjadi Title Case:

```php
$converted = Str::title('selamat pagi indonesia');
// Selamat Pagi Indonesia
```


<a id="struuid"></a>
### Str::uuid()

Method ini menghasilkan string UUID (versi 4):

```php
return Str::uuid(); // a0a2a2d2-0b87-4a18-83f2-2529882be2de (dibuat secara acak)
```


<a id="strwords"></a>
### Str::words()

Method ini membatasi jumlah kata dalam sebuah string:

```php
return Str::words('Kamu tahu, aku sangat rindu.', 3, ' >>>');
// 'Kamu tahu, aku >>>'
```
