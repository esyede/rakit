# Memeriksa Request

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Bekerja Dengan URI](#bekerja-dengan-uri)
-   [Helper Lainnya](#helper-lainnya)

<!-- /MarkdownTOC -->

<a id="bekerja-dengan-uri"></a>

## Bekerja Dengan URI

#### Mengambil URI saat ini:

```php
echo URI::current();
```

#### Mengambil segmen URI tertentu:

```php
echo URI::segment(1);
```

#### Menetukan default value jika segmen tidak ditemukan:

```php
echo URI::segment(10, 'Foo');
```

#### Mengambil seluruh URI, termasuk query string:

```php
echo URI::full();
```

Kadang kala anda mungkin ingin memeriksa apakah URI saat ini sama, atau dimulai dengan string yang anda
berikan. Untuk menangani hal seperti ini, silahkan gunakan method `is()` seperti contoh dibawah ini:

#### Memeriksa apakah URI saat ini adalah "home":

```php
if (URI::is('home')) {
    // URI saat ini adalah: 'home'
}
```

#### Memeriksa apakah URI saat ini dimulai dengan "docs/":

```php
if (URI::is('docs/*')) {
    // URI saat ini diawali dengan: 'docs/'
}
```

<a id="helper-lainnya"></a>

## Helper Lainnya

#### Mengambil method request saat ini:

```php
echo Request::method();
```

#### Mengakses variabel global `$_SERVER`:

```php
echo Request::server('http_referer');
```

#### Mengambil IP si pengirim request:

```php
echo Request::ip();
```

#### Memeriksa apakah request saat ini dikirim via HTTPS:

```php
if (Request::secure()) {
	// Request ini dikirim via HTTPS!
}
```

#### Memeriksa apakah request saat ini dikirim via AJAX:

```php
if (Request::ajax()) {
	// Request ini dikirim via AJAX!
}
```

#### Memeriksa apakah request saat ini datang dari CLI console:

```php
if (Request::cli()) {
	// Request ini datang dari CLI!
}
```
