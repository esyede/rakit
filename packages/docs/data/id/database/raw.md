# Kueri Mentah

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Method Lainnya](#method-lainnya)
- [Koneksi PDO](#koneksi-pdo)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Kueri mentah adalah baris - baris kueri yang ditulis langsung, kueri tersebut akan dikirim ke server database dan langsung dieksekusi. Method `query()` digunakan untuk mengeksekusi kueri SQL mentah terhadap koneksi database anda.


#### Mengambil record dari database:

```php
$users = DB::query('select * from users');
```


#### Mengambil record dari database mengunakan data binding:

```php
$users = DB::query('select * from users where name = ?', ['test']);
```


#### Insert sebuah record ke database

```php
$success = DB::query('insert into users values (?, ?)', $bindings);
```


#### Update record dan me-return jumlah affected rows:

```php
$affected = DB::query('update users set name = ?', $bindings);
```


#### Menghapus record dan me-return jumlah affected rows:

```php
$affected = DB::query('delete from users where id = ?', [1]);
```


<a id="method-lainnya"></a>
## Method Lainnya

Rakit menyediakan beberapa method lain untuk membuat kueri database lebih sederhana. Berikut beberapa contohnnya:

#### Menjalankan `SELECT` dan me-return hasil pertama:

```php
$user = DB::first('select * from users where id = 1');
```


#### Menjalankan `SELECT` dan me-return value dari sebuah kolom:

```php
$email = DB::only('select email from users where id = 1');
```


<a id="koneksi-pdo"></a>
## Koneksi PDO

Terkadang anda mungkin ingin mengakses object koneksi PDO mentah secara langsung dari Connection Object milik Rakit. Contoh kasusnya misalkan, kueri yang ingin anda jalankan tidak didukung oleh kelas - kelas database Rakit. Tenang saja, anda bisa melakukannya.

#### Mengakses object koneksi PDO mentah:

```php
$pdo = DB::connection('sqlite')->pdo();
// dd($pdo); // akan berisi object dari kelas \PDO
```

>  Jika tidak ada nama koneksi yang diberikan, ia akan me-return object milik koneksi `'default'`.
