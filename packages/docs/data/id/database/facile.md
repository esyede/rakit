# Facile Model

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Konvensi](#konvensi)
-   [Mengambil Model](#mengambil-model)
-   [Agregasi](#agregasi)
-   [Insert & Update Model](#insert--update-model)
-   [Relasi](#relasi)
    -   [One-To-One](#one-to-one)
    -   [One-To-Many](#one-to-many)
    -   [Many-To-Many](#many-to-many)
-   [Insert Ke Model Yang Berelasi](#insert-ke-model-yang-berelasi)
    -   [Insert Ke Model Yang Berelasi \(Many-To-Many\)](#insert-ke-model-yang-berelasi-many-to-many)
-   [Bekerja Dengan Intermediate Table](#bekerja-dengan-intermediate-table)
-   [Eagerloading](#eagerloading)
-   [Membatasi Eagerloading](#membatasi-eagerloading)
-   [Mutator & Accessor](#mutator--accessor)
-   [Mass-Assignment](#mass-assignment)
-   [Konversi Model Ke Array](#konversi-model-ke-array)
-   [Delete Model](#delete-model)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Tentu anda sudah tahu apa itu ORM atau object-relational mappper ini, dan Rakit juga
menyediakan sebuah object-relational mappper dengan sintaks yang ekspresif dan mudah dipahami.

Secara umum, anda akan membuat satu Model untuk tiap - tiap tabel yang anda miliki.
Model diletakkan ke dalam folder `models/`.

Untuk permulaan, mari kita coba membuat sebuah model yang sederhana:

```php
class User extends Facile
{
    // ..
}
```

Mantap! Perhatikan bahwa model kita meng-extends `Facile`.
Dialah yang akan menyediakan seluruh fungsionalitas yang anda butuhkan untuk mulai bekerja
dengan database anda.

> Biasanya, Facile model diletakkan di folder `models/`.

<a id="konvensi"></a>

## Konvensi

Facile membuat beberapa asumsi dasar tentang struktur database anda:

-   Setiap tabel harus memiliki primary key bernama `'id'`.
-   Setiap nama tabel harus dinamai bentuk plural (jamak) dari nama modelnya.

Tapi terkadang, anda mungkin ingin menggunakan nama tabel selain bentuk jamak dari model anda,
atau kolom primary key dengan nama selain `'id'`.
Tidak masalah. Cukup tambahkan properti `$table` dan `$key` ke model anda seperti ini:

```php
class User extends Facile
{
    public static $table = 'my_users';
    public static $key = 'my_primary_key';

    // ..
}
```

<a id="mengambil-model"></a>

## Mengambil Model

Mengambil model menggunakan Facile sangatlah sederhana.
Cara paling dasar untuk mengambil Facile model adalah via method `find()`.
Method ini akan me-return sebuah object model dengan primary key dan properti yang
sesuai dengan setiap kolom pada tabel anda:

```php
$user = User::find(1);

echo $user->email;
```

Method `find()` diatas akan mengeksekusi kueri seperti berikut:

```sql
SELECT * FROM "users" WHERE "id" = 1
```

Perlu mengambil semua record milik tabel? Cukup gunakan method `all()` seperti ini:

```php
$users = User::all();

foreach ($users as $user) {
    echo $user->email;
}
```

Tentu saja, mengambil seluruh record dari tabel akan sangat memberatkan kinerja server anda.
Untungnya, <ins>Seluruh method yang tersedia di [query builder](/docs/id/database/magic) juga
bisa dipakai di dalam Model</ins>.

Cukup mulai kueri anda dengan memanggil salah satu method milik query builder,
dan ambil hasilnya menggunakan method `get()` atau `first()`.

Method `get()` akan me-return `array` berisi beberapa object model,
sedangkan method `first()` akan me-return `satu buah` object model:

```php
$user = User::where('email', '=', $email)->first();

$user = User::where_email($email)->first();

$users = User::where_in('id', [1, 2, 3])->or_where('email', '=', $email)->get();

$users = User::order_by('votes', 'desc')->take(10)->get();
```

> Jika tidak ada data yang ditemukan, method `first()` akan me-return `NULL`.
> Sedangkan method `all()` dan `get()` akan me-return `array kosong`.

<a id="agregasi"></a>

## Agregasi

Perlu mengambil nilai `MIN`, `MAX`, `AVG`, `SUM`, atau `COUNT`? Cukup sebutkan nama kolomnya:

```php
$min = User::min('id');

$max = User::max('id');

$avg = User::avg('id');

$sum = User::sum('id');

$count = User::count();
```

Tentu saja, anda juga boleh membatasi kueri menggunakan klausa `WHERE` terlebih dahulu:

```php
$count = User::where('id', '>', 10)->count();
```

<a id="insert--update-model"></a>

## Insert & Update Model

Insert record kedalam tabel juga sangat mudah, semudah menghitung satu sampai tiga.
Pertama, instansiasi modelnya. Kedua, atur propertinya. Ketiga, panggil method `save()`:

```php
$user = new User();

$user->email = 'example@gmail.com';
$user->password = 'secret';

$user->save();
```

Atau, anda juga bisa menggunakan metode `create()`, yang akan meng-insert record baru
ke database dan me-return instance model untuk record yang baru saja anda insert,
atau `FALSE` jika operasi insertnya gagal.

```php
$user = User::create(['email' => 'example@gmail.com']);
```

Update record juga sama mudahnya. Alih-alih membuat instance model baru,
anda cukup mengambil satu data dari database. Kemudian atur propertinya,
lalu simpan kembali menggunakan method `save()` seperti ini:

```php
$user = User::find(1);

$user->email = 'new_email@gmail.com';
$user->password = 'new_secret';

$user->save();
```

Perlu memperbarui waktu pembuatan (`created_at`) dan waktu update (`updated_at`) di tabel anda?
Jangan khawatir. Cukup tambahkan properti `$timestamps` seperti ini:

```php
class User extends Facile
{
    public static $timestamps = true;

    // ..
}
```

Selanjutnya, tambahkan kolom betipe `DATE` dengan nama `created_at` dan `updated_at`
kedalam tabel anda jika belum ada. Sekarang, setiap anda menyimpan object model,
kedua kolom tersebut akan diperbarui secara otomatis. Mudah bukan?

Dalam beberapa kasus, mungkin anda hanya ingin meng-update kolom `updated_at` tanpa
mengubah record lain. Cukup gunakan method `touch()`, ia akan secara otomatis meng-update
kolom `updated_at` untuk anda:

```php
$comment = Comment::find(1);
$comment->touch();
```

Anda juga dapat menggunakan method `timestamp()` untuk meng-update kolom `'updated_at'`
tanpa langsung menyimpan si model ke database.

Perlu diingat bahwa setiap kali anda mengubah record di model,
method ini sudah secara otomatis dipanggil, sehingga anda tidak perlu memanggilnya
setiap kali meng-update record:

```php
$comment = Comment::find(1);
$comment->timestamp();

// Lakukan hal lain disini, tetapi tidak mengubah data milik model Comment

$comment->save();
```

> Anda bisa mengubah timezone untuk aplikasi anda melalui file `application/config/application.php`.

<a id="relasi"></a>

## Relasi

Lazimnya, tabel di database anda akan berelasi satu sama lain.
Misalnya, `order` mungkin milik `user`. Atau, sebuah `post` mungkin memiliki banyak `comment`.

Facile membuat pendefinisian relasi dan pengambilan model yang berelasi menjadi sederhana dan intuitif.
Rakit mendukung tiga tipe relasi:

-   [One-To-One](#one-to-one)
-   [One-To-Many](#one-to-many)
-   [Many-To-Many](#many-to-many)

Untuk mendefinisikan relasi pada model, anda cukup membuat method yang me-return
method `has_one()`, `has_many()`, `belongs_to()`, atau `belongs_to_many()`.

Mari kita coba masing - masing method tersebut:

<a id="one-to-one"></a>

### One-To-One

Relasi one-to-one (atau, satu-ke-satu) adalah bentuk relasi yang paling dasar.
Misalnya, anggaplah seorang `user` memiliki satu `phone`.

Gambaran secara sederhana relasi ini dengan Facile menjadi seperti berikut:

```php
class User extends Facile
{
    public function phone()
    {
        return $this->has_one('Phone');
    }
}
```

Perhatikan bahwa nama model yang berelasi dioper ke method `has_one()`.
Sekarang anda dapat mengambil phone milik seorang user melalui method `phone()` seperti ini:

```php
$phone = User::find(1)->phone()->first();
```

Mari kita periksa kueri yang dijalankan oleh statement ini.
Dua buah kueri akan dijalankan: satu untuk mengambil user dan satu lagi untuk mengambil phone milik user.

```sql
SELECT * FROM "users" WHERE "id" = 1

SELECT * FROM "phones" WHERE "user_id" = 1
```

Perhatikan bahwa Facile mengasumsikan primary key dari hubungan tersebut adalah `'user_id'`.

Kebanyakan primary key akan mengikuti konvensi `nama model` + `_id` ini;
Namun, jika anda ingin menggunakan nama kolom lain sebagai primary key,
cukup oper nama primary key anda ke parameter kedua seperti ini:

```php
return $this->has_one('Phone', 'my_foreign_key');
```

Ingin mengambil telepon user tanpa memanggil method `first()`?
Tidak masalah. Cukup gunakan properti `$phone` seperti dibawah ini.
Facile akan secara otomatis memuat relasinya untuk anda, ia bahkan cukup pintar untuk
menentukan apakah ia harus memanggil method `get()` (untuk relasi one-to-many)
atau `fisrt()` (untuk relasi one-to-one):

```php
$phone = User::find(1)->phone;
```

Bagaimana jika anda perlu mengambil pengguna si telepon? Karena primary key (`'user_id'`)
ada di tabel `phone`, kita harus mendeskripsikan relasi ini menggunakan method `belongs_to()`.
Masuk akal, bukan? _"Phone belongs to user"._

Saat menggunakan method `belongs_to()`, nama method relasi harus sesuai dengan
primary key (tanpa `_id`). Karena primary key-nya adalah `'user_id'`,
maka method relasinya harus dinamai `'user'`:

```php
class Phone extends Facile
{
    public function user()
    {
        return $this->belongs_to('User');
    }
}
```

Mantap! anda sekarang dapat mengakses data user melalui model `Phone` menggunakan
method relasi (method `user()` baru saja kita buat), atau menggunakan properti dinamis:

```php
echo Phone::find(1)->user()->first()->email;

echo Phone::find(1)->user->email;
```

<a id="one-to-many"></a>

### One-To-Many

Asumsikan sebuah `post` memiliki banyak `comment`. Anda bisa mendefinisikan relasi ini
via method `has_many()` seperti ini:

```php
class Post extends Facile
{
    public function comments()
    {
        return $this->has_many('Comment');
    }
}
```

Sekarang, cukup akses comment milik post melalui method relasinya atau melalui properti dinamis:

```php
$comments = Post::find(1)->comments()->get();

$comments = Post::find(1)->comments;
```

Kedua statement diatas akan menjalankan kueri berikut:

```sql
SELECT * FROM "posts" WHERE "id" = 1

SELECT * FROM "comments" WHERE "post_id" = 1
```

Perlu join tabel dengan foreign key yang berbeda? Tidak masalah.
Cukup oper nama foreign key-nya ke parameter kedua:

```php
return $this->has_many('Comment', 'my_foreign_key');
```

Anda mungkin bertanya-tanya:

_"Kalau property dinamis juga me-return data hasil relasi yang sama dan
lebih pendek untuk ditulis, kenapa saya harus menggunakan method relasi
yang panjang dan bertele-tele ini? Buang - buang waktu saja!"_

Begini, method relasi ini sangatlah ampuh. Method ini mengizinkan anda untuk terus menyambung
method milik [query builder](/docs/id/database/magic) sebelum mengambil data hasil relasi:

```php
$results = Post::find(1)->comments()
    ->order_by('votes', 'desc')
    ->take(10)
    ->get();
```

<a id="many-to-many"></a>

### Many-To-Many

Relasi many-to-many (atau banyak-ke-banyak) adalah yang paling rumit dari ketiga relasi.
Tapi jangan khawatir, anda tetap bisa melakukan ini.
Misalnya, anggap `seorang user` memiliki `banyak role`, tetapi `role` juga bisa
dimiliki oleh `banyak user`.

Tiga tabel database harus dibuat untuk membuat relasi ini antara lain:
tabel `'users'`, tabel `'roles'`, dan tabel `'role_user'`.

Struktur setiap tabel akan terlihat seperti ini:

**Tabel `users`:**

```sql
id    - INTEGER
email - VARCHAR
```

**Tabel `roles`:**

```sql
id    - INTEGER
email - VARCHAR
```

**Tabel `role_user`:**

```sql
id      - INTEGER
user_id - INTEGER
role_id - INTEGER
```

Tabel berisi banyak record dan karenanya harus dinamai plural (bentuk jamak).
Tabel pivot yang digunakan dalam relasi `belongs_to_many()` diberi nama
dengan menggabungkan nama _singular_ (bentuk tunggal) dari dua model yang berelasi
yang <ins>disusun menurut abjad </ins> dan <ins>menggabungkannya dengan garis bawah</ins>.

Sekarang anda telah siap untuk menentukan relasi pada model anda menggunakan
method `belongs_to_many()` seperti berikut:

```php
class User extends Facile
{
    public function roles()
    {
        return $this->belongs_to_many('Role');
    }
}
```

Mantap! Sekarang saatnya mengambil role milik si user:

```php
$roles = User::find(1)->roles()->get();
```

Atau, seperti biasa, anda dapat mengambil relasinya melalui properti dinamis `$roles` seperti ini:

```php
$roles = User::find(1)->roles;
```

Jika nama tabel anda tidak mengikuti konvensi yang telah ditetapkan,
cukup sebutkan nama tabel di parameter kedua milik method `belongs_to_many()` seperti ini:

```php
class User extends Facile
{
    public function roles()
    {
        return $this->belongs_to_many('Role', 'user_roles');
    }
}
```

Secara default, hanya field tertentu dari tabel pivot yang akan
di-return (dua field `'id'`, dan timestamp).

Jika tabel pivot anda berisi kolom tambahan, anda juga dapat mengambilnya dengan
menggunakan metode `with()` seperti berikut:

```php
class User extends Facile
{
    public function roles()
    {
        return $this->belongs_to_many('Role', 'user_roles')->with('column');
    }
}
```

<a id="insert-ke-model-yang-berelasi"></a>

## Insert Ke Model Yang Berelasi

Anggaplah anda memiliki model `Post` yang memiliki banyak `Comment`.
Seringkali anda mungkin ingin meng-insert komentar baru untuk postingan tertentu.

Alih-alih menyebutkan primary key `'post_id'` secara manual pada model anda,
anda dapat meng-insert komentar baru dari model `Post` yang dimilikinya. Contohnya seperti ini:

```php
$data = ['message' => 'Ini adalah komentar baru.'];

$comment = new Comment($data);

$post = Post::find(1);

$comment = $post->comments()->insert($comment);
```

Saat meng-insert ke model yang berelasi melalui model induknya,
primary key akan secara otomatis terisi. Jadi, dalam kasus ini, `'post_id'` secara
otomatis terisi dengan `1` pada komentar yang baru di-insert.

Ketika bekerja dengan relasi `has_many()`, anda boleh menggunakan method `save()` untuk
insert / update model yang berelasi:

```php
$data = [
    ['message' => 'Ini adalah komentar baru.'],
    ['message' => 'Ini komentar kedua.'],
];

$post = Post::find(1);

$post->comments()->save($data);
```

<a id="insert-ke-model-yang-berelasi-many-to-many"></a>

### Insert Ke Model Yang Berelasi (Many-To-Many)

Ini bahkan lebih membantu ketika bekerja dengan relasi many-to-many.
Misalnya, jika model `User` yang memiliki banyak role.
Demikian pula, model `Role` mungkin memiliki banyak user.
Jadi, tabel perantara untuk relasi ini memiliki kolom `'user_id'` dan `'role_id'`.
Sekarang, mari kita masukkan Role baru untuk User:

```php
$data = ['title' => 'Admin'];

$role = new Role($data);

$user = User::find(1);

$role = $user->roles()->insert($role);
```

Sekarang, ketika Role ditambahkan, Role tidak hanya ditambahkan ke dalam tabel `'roles'`,
tetapi record di tabel perantara juga secara otamatis ditambahkan untuk anda.

Namun, biasanya anda hanya perlu meng-insert record baru ke dalam tabel perantara.
Misalnya, mungkin role yang ingin anda insert ke user sudah ada sebelumnya.
Caranya, cukup gunakan method `attach()` seperti ini:

```php
$user->roles()->attach($role_id);
```

Anda juga bisa meng-insert record ke field di tabel perantara (tabel pivot),
untuk melakukannya, oper array record ke parameter ke-dua:

```php
$user->roles()->attach($role_id, ['expires' => $expires]);
```

Atau, Anda dapat menggunakan method `sync()`, untuk mengoper array ID
yang perlu anda "sinkronkan" dengan tabel perantara. Setelah operasi ini selesai,
hanya ID dalam array yang akan berada di tabel perantara.

```php
$user->roles()->sync([1, 2, 3]);
```

<a id="bekerja-dengan-intermediate-table"></a>

## Bekerja Dengan Intermediate Table

Seperti yang telah anda ketahui, relasi many-to-many (atau banyak-ke-banyak) memerlukan
kehadiran intermediate table (atau tabel perantara). Facile memudahkan pemeliharaan tabel ini.
Misalnya, anggap kita memiliki model `User` yang memiliki banyak `Role`.
Dan, demikian pula, model `Role` yang memiliki banyak `User`.

Jadi tabel perantara memiliki kolom `'user_id'` dan `'role_id'`. Kita dapat
mengakses tabel pivot untuk relasi tersebut seperti ini:

```php
$user = User::find(1);

$pivot = $user->roles()->pivot();
```

Setelah kita memiliki instance tabel pivot ini, kita bisa menggunakannya seperti model pada umumnya:

```php
$rows = $user->roles()->pivot()->get();

foreach ($rows as $row) {
    // ..
}
```

Anda juga bisa mengakses row milik tabel perantara. Contohnya seperti ini:

```php
$user = User::find(1);

foreach ($user->roles as $role) {
    echo $role->pivot->created_at;
}
```

Perhatikan bahwa setiap model `Role` berelasi yang kita ambil secara otomatis akan
memiliki atribut `$pivot`. Atribut ini berisi model yang mewakili record tabel perantara
yang terkait dengan model yang berelasi itu.

Terkadang anda mungkin ingin menghapus semua record milik si tabel perantara.
Misalnya, mungkin anda ingin menghapus semua `Role` yang telah di-assign ke user:

```php
$user = User::find(1);

$user->roles()->delete();
```

Harap diingat bahwa ini tidak akan menghapus role dari tabel `roles`,
tetapi hanya akan menghapus record dari tabel perantara yang berelasi role dengan user tertentu.

<a id="eagerloading"></a>

## Eagerloading

Eager loading ada untuk mengatasi masalah `N + 1` pada kueri. Masalah apa sih ini?
Anggaplah setiap `Book` milik seorang `Author`. Kita biasanya akan menggambarkan relasi itu seperti ini:

```php
class Book extends Facile
{
    public function author()
    {
        return $this->belongs_to('Author');
    }
}
```

Sekarang, coba perhatikan potongan kode berikut:

```php
foreach (Book::all() as $book) {
    echo $book->author->name;
}
```

Berapa banyak kueri yang akan dieksekusi? Mari kita hitung, satu kueri akan dieksekusi
untuk mengambil semua buku dari tabel. Namun, kueri lain akan diperlukan pada setiap buku
untuk mengambil `author`-nya. Jika kita ingin menampilkan author dari 25 buku,
kita akan membutuhkan 26 kueri (N + 1 kan?).

Lalu, bagaimana jika kita punya 10.000 buku? atau, 2 juta buku?
Lihat, kueri kita akan semakin bertambah banyak. Efeknya, waktu eksekusi kueri akan semakin lama,
belum lagi penggunaan resource server juga akan semakin besar, apa yang akan terjadi?

Untungnya, anda bisa meng-eagerload model Author menggunakan method `with()`.
Cukup sebutkan `nama method` dari relasi yang ingin anda eagerload:

```php
foreach (Book::with('author')->get() as $book) {
    echo $book->author->name;
}
```

Pada contoh diatas, hanya **2 kueri** saja yang akan dijalankan!

```sql
SELECT * FROM "books"

SELECT * FROM "authors" WHERE "id" IN (1, 2, 3, 4, 5, ...)
```

Jelasnya, penggunaan metode eagerloading secara bijak dapat secara dramatis
meningkatkan kinerja aplikasi anda. Pada contoh di atas, eagerload memotong waktu
eksekusi kueri menjadi hanya separuhnya.

Perlu meng- eagerload lebih dari satu relasi? Bisa kok:

```php
$books = Book::with(['author', 'publisher'])->get();
```

> Ketika melakukan eagerloading, pemanggilan method `with()` harus selalu dilakukan di awal kueri.

Anda bahkan boleh meng-eagerload nested relationship (atau relasi yang bersarang).
Sebagai contoh, mari asumsikan model `Author` memiliki sebuah relasi `contacts`.
Kita bisa meng-eagerload kedua relasi ini dari model `Book` dengan cara seperti ini:

```php
$books = Book::with(['author', 'author.contacts'])->get();
```

Jika anda sering meng-eagerload model yang sama, Silahkan gunakan properti `$with` agar
anda tidak perlu mengulang - ulang penulisannya.

```php
class Book extends Facile
{
    public $with = ['author'];


    public function author()
    {
        return $this->belongs_to('Author');
    }
}
```

Properti `$with` menerima parameter yang sama dengan yang diminta method `with()`.
Jadi, kode berikut juga sudah ter-eagerload.

```php
foreach (Book::all() as $book) {
    echo $book->author->name;
}
```

> Method `with()` memiliki prioritas yang lebih tinggi dari properti `$with`,
> sehingga penggunaan method `with()` akan meng-override value yang telah anda
> tetapkan mmelalui properti `$with`.

<a id="membatasi-eagerloading"></a>

## Membatasi Eagerloading

Terkadang anda mungkin ingin meng-eagerload sebuah relasi, tetapi juga menentukan
kondisi untuk eagerloadingnya. Mudah saja. Begini caranya:

```php
$users = User::with(['posts' => function ($query) {
    // Lakukan eagerload hanya pada judul yang mengndung kata 'teknologi'
    $query->where('title', 'like', '%teknologi%');

}])->get();
```

Pada contoh diatas, kita meng-eagerload `posts` untuk `users`,
tetapi hanya akan dilakukan jika kolom `posts.title` mengandung kata 'foo'.

<a id="mutator--accessor"></a>

## Mutator & Accessor

Mutator memungkinkan anda untuk menangani penetapan atribut dengan cara khusus.
Definisikan mutator dengan menambahkan `set_` ke nama atribut yang anda inginkan.

Pada contoh ini, kita akan membuat mutator untuk atribut `'password'`:

```php
public function set_password($password)
{
    $this->set_attribute('password', Hash::make($password));
}
```

Panggil mutator tersebut sebagai variabel (tanpa tanda kurung) dan tanpa awalan `set_` seperti ini:

```php
$this->password = 'foobar'; // Hasilnya akan sama dengan $this->password = Hash::make('foobar');
```

Accessor merupakan kebalikan dari mutator. Accessor digunakan untuk memodifikasi attribut
sebelum atribut tersebut di-return ke kita.

Cara pendefinisiannya cukup dengan menambahkan kata `get_` ke nama atribut:

```php
public function get_published_at()
{
    return date('M j, Y', $this->get_attribute('published_at'));
}
```

Panggil mutator tersebut sebagai property dan tanpa awalan `get_` seperti ini:

```php
echo $this->published_at; // output: Sep 14, 2020
```

<a id="mass-assignment"></a>

## Mass-Assignment

Mass-assignment adalah praktik mengoper array asosiatif ke suatu model yang kemudian
ia akan mengisi atribut milik si model dengan value dari array tadi.
Mass-assignment dapat dilakukan dengan mengoper array ke constructor milik si model:

```php
$user = new User([
    'username' => 'budi01',
    'password' => 'rahasia'
]);

$user->save();
```

Selain itu, anda juga dapat melakukannya menggunakan method `fill()` seperti berikut:

```php
$user = new User();

$user->fill([
    'username' => 'budi01',
    'password' => 'rahasia'
]);

$user->save();
```

Secara default, semua key / value atribut akan disimpan selama mass-assignment.
Namun, anda juga bebas membuat _white-list_ atribut apa saja yang perlu disimpan.

Jika ada definisi white-list di model anda, maka hanya atribut - atribut yang berada dalam
white-list itu saja yang akan disimpan selama mass-assignment.

Untuk membuat white-list ini, silahkan tambahkan property `$fillable` berisi
array nama - nama kolom yang ingin anda white-list:

```php
public static $fillable = ['email', 'password', 'name'];
```

Selain itu, anda juga dapat melakukannya menggunakan method `fillable()` seperti berikut:

```php
User::fillable(['email', 'password', 'name']);
```

> Harap lakukan validasi secara cermat terlebih dahulu sebelum menjalankan mass-assignment
> menggunakan data inputan user. Keteledoran dapat menyebabkan celah keamanan yang serius.

<a id="konversi-model-ke-array"></a>

## Konversi Model Ke Array

Ketika ingin membuat JSON API, anda akan perlu untuk mengubah model menjadi array
sehingga dapat dengan mudah diserialisasi. Caranya sangat sederhana!

#### Mengkonversi sebuah model menjadi array:

```php
$users = $user->to_array();

return json_encode($users);
```

Metode `to_array()` akan secara otomatis mengambil semua atribut milik model anda,
beserta relasi yang dimilikinya.

Terkadang, anda mungkin tidak ingin menampilkan satu atau beberapa atribut milik model anda,
seperti attribut password misalnya.

Untuk melakukan ini, cukup tambahkan properti `$hidden` di model anda dan sebutkan
kolom apa saja yang tidak ingin anda tampilkan:

#### Mengecualikan atribut dari array:

```php
class User extends Facile
{
    public static $hidden = ['password'];

    // ...
}
```

<a id="delete-model"></a>

## Delete Model

Karena Facile mewarisi semua fitur dan method milik [query builder](/docs/id/database/magic),
menghapus model sangatlah mudah:

```php
$author->delete();
```

Walaupun begitu, ini tidak akan menghapus model yang berelasi
(misalnya semua model `Book` milik `Author` akan tetap ada), kecuali anda telah
mengatur [foreign key](/docs/id/database/schema#foreign-key) dan cascade delete.
