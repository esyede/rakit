# Menyusun Kode HTML

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Entities](#entities)
- [Javascript dan CSS](#javascript-dan-css)
- [Link](#link)
- [Link Ke Named Route](#link-ke-named-route)
- [Link Ke Action Controller](#link-ke-action-controller)
- [Link Ke Bahasa Lain](#link-ke-bahasa-lain)
- [Mailto](#mailto)
- [Image](#image)
- [Listing](#listing)
- [Macro Baru](#macro-baru)

<!-- /MarkdownTOC -->


<a id="entities"></a>
## Entities

Saat menampilkan inputan user di view, biasakanlah untuk mengkonversi semua karakter HTML
ke representasi entitiesnya. Sebagai contoh, simbol ` < ` harus dikonversi ke representasi entitiesnya.
Hal ini penting agar aplikasi anda terlindung
dari [XSS Attack](https://en.wikipedia.org/wiki/Cross-site_scripting).


#### Mengonversi string ke representasi entitiesnya:

```php
echo HTML::entities('<script>alert("aing maung!");</script>');
```


#### Menggunakan helper `e()` untuk entities:

```php
echo e('<script>alert("Roarrr!! Aing maung!");</script>');
```


<a id="javascript-dan-css"></a>
## Javascript dan CSS


#### Mengimpor file javascript:

```php
echo HTML::script('js/jquery.min.js');
```


#### Mengimpor file CSS:

```php
echo HTML::style('css/bootstrap.min.css');
```


#### Mengimpor file CSS dengan menambahkan media type:

```php
echo HTML::style('css/bootstrap.min.css', ['media' => 'print']);
```

_Bacaan lebih lanjut:_

- _[Mengelola Aset](/docs/views/assets)_



<a id="link"></a>
## Link


#### Membuat link ke sebuah URI:

```php
echo HTML::link('user/profile', 'Profil');
```


#### Membuat link dengan menyertakan tag HTML tambahan:

```php
echo HTML::link('user/profile', 'Profil', ['id' => 'profile_link']);
```


<a id="link-ke-named-route"></a>
## Link Ke Named Route


#### Membuat link ke named route:

```php
echo HTML::link_to_route('profile');
```


#### Membuat link ke named route dengan wildcard value:

```php
$url = HTML::link_to_route('profile', 'Profil', [$username]);
```

_Bacaan lebih lanjut:_

- _[Named Route](/docs/routing#named-route)_



<a id="link-ke-action-controller"></a>
## Link Ke Action Controller


#### Membuat link ke sebuah action milik controller:

```php
echo HTML::link_to_action('home@index');
```


<a id="membuat-link-action-controller-dengan-wildcard-values"></a>
#### Membuat link action controller dengan wildcard values:

```php
echo HTML::link_to_action('user@profile', 'Profil', [$username]);
```


<a id="link-ke-bahasa-lain"></a>
## Link Ke Bahasa Lain


#### Membuat link ke halaman yang sama menggunakan bahasa lain:

```php
echo HTML::link_to_language('id');
```


#### Membuat link ke beranda menggunakan bahasa lain

```php
echo HTML::link_to_language('id', true);
```


<a id="mailto"></a>
## Mailto

Method `mailto()` melindungi alamat email yang diberikan sehingga tidak bisa di-sniff oleh bot.


#### Membuat link mailto:

```php
echo HTML::mailto('example@gmail.com', 'Kontak via E-Mail');
```


#### Membuat link mailto menggunakan alamat email sebagai judul linknya:

```php
echo HTML::mailto('example@gmail.com');
```


<a id="image"></a>
## Image


#### Membuat tag HTML image:

```php
echo HTML::image('img/smile.jpg', $alt_text);
```


#### Membuat tag HTML image dengan atribut tambahan:

```php
echo HTML::image('img/smile.jpg', $alt_text, ['id' => 'smile']);
```


<a id="listing"></a>
## Listing


#### Membuat listing dari data array:

```php
echo HTML::ol(['Monitor', 'Mouse', 'Keyboard']);

echo HTML::ul('Ubuntu', 'Mac OS', 'Windows');

echo HTML::dl(['Ubuntu' => 'Cannonical', 'Mac OS' => 'Apple', 'Windows' => 'Microsoft']);
```


<a id="macro-baru"></a>
## Macro Baru

Jika elemen-elemen yang tersedia dirasa kurang, anda dapat menambahkan macro baru sesuai keinginan.
Caranya sangat mudah. Anda cukup daftarkan nama macro dan `Closure` sebagai proses macronya:


#### Mendaftarkan macro baru:

```php
HTML::macro('awesome', function () {
	return '<article type="awesome">';
});
```

Sekarang, anda sudah bisa memanggil makro anda menggunakan namanya:


#### Memanggil macro baru:

```php
echo HTML::awesome();
```
