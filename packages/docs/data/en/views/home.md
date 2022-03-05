# View & Respon

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Binding Data Ke View](#binding-data-ke-view)
- [Nested View](#nested-view)
- [Named View](#named-view)
- [View Composer](#view-composer)
- [Redirect](#redirect)
- [Redirect Dengan Flash Data](#redirect-dengan-flash-data)
- [Respon Download](#respon-download)
- [Respon Error](#respon-error)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

View berisi HTML yang ditampilkan ke pengunjung situs anda. Dengan memisahkan view dari logika bisnis,
kode anda akan lebih bersih dan lebih mudah dikelola.

View disimpan dalam direktori `views/` dan menggunakan ekstensi PHP biasa. Komponen `View` menyediakan
cara sederhana untuk mengambil file view dan menampilkannya ke pengunjung.

#### Membuat view:

```html
<html>
	Aku disimpan di views/home/index.php!
</html>
```


#### Mereturn view dari sebuah rute:

```php
Route::get('/', function () {
	return View::make('home.index');
});
```


#### Mereturn view dari dalam action controller:

```php
public function action_index()
{
	return View::make('home.index');
}
```


#### Memeriksa apakah view ada:

```php
if (View::exists('home.index')) {
    // Yay! view home/index.php ada!
}
```

Terkadang anda perlu kendali lebih atas respon yang dikirim ke browser. Misalnya, anda mungkin perlu
mengirim custom header, atau mengubah HTTP status code. Begini caranya:


#### Mereturn respon kustom:

```php
Route::get('/', function () {
	$headers = ['Cache-Control' => 'max-age=3600'];

	return Response::make('Hello World!', 200, $headers);
});
```


#### Mereturn respon kustom yang berisi view, dengan binding data:

```php
return Response::view('home', ['foo' => 'bar']);
```


#### Mereturn respon JSON:

```php
return Response::json(['name' => 'Budi']);
```


#### Mereturn respon JSONP:

```php
return Response::jsonp('myCallback', ['name' => 'Budi']);
```


#### Mereturn Model dalam bentuk JSON:

```php
return Response::facile(User::find(1));
```


<a id="binding-data-ke-view"></a>
## Binding Data Ke View

Biasanya, rute atau controller akan meminta data dari model yang perlu ditampilkan oleh view.
Jadi, kita butuh cara untuk mengoper data ke view.

Ada beberapa cara untuk melakukannya, pilih saja cara yang paling anda sukai!

#### Binding data ke sebuah view:

```php
Route::get('/', function () {
	return View::make('home')->with('name', 'Andi');
});
```


#### Mengakses data dari dalam view:

```php
<html>
	Halo, <?php echo $name; ?>.
</html>
```


#### Binding beberapa data ke view:

```php
View::make('home')
	->with('name', 'Andi')
	->with('age', 28)
	->with('hobby', 'Programming');
```


#### Menggunakan array untuk binding data:

```php
View::make('home', ['name' => 'Andi']);
```


#### Memanfaatkan `compact()` untuk binding data:

```php
$name = 'Andi';

View::make('home', compact('name'));
```


#### Memanfaatkan magic method `__set()` untuk binding data:

```php
$view = View::make('home');

$view->name  = 'Andi';

return $view;
```


#### Memanfaatkan `ArrayAccess` untuk binding data:

```php
$view = View::make('home');

$view['name']  = 'Andi';

return $view;
```


<a id="nested-view"></a>
## Nested View

Seperti halnya controller, seringkali anda ingin membuat nested view. Nested view ini kadang-kadang
disebut sebagai partials (parsial) atau potongan-potongan view, ini membantu anda menjaga agar view
tetap ramping dan fleksibel.


#### Membuat nested view menggunakan method `nest()`:

```php
View::make('home')->nest('footer', 'partials.footer');
```


#### Binding data ke nested view:

```php
$view = View::make('home');

$view->nest('content', 'orders', ['orders' => $orders]);
```

Terkadang anda mungkin ingin langsung meng-include suatu view dari dalam view lain.
Anda dapat memanfaatkan helper `render()`:


#### Memanfaatkan helper `render()` untuk menampilkan view:

```php
<div class="content">
	<?php echo render('user.profile'); ?>
</div>
```

Juga sangat umum untuk memiliki view parsial yang bertanggung jawab untuk menampilkan instance data
dalam array.

Misalnya, anda dapat membuat view parsial yang bertanggung jawab untuk menampilkan
detail tentang suatu pesanan. Kemudian, misalnya, anda dapat me-looping array pesanan tersebut,
lalu menampilkan view parsial untuk setiap itemnya.

Caranya sangat sederhana, gunakan helper `render_each()`:


#### Render view parsial untuk setiap item dalam array:

```php
<div class="orders">
	<?php echo render_each('partials.order', $orders, 'order'); ?>
</div>
```

Parameter pertama adalah nama view parsialnya, yang kedua adalah array datanya, dan yang ketiga adalah
nama variabel yang harus digunakan ketika setiap item array dioperkan ke view parsial.


<a id="named-view"></a>
## Named View

Named view (view dengan nama) dapat membantu membuat kode anda lebih terorganisir.
Penggunaannya juga sangat mudah:


#### Mendaftarkan sebuah named view:

```php
View::name('layouts.default', 'layout');
```


#### Mengambil instance dari named view:

```php
return View::of('layout');
```


#### Binding data ke named view:

```php
return View::of('layout', ['orders' => $orders]);
```


<a id="view-composer"></a>
## View Composer

Tunggu dulu, kita tidak sedang membicarakan [composer yang itu](https://getcomposer.org/).

Setiap kali suatu view dimuat, event `'composer'`-nya akan otomatis terpanggil. Anda dapat me-listen
event tersebut dan menggunakannya untuk kebutuhan - kebutuhan khusus pada aplikasi anda.

Penggunaan umum fitur ini contohnya pada view parsial navigasi sidebar yang memperlihatkan daftar
posting blog secara acak. Anda dapat membuat nested view parsial dengan memuatnya dalam layout view anda.
Kemudian, daftarkan composer untuk view parsial tersebut.

Composer kemudian dapat meng-query tabel 'post' dan mengambil semua data yang diperlukan untuk
me-render view anda. Jadi, tidak ada lagi kode-kode acak yang bertebaran di dalam view!

View composer biasanya didaftarkan di file `composers.php`. Contohnya seperti ini:


#### Mendaftarkan sebuah view composer untuk view "home":

```php
View::composer('home', function ($view) {
	$view->nest('footer', 'partials.footer');
});
```

Sekarang setiap kali view `'home'` dimuat, sebuah instance dari kelas `View` akan dioper ke closure
yang telah anda daftarkan diatas, sehingga anda dapat menyiapkan view sesuai keinginan anda.


#### Mendaftarkan sebuah composer yang menangani beberapa view:

```php
View::composer(['home', 'profile'], function ($view) {
	// ..
});
```

>  Sebuah file view boleh memiliki lebih dari satu composer.



<a id="redirect"></a>
## Redirect

Penting untuk dicatat bahwa baik rute maupun controller memerlukan respon untuk di-return.
Alih-alih hanya memanggil `Redirect::to()` ketika anda ingin me-redirect user, anda perlu memanggil
`return Redirect::to()`.

Perbedaan ini penting karena berbeda dari kebanyakan framework PHP lainnya, dan mungkin mudah untuk
secara tidak sengaja mengabaikan pentingnya praktik `return` ini.


#### Redirect ke URI lain:

```php
return Redirect::to('user/profile');
```


#### Redirect dengan HTTP status kustom:

```php
return Redirect::to('user/profile', 301);
```


#### Redirect ke root aplikasi:

```php
return Redirect::home();
```


#### Redirect kembali ke action sebelumnya:

```php
return Redirect::back();
```


#### Redirect ke named route:

```php
return Redirect::to_route('profile');
```


#### Redirect ke action controller:

```php
return Redirect::to_action('home@index');
```

Terkadang anda mungkin perlu me-redirect ke named route, tetapi juga perlu menentukan parameter apa
yang harus digunakan, selain URI wildcard bawaan rute. Juga sangat mudah untuk mengganti wildcard
dengan parameter yang anda inginkan:


#### Redirect ke named route dengan wildcard value:

```php
return Redirect::to_route('profile', [$username]);
```


#### Redirect ke action dengan wildcard value:

```php
return Redirect::to_action('user@profile', [$username]);
```


<a id="redirect-dengan-flash-data"></a>
## Redirect Dengan Flash Data

Setelah user membuat akun ataupun login ke aplikasi anda, biasanya akan ditampilkan pesan
selamat datang atau status lainnya. Tetapi, bagaimana anda bisa mengatur pesan status agar
tetap tersedia untuk request berikutnya?

Gunakan metode `with()` untuk mengirim data flash bersama dengan respon redirect:

```php
return Redirect::to('profile')->with('status', 'Selamat datang kembali!');
```

Anda dapat mengakses pesan ini dari view menggunakan method `Session::get()`:

```php
<?php echo Session::get('status'); ?>
```

_Bacaan lebih lanjut:_

- *[Session](/docs/en/session/config)*


<a id="respon-download"></a>
## Respon Download

Selain mengirim respon berupa HTML maupun JSON, anda juga dapat mengirim respon berupa download file.
Hal ini sangat berguna ketika anda perlu mengirim file ke user tanpa harus menunjukkan dimana
lokasi asli file di server anda.



#### Mengirim respon download file:

```php
return Response::download('path/file.jpg');
```


#### Mengirim download file dan menentukan nama filenya:

```php
return Response::download('path/file.jpg', 'kitten.jpg');
```


<a id="respon-error"></a>
## Respon Error

Untuk menghasilkan respon error yang tepat, cukup tentukan HTTP response code yang ingin anda tampilkan.
Rakit akan mencari view error terkait kedalam folder `application/views/error/` dan
akan secara otomatis menampilkannya.

Jadi, jika view error yang anda butuhkan belum ada, silahkan tambahkan sendiri ke folder tersebut.
Anda juga boleh mengubah tampilannya sesuai kebutuhan.


#### Membuat respon error 404:

```php
return Response::error('404');
```


#### Membuat respon error 500:

```php
return Response::error('500');
```
