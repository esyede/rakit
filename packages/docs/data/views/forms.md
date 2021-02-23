# Membuat Formulir

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Membuka Form](#membuka-form)
- [Proteksi CSRF](#proteksi-csrf)
- [Label](#label)
- [Text Input, Text Area, Password & Hidden Input](#text-input-text-area-password--hidden-input)
- [Checkbox & Radio](#checkbox--radio)
- [File Input](#file-input)
- [Drop-down List](#drop-down-list)
- [Button](#button)
- [Menambahkan Custom Element](#menambahkan-custom-element)

<!-- /MarkdownTOC -->



<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Dalam dunia web, file view tentu sangat erat hubungannya dengn formulir HTML. Meskipun anda dapat
membuat formulir dengan menulis tag-tag HTML secara langsung, rakit juga menyediakan komponen untuk
menangani pekerjaan pembutan formulir ini, dengan beberapa tambahan fitur yang menarik tentunya.



>  Semua data input yang ditampilkan dalam library Form ini sudah otomatis tersaring dengan
   bantuan method `HTML::entities()`.


<a id="membuka-form"></a>
## Membuka Form

#### Membuka form `POST` untuk URL saat ini:

```php
echo Form::open();
```


#### Membuka form untuk URL dan tipe request tertentu:

```php
echo Form::open('user/profile', 'PUT');
```


#### Menambahkan atribut html pada form:

```php
echo Form::open('user/profile', 'POST', ['class' => 'form-user']);
```


#### Membuka form `FILES` untuk kebutuhan upload:

```php
echo Form::open_for_files('users/profile');
```


#### Menutup form:

```php
echo Form::close();
```


<a id="proteksi-csrf"></a>
## Proteksi CSRF

Rakit menyediakan metode yang mudah untuk melindungi aplikasi anda
dari [CSRF Attack](http://en.wikipedia.org/wiki/Cross-site_request_forgery).
Pertama, sebuah token akan ditambakan kedalam session milik user. Jangan khawatir,
ini dilakukan secara otomatis. Berikutnya, anda tinggal gunakan method `token()` membuat
hidden input berisi token pada formulir anda:


#### Membuat hidden input berisi token pada formulir:

```php
echo Form::token();
```


#### Melampirkan middleware CSRF ke rute:

```php
Route::post('profile', ['before' => 'csrf', function () {
	// ..
}]);
```


#### Mengambil string CSRF token:

```php
$token = Session::token();
```

>  Anda harus mengkonfigurasi driver session sebelum menggunakan fasilitas proteksi CSRF ini.

_Bacaan lebih lanjut:_

- [Middleware](/docs/routing#middleware)


<a id="label"></a>
## Label

#### Membuat label:

```php
echo Form::label('email', 'E-Mail');
```


#### Menambahkan atribut HTML ke label:

```php
echo Form::label('email', 'E-Mail', ['class' => 'awesome']);
```

>  Setelah anda membuat label, setiap elemen lain yang anda buat, jika ia memiliki nama
   yang sama dengan nama label ini, maka elemen tersebut akan secara otomatis ditambahkan
   atribut `'id'` sesuai nama label.


<a id="text-input-text-area-password--hidden-input"></a>
## Text Input, Text Area, Password & Hidden Input

#### Membuat text input:

```php
echo Form::text('username');
```


#### Menambahkan default value ke text input:

```php
echo Form::text('email', 'example@gmail.com');
```

>  Urutan parameter pada method `hidden()` dan `textarea()` sama saja dengan milik
   method `text()` method. Sehingga cara pemakaiannya juga sama persis.


#### Membuat input password:

```php
echo Form::password('password');
```


<a id="checkbox--radio"></a>
## Checkbox & Radio


#### Membuat checkbox:

```php
echo Form::checkbox('name', 'value');
```


#### Membuat checkbox yang secara default sudah ter-centang:

```php
echo Form::checkbox('name', 'value', true);
```

>  Urutan parameter pada method `radio()` judga sama saja dengan milik method `checkbox()` method.
   Sehingga cara pemakaiannya juga sama persis.


<a id="file-input"></a>
## File Input


#### Membuat input file:

```php
echo Form::file('image');
```

<a id="drop-down-list"></a>
## Drop-down List


#### Membuat drop-down list dengan array:

```php
echo Form::select('size', ['L' => 'Large', 'S' => 'Small']);
```


#### Membuat drop-down list dengan default value:

```php
echo Form::select('size', ['L' => 'Large', 'S' => 'Small'], 'S');
```


<a id="button"></a>
## Button


#### Membuat submit button:

```php
echo Form::submit('Kirim Komentar');
```

>  Perlu membuat elemen `<button>`? Gunakan saja method `button()`. Urutan parameternya
   juga sama saja dengan milik `submit()`.


<a id="menambahkan-custom-element"></a>
## Menambahkan Custom Element

Jika fitur library ini dirasa kurang, anda juga dapat membuat custom elemen anda sendiri.
Caranya dengan memanfaatkan mehod `macro()` dan `Closure`. Contohnya seperti ini:


#### Menambahkan custom element bernama `my_field()`:

```php
Form::macro('my_field', function () {
	return '<input type="awesome">';
});
```

Sekarang, anda sudah bisa memanggil custom element tersebut:


#### Memanggil custom element:

```php
echo Form::my_field();
```
