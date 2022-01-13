# Membuat URL

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [URL Ke Route](#url-ke-route)
- [URL Ke Method Milik Controller](#url-ke-method-milik-controller)
- [URL Ke Alih Bahasa](#url-ke-alih-bahasa)
- [URL Ke Aset](#url-ke-aset)
- [Helper Lainnya](#helper-lainnya)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar
Ketika membuat view, tentu anda perlu untuk mengarahkan URL ke resource yang anda inginkan,
baik itu gambar, css, javascript atau resource lainnya.

Di bagian ini anda akan dibawa untuk merasakan mudahnya membuat URL tersebut.


#### Mengambil base URL aplikasi:

```php
$url = URL::base();
```

#### Membuat URL dari base URL:

```php
$url = URL::to('user/profile');
```

#### Mengambil URL saat ini:

```php
$url = URL::current();
```

#### Mengambil URL saat ini beserta query string:

```php
$url = URL::full();
```


<a id="url-ke-route"></a>
## URL Ke Route

#### Membuat URL ke named route:

```php
$url = URL::to_route('profile');
```

Terkadang, anda mungkin perlu membuat URL ke named route, tetapi juga perlu menentukan
value yang harus digunakan sebagai pengganti karakter pengganti URI rute.

Mudah sekali mengganti wildcard dengan value yang anda inginkan:

#### Membuat URL ke named route dengan wildcard value:

```php
$url = URL::to_route('profile', [$username]);
```

_Bacaan lebih lanjut:_

- [Named Route](/docs/routing#named-route)


<a id="url-ke-method-milik-controller"></a>
## URL Ke Method Milik Controller

#### Membuat URL ke sebuah method milik controller:

```php
$url = URL::to_action('user@profile');
```

#### Membuat URL ke method milik controller dengan wildcard value:

```php
$url = URL::to_action('user@profile', [$username]);
```


<a id="url-ke-alih-bahasa"></a>
## URL Ke Alih Bahasa

#### Membuat URL ke halaman yang sama dalam bahasa lain:

```php
$url = URL::to_language('fr');
```

#### Membuat URL ke home page dalam bahasa lain:

```php
$url = URL::to_language('fr', true);
```


<a id="url-ke-aset"></a>
## URL Ke Aset

URL yang dibuat untuk aset tidak akan mengandung value milik konfigurasi `application.index`.

#### Membuat URL ke aset:

```php
$url = URL::to_asset('js/jquery.js');
```


<a id="helper-lainnya"></a>
## Helper Lainnya

Kami juga telah menyediakan fungsi global yang dapat dimanfaatkan agar mempermudah pekerjaan anda dalam pembuatan URL:

#### Membuat URL dari base URL:

```php
$url = url('user/profile');
```

#### Membuat URL ke sebuah aset:

```php
$url = asset('js/jquery.js');
```

#### Membuat URL ke named route:

```php
$url = route('profile');
```

#### Membuat URL ke named route dengan wildcard value:

```php
$url = route('profile', [$username]);
```

#### Membuat URL ke method milik controller:

```php
$url = action('user@profile');
```

#### Membuat URL ke method milik controller dengan wildcard value:

```php
$url = action('user@profile', [$username]);
```
