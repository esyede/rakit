# Controller

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Controller Routing](#controller-routing)
- [Controller Paket](#controller-paket)
- [Action Middleware](#action-middleware)
- [Nested Controller](#nested-controller)
- [Layout Controller](#layout-controller)
- [RESTful Controller](#restful-controller)
- [Dependency Injection](#dependency-injection)
- [Controller Factory](#controller-factory)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Controller adalah kelas yang bertanggung jawab untuk menerima input pengguna dan mengelola interaksi
antara model, library, dan view. Biasanya, mereka akan meminta data ke Model, dan kemudian mengembalikan
view yang merepresentasikan data tersebut kepada pengguna.

Penggunaan controller adalah metode yang paling umum untuk menerapkan logika aplikasi dalam pengembangan
web modern.

Namun, Rakit juga memberdayakan pengembang untuk mengimplementasikan logika aplikasi mereka dalam
deklarasi routing melalui closure. Topik ini dibahas secara rinci dalam
[dokumentasi routing](/docs/id/routing).

Pengguna baru disarankan untuk memulai dengan controller. Tidak ada yang dapat dilakukan oleh
logika aplikasi berbasis closure routing yang tidak dapat dilakukan oleh controller.

Kelas controller harus disimpan dalam folder `controllers/`. Kami telah menyertakan
kelas `Home_Controller` (di file `application/controllers/home.php`) sebagai contoh penggunaannya.

<a id="membuat-controller-sederhana"></a>
#### Membuat controller sederhana:

```php
class Admin_Controller extends Controller
{
    public function action_index()
    {
        // ..
    }
}
```

**Action** adalah nama method controller yang dimaksudkan agar dapat diakses via web.
Penamaan method untuk action harus diawali dengan kata `action_`.

Semua method lain, jika namanya tidak diawali dengan kata `action_` maka ia **tidak akan bisa diakses**
oleh pengunjung situs anda.

>  Kelas `Base_Controller` meng-extend kelas controller utama bawaan Rakit, dan memberi anda tempat
   yang nyaman untuk meletakkan method yang umum digunakan oleh banyak controller.


<a id="controller-routing"></a>
## Controller Routing

Penting untuk diketahui bahwa semua rute di Rakit harus didefinisikan secara eksplisit,
termasuk rute ke controller.

Ini berarti bahwa method di kelas controller yang belum diekspos melalui registrasi rute
**tidak akan bisa diakses** oleh pengunjung.

Dimungkinkan untuk secara otomatis mengekspos semua metode dalam controller menggunakan registrasi
`Route::controller()`. Registrasi rute controller biasanya dilakukan di file `routes.php`.

Baca [halaman routing](/docs/id/routing#controller-routing) untuk panduan lebih lanjut mengenai
controller routing.


<a id="controller-paket"></a>
## Controller Paket

Paket adalah sistem modularisasi yang sangat fleksibel. Paket dapat dengan mudah dikonfigurasi untuk
menangani request yang datang ke aplikasi anda. Kami akan membahas [paket lebih detail](/docs/id/packages)
di dokumen lain.

Membuat controller untuk paket hampir sama caranya dengan membuat controller biasa. Cukup awali
nama kelas controller dengan nama paket. Jadi jika anda ingin membuat paket bernama `admin`,
kelas controller anda akan terlihat seperti ini:


#### Membuat controller untuk paket admin:

```php
class Admin_Home_Controller extends Controller
{
    public function action_index()
    {
        return 'Selamat datang di halaman index milik paket admin!';
    }
}
```

Lantas, bagaimana caranya mendaftarkan controller paket ke router? Mudah saja. Begini caranya:


#### Mendaftarkan controller paket ke router:

```php
Route::controller('admin::home');
```

Mantap! Sekarang kita dapat mengakses controller home milik paket `admin` dari web!

>  Secara default, sintaks `::` (kolon ganda) digunakan untuk merujuk segala informasi milik sebuah paket.
   Informasi lebih lanjut mengenai paket dapat ditemukan di [dokumentasi paket](/docs/id/packages).


<a id="action-middleware"></a>
## Action Middleware

Action middleware adalah middleware yang dapat dijalankan sebelum atau sesudah action controller dieksekusi.
Anda tidak hanya memiliki kendali atas middleware mana yang ditugaskan untuk action mana, tetapi
juga dapat memilih tipe request apa (`GET`, `POST`, `PUT`, atau `DELETE`) yang akan mengaktifkan
si middleware tersebut.

Anda dapat mendefinisikan middleware `before()` dan `after()` melalui **constructor** milik controller.

Mari kita coba tambahkan middleware ke controller paket `admin` diatas.


#### Melampirkan middleware ke semua action:

```php
class Admin_Home_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'auth');
    }


    public function action_index()
    {
        return 'Selamat datang di halaman index milik paket admin!';
    }
}
```

Pada contoh diatas middleware `'auth'` akan dipnggil sebelum setiap action
dalam controller ini berjalan.

Middleware `'auth'` ini adalah middleware bawaan Rakit dimana implementasinya dapat
anda lihat di `application/middlewares.php`. Middleware auth memverifikasi bahwa
user sudah login, dan me-redirect mereka ke halaman `'/login'` jika belum.


#### Melampirkan middleware hanya untuk beberapa action saja:

```php
$this->middleware('before', 'auth')->only(['index', 'list']);
```

Pada contoh diatas middleware `'auth'` hanya akan dijalankan sebelum method `action_index()`
atau `action_list()` dieksekusi. User harus login dulu untuk dapat mengakses 2 action ini.
Action lain tidak akan terpengaruh.


#### Melampirkan middleware ke semua action kecuali yang disebutkan:

```php
$this->middleware('before', 'auth')->except(['add', 'posts']);
```

Sama seperti contoh sebelumnya, deklarasi ini memastikan bahwa middleware `'auth'` hanya
akan dijalankan pada beberapa action saja.

Alih-alih mendefinisikan action mana yang perlu di otentikasi, kita justru hanya perlu mendeklarasikan
action mana saja yang tidak akan membutuhkan otentikasi.

Terkadang lebih aman menggunakan method `except()` ini karena mungkin di kemudian hari anda perlu
menambahkan action baru ke controller ini dan lupa menambahkannya ke method `only()`.

Ini berpotensi menyebabkan action controller anda tidak dapat diakses secara tidak sengaja oleh
user yang belum login.


#### Melampirkan middleware untuk dijalankan hanya pada tipe request POST:

```php
$this->middleware('before', 'csrf')->on('post');
```

Contoh ini menunjukkan bagaimana middleware hanya akan dijalankan pada tipe request tertentu.
Dalam hal ini kami menerapkan middleware `'csrf'` hanya ketika request yang datang bertipe `POST`.

Middleware `'csrf'` dirancang untuk mencegah pengiriman data POST dari pihak lain (misalnya bot spam).

Middleware ini juga sudah disediakan secara default. Anda dapat melihat implementasi default
middleware `'csrf'` ini di file `middlewares.php`.

_Bacaan lebih lanjut:_

- _[Middleware](/docs/id/routing#middleware)_


<a id="nested-controller"></a>
## Nested Controller

Nested controller adalah controller yang diletakkan kedalam subfolder. Benar, anda boleh menyimpan
controller didalam sejumlah subfolder di dalam folder `controllers/`.

Coba buat kelas controller berikut dan simpan ke `controllers/admin/panel.php`:

```php
class Admin_Panel_Controller extends Controller
{
    public function action_index()
    {
        // ..
    }
}
```


#### Daftarkan nested controller ke router menggunakan notasi dot:

```php
Route::controller('admin.panel');
```

>  Saat menggunakan nested controller, selalu daftarkan controller anda dari yang berada di
   subfolder paling dalam agar rute controller tidak saling tumpang tindih.


#### Mengakses action `index` milik controller:

```ini
mysite.com/admin/panel
```


<a id="layout-controller"></a>
## Layout Controller

Dokumentasi lengkap tentang penggunaan layout dengan Controller dapat ditemukan di
[halaman templating](/docs/id/views/templating).


<a id="restful-controller"></a>
## RESTful Controller
Rakit juga mendukung RESTful controller. Ini sangat berguna ketika membangun sistem
[CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) karena anda dapat memisahkan
logika pembuatan formulir HTML dari logika yang memvalidasi dan menyimpan hasilnya.

RESTful controller ditandai dengan adanya properti publik `$restful` di suatu kelas controller,
serta valuenya adalah `TRUE`.

Alih-alih mengawali nama action controller dengan kata `action_`, anda boleh menggantinya
dengan tipe request apa (misalnya `POST`, `GET`, `PUT` atau `DELETE`) yang harus ia respon.


#### Menambahkan property `$restful` ke controller:
```php
class Home_Controller extends Controller
{
    public $restful = true;

    // ..
}
```


#### Membuat RESTful action pada controller:

```php
class Home_Controller extends Controller
{
    public $restful = true;


    public function get_index()
    {
        // Aku hanya menerima GET
    }

    public function post_index()
    {
        // Aku hanya menerima POST
    }
}
```


<a id="dependency-injection"></a>
## Dependency Injection

Jika anda berfokus pada penulisan kode yang _test-able_ atau mudah diuji, anda mungkin perlu
meng-inject dependensi ke constructor controller anda.

Tidak masalah. Cukup daftarkan controller anda ke [Container](/docs/id/container).
Saat mendaftarkan controller ke container, awali key-nya dengan kata `controller`.

Sebagai contoh, dalam file `boot.php`, anda dapat mendaftarkan controller `User` seperti berikut:

```php
Container::register('controller: user', function () {
    return new User_Controller();
});
```

Ketika request datang ke controller anda, Rakit akan secara otomatis memeriksa apakah controller
tersebut terdaftar dalam container atau tidak, dan jika iya, maka Rakit akan menggunakan data ini
untuk meresolve instance controller tersebut.

>  Sebelum menyelam lebih jauh kedalam Dependency Injection Controller,
   anda mungkin ingin membaca dokumentasi tentang [Container](/docs/id/container).


<a id="controller-factory"></a>
## Controller Factory

Jika anda ingin lebih punya kendali tentang cara instansiasi controller anda, seperti
ketika menggunakan container pihak ketiga, anda harus menggunakan fitur controller factory ini.


#### Daftarkan event untuk menangani instansiasi controller:

```php
Event::listen(Controller::FACTORY, function ($controller) {
    return new $controller();
});
```

Event akan menerima nama kelas controller yang perlu diresolve. Yang perlu anda lakukan hanyalah
mereturn instance dari kelas controller.
