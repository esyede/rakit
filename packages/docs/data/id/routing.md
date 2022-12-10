# Routing

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Routing Dasar](#routing-dasar)
    -   [Route Redirect](#route-redirect)
    -   [Route View](#route-view)
-   [URI Wildcard](#uri-wildcard)
-   [Event 404](#event-404)
-   [Middleware](#middleware)
-   [Middleware Pola URI](#middleware-pola-uri)
-   [Middleware Global](#middleware-global)
-   [Route Grouping](#route-grouping)
-   [Named Route](#named-route)
-   [Routing Paket](#routing-paket)
-   [Routing Controller](#routing-controller)
-   [Route Testing via CLI](#route-testing-via-cli)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Tidak seperti kebanyakan framework lainnya, dengan Rakit memungkinkan untuk menanamkan
logika aplikasi dalam dua cara.

Meskipun controller adalah cara paling umum untuk mengimplementasikan logika aplikasi,
anda juga dapat menanamkan logika anda langsung ke rute menggunakan closure.

Closure ini sangat bagus untuk situs kecil yang hanya berisi beberapa halaman karena anda
tidak harus membuat banyak controller hanya untuk mengekspos setengah lusin method atau
memasukkan beberapa metode yang tidak terkait ke dalam controller yang sama,
dan kemudian harus secara manual menunjuk rutenya satu per satu.

Rute biasanya didefinisikan di dalam file `routes.php`.

Dalam contoh berikut, parameter pertama adalah rute yang anda _"daftarkan"_ ke router.
Parameter kedua adalah closure yang berisi logika untuk rute itu.

Rute didefinisikan tanpa garis miring. Satu-satunya pengecualian adalah rute default
yang diwakili dengan hanya sebuah garis miring.

> Rute dievaluasi per baris sesuai urutannya, jadi daftarkan semua rute "wildcard" di
> bagian bawah file `routes.php` anda.

<a id="routing-dasar"></a>

## Routing Dasar

Rute yang paling dasar menerima URI dan closure, menyediakan metode yang sangat sederhana dan
ekspresif untuk menentukan rute dan perilaku tanpa file konfigurasi routing yang rumit:

#### Mendaftarkan rute `GET`:

```php
Route::get('/', function () {
	return 'Halo dunia!';
});
```

#### Rute valid untuk HTTP method apa pun (`GET`, `POST`, `PUT`, dan `DELETE`):

```php
Route::any('/', function () {
	return 'Halo dunia!';
});
```

#### Mendaftarkan rute untuk HTTP method lainnya:

```php
Route::post('user', function () {
    // ..
});

Route::put('user/(:num)', function ($id) {
    // ..
});

Route::delete('user/(:num)', function ($id) {
	// ..
});
```

#### Mendaftarkan URI tunggal untuk beberapa HTTP method:

```php
Router::register(['GET', 'POST'], $uri, $callback);
```

<a id="route-redirect"></a>

### Route Redirect

Jika anda perlu membuat rute redireksi ke URI lain, anda bisa menggunakan method `Route::redirect()`.
Method ini menyediakan jalan pintas yang nyaman sehingga anda tidak perlu menggunakan Closure untuk
melakukan redireksi sederhana:

```php
Route::redirect('deleted-page', 'home');
```

Secara default, ia akan mereturn kode status `302`. Anda dapat menyesuaikan kode status
tersebut menggunakan parameter ketiga seperti ini:

```php
Route::redirect('deleted-page', 'home', 301);
```

<a id="route-view"></a>

### Route View

Jika rute anda hanya perlu mereturn view, anda dapat menggunakan method `Route::view()`.

Metode ini menerima URI sebagai argumen pertamanya dan nama view sebagai argumen keduanya.
Selain itu, anda juga dapat mengoper array data untuk diteruskan ke view sebagai argumen ketiga:

```php
Route::view('/', 'home');

Route::view('profile', 'profile', ['name' => 'Budi']);
```

<a id="uri-wildcard"></a>

## URI Wildcard

#### Memaksa segmen URI untuk hanya menerima string alfabet:

```php
Route::get('user/(:alpha)', function ($id) {
    // ..
});
```

#### Memaksa segmen URI untuk hanya menerima string numerik:

```php
Route::get('user/(:num)', function ($id) {
    // ..
});
```

#### Mengizinkan segmen URI menjadi string alfa-numerik:

```php
Route::get('post/(:any)', function ($title) {
    // ..
});
```

#### Menangkap sisa URI:

```php
Route::get('files/(:all)', function ($path) {
    // ..
});
```

#### Mengizinkan segmen URI opsional (boleh ada, boleh tidak):

```php
Route::get('page/(:any?)', function ($page = 'index') {
    // ..
});
```

<a id="event-404"></a>

## Event 404

Jika request memasuki aplikasi anda tetapi tidak ada yang cocok dengan rute yang ada,
event 404 akan dimunculkan. Anda dapat menemukan implementasi defaultnya
di file `application/events.php`.

#### Handler event 404 default:

```php
Event::listen('404', function () {
    return Response::error('404');
});
```

Anda bebas mengubah ini agar sesuai dengan kebutuhan aplikasi anda!

_Bacaan lebih lanjut:_

-   _[Events](/docs/id/events)_

<a id="middleware"></a>

## Middleware

Middleware dapat dijalankan sebelum atau setelah rute dijalankan. Jika middleware `'before'`
mengembalikan sebuah nilai, nilai itu dianggap sebagai respon terhadap si request
dan rute tidak akan dieksekusi, ini akan berguna misalnya saat anda menerapkan middleware untuk
keperluan otentikasi user.

Middleware biasanya diletakkan di dalam file `middlewares.php`.

#### Mendaftarkan sebuah middleware:

```php
Route::middleware('auth', function () {
    return Redirect::to('home');
});
```

#### Melampirkan middleware ke rute:

```php
Route::get('blocked', ['before' => 'auth', function () {
    return View::make('blocked');
}]);
```

#### Melampirkan middleware `"after"` ke rute:

```php
Route::get('download', ['after' => 'track', function () {
    // ..
}]);
```

#### Melampirkan beberapa middleware ke rute:

```php
Route::get('create', ['before' => 'csrf|auth', function () {
    // ..
}]);
```

#### Mengoper parameter ke middleware:

```php
Route::get('panel', ['before' => 'role:admin', function () {
    // ..
}]);
```

<a id="middleware-pola-uri"></a>

## Middleware Pola URI

Terkadang anda mungkin ingin melampirkan middleware ke semua request yang dimulai dengan pola
URI tertentu. Misalnya, anda mungkin ingin melampirkan middleware `'auth'` ke semua request
dengan URI yang dimulai dengan `'/admin'`. Begini caranya:

#### Mendefinisikan middleware berdasarkan pola URI:

```php
Route::middleware('pattern: admin/*', 'auth');
```

Selain menggunakan cara diatas, anda juga dapat mendaftarkan middleware secara langsung
saat melampirkan middleware ke URI yang diberikan dengan mengoper array dengan nama middleware
dan closure sebagai callbacknya.

#### Mendefinisikan middleware dan URI berbasis di salah satu middleware:

```php
Route::middleware('pattern: admin/*', ['name' => 'auth', function () {
    // ..
}]);
```

<a id="middleware-global"></a>

## Middleware Global

Rakit memiliki dua middleware global yaitu `"before"` yang berjalan sebelum request ditanggapi
dan `"after"` yang berjalan setelah request ditanggapi.

Anda dapat menemukan keduanya di file `application/middlewares.php`. Middleware ini menjadi
tempat yang bagus untuk menjalankan paket default atau kebutuhan lain.

> Middleware `"after"` menerima objek `Response` untuk request saat ini.

<a id="route-grouping"></a>

## Route Grouping

Route grouping atau pengelompokan rute memberikan anda keleluasaan untuk melampirkan serangkaian atribut
ke sekelompok rute sekaligus, ini memungkinkan anda untuk menjaga agar kode anda tetap pendek dan rapi.

```php
Route::group(['before' => 'auth'], function () {

	Route::get('panel', function () {
        // ..
	});

	Route::get('dashboard', function () {
        // ..
	});
});
```

<a id="named-route"></a>

## Named Route

Terlalu sering menggunakan URL atau redirect menggunakan URI dapat menyebabkan masalah ketika rute
diubah di masa mendatang. Mendaftarkan rute dengan _"nama yang unik"_ memberi anda cara yang nyaman
untuk merujuk ke rute tersebut dari seluruh aplikasi anda.

Ketika terjadi perubahan rute, link yang dibuat akan mengarah ke rute baru tanpa perlu diubah lagi,
cukup menyesuaikan nama-nya saja.

#### Mendaftarkan nama rute:

```php
Route::get('/', ['as' => 'home', function () {
    return 'Selamat datang di homepage kami!';
}]);
```

#### Mengambil URL berdasarkan nama:

```php
$url = URL::to_route('home');
```

#### Redirect ke URL berdasarkan nama:

```php
return Redirect::to_route('home');
```

Setelah rute anda diberi nama, anda dapat dengan mudah memeriksa apakah rute yang menangani
request saat ini memiliki nama yang diberikan.

#### Menentukan apakah rute yang menangani request memiliki nama tertentu:

```php
if (Request::route()->is('home')) {
    return 'Rute saat ini bernama: home';
}
```

<a id="routing-paket"></a>

## Routing Paket

Rakit adalah framework yang fleksibel, cara kerjanya mirip dengan manajer paket di Linux.
Oleh karena itu, paket dapat dengan mudah dikonfigurasi untuk menangani request ke aplikasi anda.

Kami akan membahas [paket secara detail](/docs/id/packages) di dokumen lain. Untuk saat ini,
bacalah bagian ini dan perlu diketahui bahwa rute tidak hanya dapat digunakan untuk mengekspos
fungsionalitas dalam paket, tetapi juga dapat didaftarkan dari dalam paket.

Mari kita buka file `application/packages.php` dan tambahkan sesuatu:

#### Mendaftarkan paket untuk menangani rute:

```php
return [

	'admin' => ['handles' => 'admin'],

];
```

Perhatikan key baru bernama `handles` di array konfigurasi paket diatas. Ini memberitahu Rakit
untuk memuat paket bernama `admin` pada setiap request yang URI-nya diawali dengan `"/admin"`.

Sekarang anda siap untuk mendaftarkan beberapa rute untuk paket anda, jadi mari buat file `routes.php`
di dalam direktori root paket anda dan tambahkan kode berikut:

#### Mendaftarkan rute '/' (root) untuk sebuah paket:

```php
Route::get('(:package)', function () {
    return 'Selamat datang di paket admin!';
});
```

Pada contoh diatas, placeholder `(:package)` akan otomatis digantikan dengan value dari
klausa `handles` yang anda gunakan untuk mendaftarkan paket anda tadi.

Ini memungkinkan orang lain yang nantinya menggunakan paket anda untuk mengubah root URI-nya tanpa
mempengaruhi definisi routing milik mereka. Bagus, kan?

Tentu saja, anda dapat menggunakan placeholder `(:package)` untuk semua rute, bukan hanya root saja.

#### Mendaftarkan rute paket:

```php
Route::get('(:package)/password', function () {
    return 'Selamat datang di halaman admin >> password!';
});
```

<a id="routing-controller"></a>

## Routing Controller

Controller menyediakan cara lain untuk mengelola logika aplikasi anda. Jika anda belum paham dengan
controller, silahkan baca [dokumentasi tentang controller](/docs/id/controllers) sebelum lanjut ke bagian ini.

Penting untuk diketahui bahwa semua rute di Rakit harus didefinisikan secara eksplisit,
termasuk rute ke controller. Ini berarti bahwa jika method controller belum didaftarkan,
maka ia **tidak akan bisa diakses** oleh pengunjung situs anda.

Pendaftaran rute controller biasanya dilakukan di file `routes.php`.

Lazimnya, anda hanya perlu meregistrasikan semua controller di direktori `controllers/`.

Dimungkinkan untuk secara otomatis mengekspos semua method dalam controller menggunakan registrasi
route controller. Anda dapat melakukannya dengan sangat mudah:

#### Daftarkan semua controller untuk aplikasi:

```php
Route::controller(Controller::detect());
```

Method `Controller::detect()` hanya me-return array dari semua controller yang telah didaftarkan.

Jika anda ingin mendeteksi controller secara otomatis dalam sebuah paket, cukup oper nama paket
ke method `detect()` tersebut.

Jika tidak ada nama paket yang dioper, secara default Rakit akan mencari ke folder `application/controllers/`.

> Penting untuk dicatat bahwa metode ini tidak memberi Anda kendali atas urutan pemuatan controller.
> `Controller::detect()` hanya boleh digunakan untuk merutekan controller di situs yang tidak terlalu kompleks.
> Pengontrol perutean "secara manual" memberi Anda lebih banyak kontrol, l
> ebih mendokumentasikan diri sendiri, dan tentu saja disarankan.

#### Daftarkan semua controller untuk paket `admin`:

```php
Route::controller(Controller::detect('admin'));
```

#### Mendaftarkan controller `home` ke Router:

```php
Route::controller('home');
```

#### Mendaftarkan beberapa controller ke router:

```php
Route::controller(['dashboard.panel', 'admin']);
```

Setelah controller didaftarkan, anda dapat mengakses methodnya menggunakan konvensi URI sederhana:

```ini
mysite.com/<nama_controller>/<nama_method>/<parameter_tambahan>
```

Konvensi ini mirip dengan yang digunakan oleh CodeIgniter 3 dan
beberapa framework populer lainnya, dimana segmen pertama adalah nama controller,
yang kedua adalah nama method, dan segmen sisanya akan dioper ke method sebagai argumen.

Jika tidak ada segmen method yang diberikan, method `index` yang akan otomatis digunakan.

Kami tahu, konvensi seperti ini mungkin tidak selalu cocok untuk setiap situasi, jadi anda juga dapat
secara eksplisit mendaftarkan rute URI ke method controller menggunakan sintaks yang sederhana.

#### Mendaftarkan rute yang merujuk ke method controller:

```php
Route::get('/', 'home@index');
```

#### Mendaftarkan rute dengan middleware yang menunjuk ke method controller:

```php
Route::get('/', ['uses' => 'home@index', 'after' => 'track']);
```

#### Mendaftarkan named route yang menunjuk ke method controller:

```php
Route::get('/', ['uses' => 'home@index', 'as' => 'home.welcome']);
```

<a id="route-testing-via-cli"></a>

## Route Testing via CLI

Anda dapat memanggil rute yang anda buat via [console](/docs/id/console#memanggil-rute).
Cukup sebutkan tipe request dan URI mana yang ingin anda panggil.

#### Memanggil rute melalui CLI:

```bash
php rakit route:call get api/user/1
```
