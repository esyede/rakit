# Menggunakan Otentikasi

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Mengenkripsi Password](#mengenkripsi-password)
- [Log In](#log-in)
- [Memproteksi Route](#memproteksi-route)
- [Mengambil Data User](#mengambil-data-user)
- [Log Out](#log-out)

<!-- /MarkdownTOC -->


>  Sebelum menggunakan kelas `Auth` ini, terlebih dahulu anda harus [mengkonfigurasi driver session](/docs/id/session/config).


<a id="mengenkripsi-password"></a>
## Mengenkripsi Password

Jika anda menggunakan kelas Auth ini, kami sangat menganjurkan untuk mengenkripsi seluruh password. Pengembangan aplikasi web development harus dilakukan dengan cara yang bertanggung jawab. Password yang terenkripsi meminimalisir potensi kebocoran data milik user anda.

Sekarang, mari kita lanjutkan untuk mengenkripsi password:

```php
$hash = Hash::make('admin123');
```

Secara default, si `Hash::make()` ini akan menggunakan default cost `10`, tetapi anda juga bisa menambah atau menguranginya jika dirasa sangat perlu, begini caranya:

```php
$cost = 22;

$hash = Hash::make('admin123', $cost);
```

>  Cost hanya boleh diisi integer antara `4` sampai `31`.

Anda dapat membandingkan nilai yang tidak di enkripsi dengan nilai yang di enkripsi menggunakan method `check()` seperti ini:

```php
if (Hash::check('admin123', $hash)) {
	return 'Password benar!';
}
```


<a id="log-in"></a>
## Log In

Sangat mudah untuk me-login-kan user ke dalam aplikasi anda menggunakan method `attempt()`. Cukup oper username dan password user ke method tersebut. Kredensial harus ditaruh dalam array, yang memungkinkan fleksibilitas maksimum di seluruh driver, karena beberapa driver mungkin memerlukan jumlah argumen yang berbeda. Method `attempt()` akan me-return `TRUE` jika kredensial valid dan `FALSE` jika sebaliknya:

```php
$credentials = [
    'username' => 'example@gmail.com',
    'password' => 'secret',
];

if (Auth::attempt($credentials)) {
	return Redirect::to('user/profile');
}
```

Jika kredensial user ternyata valid, ID si user akan disimpan dalam session dan user akan dianggap "sudah login" pada request berikutnya ke aplikasi anda.

Untuk menentukan apakah seorang user sudah login atau belum, gunakan method `check()` seperti ini:

```php
if (Auth::check()) {
	return 'Login berhasil!';
}
```

Gunakan method `login()` untuk me-login-kan user tanpa memeriksa kredensial mereka.
Contoh kasusnya seperti setelah seorang user berhasil melakukan registrasi,
ia akan langsung diarahkan ke halaman dashboard tanpa harus login lagi menggunakan
akun yang baru dibuatnya. Caranya mudah, cukup oper ID si user ke method `login()`:

```php
Auth::login($user->id);

Auth::login(15);
```

<a id="memproteksi-route"></a>
## Memproteksi Route

Sangatlah umum untuk membatasi akses ke rute tertentu hanya untuk user yang sudah login saja.
Di Rakit, ini dilakukan dengan menggunakan middleware `'auth'`. Jika user berhasil login,
request akan diproses seperti biasa; Namun, jika user belum login, mereka akan
diarahkan ke [named route](/docs/id/routing#named-route) bernama `'login'`.

Untuk memproteksi route, cukup lampirkan middleware `'auth'` ke route yang ingin anda proteksi:

```php
Route::get('admin', ['before' => 'auth', function () {
	// ..
}]);
```

>  Anda bebas mengubah middleware `'auth'` sesuai kebutuhan. Implementasi defaultnya dapat
   ditemukan di file `application/middlewares.php`.


<a id="mengambil-data-user"></a>
## Mengambil Data User

Once a user has logged in to your application, you can access the user model via the **user** method on the Auth class:

Setelah user berhasil login, anda dapat mengakses model user melalui method `user()` seperti ini:

```php
return Auth::user()->email;
```

>  Jika si user belum log in, method `user()` ini akan me-return `NULL`.


<a id="log-out"></a>
## Log Out

Bagaimana jika anda ingin me-logout-kan user? Mudah saja, cukup panggil method `logout()` seperti ini:

```php
Auth::logout();
```

Method ini akan menghapus ID user dari session, sehingga si user tidak lagi dianggap "sudah login" pada request berikutnya.
