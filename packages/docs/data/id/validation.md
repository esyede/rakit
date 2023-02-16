# Validasi Data

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Rule Validasi](#rule-validasi)
    -   [Wajib Diisi](#wajib-diisi)
    -   [Alfabet, Angka & Tanda Hubung](#alfabet-angka--tanda-hubung)
    -   [Ukuran](#ukuran)
    -   [Angka](#angka)
    -   [Inklusi & Pengecualian](#inklusi--pengecualian)
    -   [Konfirmasi](#konfirmasi)
    -   [Persetujuan](#persetujuan)
    -   [Sama & Berbeda](#sama--berbeda)
    -   [Regular Expression](#regular-expression)
    -   [Keunikan & Eksistensi](#keunikan--eksistensi)
    -   [Tanggal](#tanggal)
    -   [E-Mail](#e-mail)
    -   [URL](#url)
    -   [File Upload](#file-upload)
    -   [Array](#array)
-   [Mengambil Pesan Error](#mengambil-pesan-error)
-   [Panduan Validasi](#panduan-validasi)
-   [Pesan Error Kustom](#pesan-error-kustom)
-   [Rule Validasi Kustom](#rule-validasi-kustom)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Hampir setiap aplikasi web interaktif perlu memvalidasi data. Misalnya, formulir pendaftaran
mungkin memerlukan password untuk dikonfirmasi. Mungkin alamat emailnya harus unik.

Memvalidasi data bisa menjadi proses yang tidak praktis. Syukurlah, ini tidak terjadi di Rakit.
Komponen `Validator` menyediakan sejumlah helper validasi yang luar biasa untuk memudahkan
proses validasi data anda. Mari kita lihat contohnya:

#### Ambil array data inputan yang akan divalidasi:

```php
$input = Input::all();
```

#### Definisikan rule validasi untuk tiap-tiap datanya:

```php
$rules = [
    'name'  => 'required|max:50',
    'email' => 'required|email|unique:users',
];
```

Selain menggunakan karakter `|` (pipe) sebagai pemisah, anda juga dapat menuliskannya
dengan sintaks array:

```php
$rules = [
  'name'  => ['required', 'max:50'],
  'email' => ['required', 'email', 'unique:users'],
];
```

#### Buat instance `Validator` dan validasilah datanya:

```php
$validation = Validator::make($input, $rules);

if ($validation->fails()) {
    dd($validation->errors);
}
```

Didalam properti `$errors`, anda dapat mengakses kelas `Messages` yang membuat
penanganan pesan error menjadi sangat mudah.

Tentu saja, pesan - pesan error default telah disertakan untuk semua rule validasi.
Pesan error default tersebut disimpan di file `language/id/validation.php`.

Sekarang anda sudah familiar dengan penggunaan dasar kelas `Validator`. Saatnya menggali
lebih dalam rule apa saja yang bisa anda gunakan untuk memvalidasi data anda!

<a id="rule-validasi"></a>

## Rule Validasi

-   [Wajib Diisi](#wajib-diisi)
-   [Alfabet, Angka & Tanda Hubung](#alfabet-angka--tanda-hubung)
-   [Ukuran](#ukuran)
-   [Angka](#angka)
-   [Inklusi & Pengecualian](#inklusi--pengecualian)
-   [Konfirmasi](#konfirmasi)
-   [Persetujuan](#persetujuan)
-   [Sama & Berbeda](#sama--berbeda)
-   [Regular Expression](#regular-expression)
-   [Keunikan & Eksistensi](#keunikan--eksistensi)
-   [Tanggal](#tanggal)
-   [E-Mail](#e-mail)
-   [URL](#url)
-   [File Upload](#file-upload)
-   [Array](#array)

<a id="wajib-diisi"></a>

### Wajib Diisi

#### Validasi bahwa atribut harus ada dan bukan string kosong:

```php
'name' => 'required',
```

#### Validasi bahwa atribut hasrus ada jika atribut lain ada:

```php
'last_name' => 'required_with:first_name',
```

<a id="alfabet-angka--tanda-hubung"></a>

### Alfabet, Angka & Tanda Hubung

#### Validasi bahwa atribut hanya terdiri dari huruf alfabet:

```php
'name' => 'alpha',
```

#### Validasi bahwa atribut hanya terdiri dari huruf alfabet dan angka:

```php
'username' => 'alpha_num',
```

#### Validasi bahwa atribut hanya terdiri dari huruf alfabet, angka, tanda hubung dan garis bawah:

```php
'username' => 'alpha_dash',
```

<a id="ukuran"></a>

### Ukuran

#### Validasi bahwa atribut memiliki panjang tertentu, atau, jika atribut berupa angka, ia adalah value tertentu:

```php
'name' => 'size:10',
```

#### Validasi bahwa ukuran atribut berada dalam rentang tertentu:

```php
'payment' => 'between:10,50',
```

> Nilai minimum dan maksimumnya bersifat inklusif. Maksudnya, jika user menginput `10` atau `50`
> maka validasi `between()` diatas akan lolos.

#### Validasi bahwa atribut minimal harus memiliki ukuran yang ditentukan:

```php
'payment' => 'min:10',
```

#### Validasi bahwa ukuran atribut tidak boleh melebihi yang ditentukan:

```php
'payment' => 'max:50',
```

<a id="angka"></a>

### Angka

#### Validasi bahwa atribut berupa angka:

```php
'payment' => 'numeric',
```

#### Validasi bahwa atribut berupa integer:

```php
'payment' => 'integer',
```

<a id="inklusi--pengecualian"></a>

### Inklusi & Pengecualian

#### Validasi bahwa atribut ada dalam list tertentu:

```php
'size' => 'in:small,medium,large',
```

#### Validasi bahwa atribut tidak ada dalam list tertentu:

```php
'language' => 'not_in:cobol,assembler',
```

<a id="konfirmasi"></a>

### Konfirmasi

Rule `'confirmed'` memvalidasi bahwa, untuk atribut tertentu, harus memiliki atribut lain
bernama `'xxx_confirmation'`, dimana `'xxx'` adalah nama atribut asalnya.

#### Validasi bahwa atribut sudah dikonfirmasi:

```php
'password' => 'confirmed',
```

Pada contoh diatas, si validator akan memvalidasi bahwa value milik atribut `'password'`
harus cocok dengan value di atribut `'password_confirmation'`.

<a id="persetujuan"></a>

### Persetujuan

Rule `'accepted'` memvalidasi bahwa value dari sebuah atribut merupakan salah satu
dari: `'yes'`, `'on'`, `'1'`, `1`, `true`, atau `'true'`. Rule ini sangat berguna ketika
memvalidasi form checkbox, seperti checkbox persetujuan peraturan situs.

#### Validasi bahwa atribut telah disetujui:

```php
'terms' => 'accepted',
```

<a id="sama--berbeda"></a>

### Sama & Berbeda

#### Validasi bahwa value sebuah atribut cocok dengan milik attribute lain:

```php
'token1' => 'same:token2',
```

#### Validasi bahwa value dua buah atribut memiliki value yang berbeda:

```php
'password' => 'different:old_password',
```

<a id="regular-expression"></a>

### Regular Expression

Rule `'match'` memvalidasi bahwa value sebuah atribut cocok dengan pola regular expression tertentu.

#### Validasi bahwa value sebuah atribut cocok dengan pola regular expression tertentu:

```php
'username' => 'match:/[a-zA-Z0-9]+/',
```

Ketika anda menggunakan rule `'match'` ini secara kompleks, sangat direkomendasikan untuk
menggunakan sintaks array untuk menghindari error pada regexnya:

```php
$rules = [
    'username' => ['required', 'max:20', 'match:/[a-zA-Z0-9]+/'],
];
```

<a id="keunikan--eksistensi"></a>

### Keunikan & Eksistensi

#### Validasi bahwa atribut unik pada tabel database tertentu:

```php
'email' => 'unique:users',
```

Pada contoh diatas, atribut `'email'` akan diperiksa keunikannya pada tabel `'users'`.
Unik disini artinya tidak ada duplikasi, atau kesamaan data.

Perlu memeriksa keunikan pada nama kolom yang berbeda dengan nama atribut? Tidak masalah:

#### Tentukan nama kolom kustom untuk memeriksa keunikan:

```php
'email' => 'unique:users,email_address',
```

Sering kali, saat memperbarui record di database, anda ingin menggunakan rule `'unique'`,
tetapi mengecualikan row yang sedang di-update. Misalnya, saat mengupdate profil user, anda dapat
mengizinkan mereka untuk mengubah alamat email.

Namun, ketika rule `'unique'` berjalan, pastinya anda tidak ingin menerapkannya ke user tertentu
karena si user mungkin saja tidak mengubah alamatnya, sehingga menyebabkan rule unique gagal.

Lalu bagaimana cara mengatasinya? Mudah saja:

#### Memaksa rule `unique` untuk mengabaikan ID tertentu:

```php
'email' => 'unique:users,email_address,10',
```

#### Validasi bahwa atribut ada di tabel database tertentu:

```php
'city' => 'exists:cities',
```

#### Tentukan nama kolom kustom untuk rule `exists`:

```php
'city' => 'exists:cities,abbreviation',
```

<a id="tanggal"></a>

### Tanggal

#### Validasi bahwa atribut tanggal adalah sebelum tanggal tertentu:

```php
'birthdate' => 'before:1992-11-02',
```

#### Validasi bahwa atribut tanggal adalah setelah tanggal tertentu:

```php
'birthdate' => 'after:1992-11-02',
```

> Rule `before` dan `after` ini menggunakan fungsi
> [strtotime()](https://php.net/manual/en/function.strtotime.php) untuk mengkonversi
> string tanggal yang diberikan.

#### Validasi format atribut tanggal cocok dengan format tertentu:

```php
'start_date' => 'date_format:H\\:i',
```

> Pada contoh diatas, `\\` (back-slash ganda) digunakan untuk meng-escape `:` (colon) sehingga karakter tersebut
> tidak dianggap sebagai parameter separator oleh PHP.

Opsi pemformatan untuk tanggal bisa anda baca di
[PHP Date](https://php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters).

<a id="e-mail"></a>

### E-Mail

#### Validasi bahwa atribut merupakan sebuah alamat e-mail:

```php
'address' => 'email',
```

> Rule ini menggunakan fungsi [filter_var()](https://php.net/manual/en/function.filter-var.php)
> untuk pengecekannya.

<a id="url"></a>

### URL

#### Validasi bahwa atribut merupakan sebuah URL:

```php
'link' => 'url',
```

#### Validasi bahwa atribut merupakan sebuah URL aktif:

```php
'link' => 'active_url',
```

> Rule ini menggunakan fungsi [checkdnsrr()](https://php.net/manual/en/function.checkdnsrr.php)
> untuk pengecekannya.

<a id="file-upload"></a>

### File Upload

Rule `mimes` memvalidasi bahwa file yang diupload memiliki jenis MIME tertentu.
Rule ini menggunakan ekstensi [PHP Fileinfo](http://php.net/manual/en/book.fileinfo.php) untuk
membaca konten file dan menentukan jenis MIME yang sebenarnya.

#### Validasi bahwa file adalah salah satu dari mime-type yang diberikan:

```php
'picture' => 'mimes:jpg,png,gif',
```

> Ketika memvalidasi file, pastikan untuk menggunakan `Input::file()` atau `Input::all()`
> untuk mengambil data input dari user.

#### Validasi bahwa file adalah gambar:

```php
'picture' => 'image',
```

#### Validasi bahwa ukuran file tidak melebihi ukuran yang ditentukan dalam kilobytes:

```php
'picture' => 'image|max:100',
```

<a id="array"></a>

### Array

#### Validasi bahwa atribut merupakan array:

```php
'categories' => 'array',
```

#### Validasi bahwa atribut merupakan array, dan memiliki tepat 3 elemen:

```php
'categories' => 'array|count:3',
```

#### Validasi bahwa atribut merupakan array, dan memiliki 1 sampai 3 elemen:

```php
'categories' => 'array|countbetween:1,3',
```

#### Validasi bahwa atribut merupakan array, dan setidaknya memiliki 2 elemen:

```php
'categories' => 'array|countmin:2',
```

#### Validasi bahwa atribut merupakan array, dan memiliki maksimal 2 elemen:

```php
'categories' => 'array|countmax:2',
```

<a id="mengambil-pesan-error"></a>

## Mengambil Pesan Error

Penanganan pesan error menjadi sangat mudah berkat kelas error collector di rakit yang sederhana.
Setelah memanggil method `passes()` atau `fails()` milik kelas `Validator`, anda bisa mengakses
pesan - pesan errornya via propreti `$errors`.

Si kelas error collector punya beberapa method bantuan untuk mempermudah anda dalam
pengambilan pesan error:

#### Periksa apakah suatu atribut memiliki pesan error:

```php
if ($validation->errors->has('email')) {
    // Atribut e-mail memiliki error..
}
```

#### Ambil pesan error pertama untuk sebuah atribut:

```php
echo $validation->errors->first('email');
```

Terkadang anda mungkin perlu memformat pesan error dengan membungkusnya dalam tag HTML.

Tidak masalah. Cukup operkan format yang anda inginkan bersama dengan placeholder `:message`
ke parameter ke-dua.

#### Memformat sebuah pesan error:

```php
echo $validation->errors->first('email', '<p>:message</p>');
```

#### Ambil semua pesan error untuk atribut tertentu:

```php
$messages = $validation->errors->get('email');
```

#### Format semua pesan error untuk atribut tertentu:

```php
$messages = $validation->errors->get('email', '<p>:message</p>');
```

#### Ambil semua pesan error untuk seluruh atribut:

```php
$messages = $validation->errors->all();
```

#### Format semua pesan error untuk seluruh atribut:

```php
$messages = $validation->errors->all('<p>:message</p>');
```

<a id="panduan-validasi"></a>

## Panduan Validasi

Setelah anda melakukan validasi, anda memerlukan cara mudah untuk mengembalikan pesan error tersebut
ke view agar bisa dilihat oleh user.

Mudah saja. Mari kita telusuri skenario umum berikut. Kami akan menentukan dua rute:

```php
Route::get('register', function () {
    return View::make('register');
});

Route::post('register', function () {
    $rules = [ ... ]; // rule validasi disini

    $validation = Validator::make(Input::all(), $rules);

    if ($validation->fails()) {
        return Redirect::to('register')->with_errors($validation);
    }
});
```

Mantap! Jadi, kita memiliki dua buah route untuk registrasi akun. Satu untuk menmpilkan
view formulir, dan satu lagi untuk menangani data POST yang dikirim dari formulir tadi.

Di route POST, kita menjalankan beberapa validasi atas inputan user. Jika validasi gagal,
kita mengarahkan kembali ke formulir registrasi dan mem-flash pesan error validasi ke session
sehingga akan bisa diakses secara global, dengan begitu kita bisa tampilkan pesan errornya di view.

**Perhatikan bahwa kita tidak secara eksplisit mengikat pesan error ke view di route GET kita**.

Namun, variabel `$error` akan tetap tersedia di view. Rakit dengan cerdas menentukan apakah
ada error dalam session, dan jika ada, ia akan secara otomatis mengikatnya ke view untuk anda.

Jika tidak ada error dalam session, message container kosong akan tetap terikat ke view.

Dalam view anda, ini memungkinkan anda untuk selalu menganggap anda memiliki message container
yang tersedia melalui variabel `$error`. Ini pastinya akan membuat hidup anda lebih mudah.

Misalnya, jika validasi email gagal, kita dapat mencari `'email'` di dalam variabel session `$error`.

```php
$errors->has('email')
```

Dengan [Blade](/docs/id/views/templating#blade-template-engine), kita kemudian dapat menambahkan
pesan errornya ke view kita secara kondisional:

```php
{!! $errors->has('email') ? 'Email tidak sah' : 'Kondisinya false. Tidak boleh dikosongkan' !!}
```

Ini juga akan bekerja dengan baik ketika kita perlu menambahkan kelas secara kondisional saat
menggunakan sesuatu seperti Bootstrap. Misalnya, jika validasi email gagal, kita mungkin ingin
menambahkan kelas `"error"` dari Bootstrap ke `<div class="control-group">`.

```html
<div class="control-group{{ $errors->has('email') ? ' error' : '' }}"></div>
```

Saat validasinya gagal, view yang kita render akan memiliki kelas `'error'` yang ditambahkan tadi.

```html
<div class="control-group error"></div>
```

<a id="pesan-error-kustom"></a>

## Pesan Error Kustom

Ingin menggunakan pesan error selain default? Mungkin anda bahkan ingin menggunakan pesan error
kustom untuk atribut dan rule tertentu. Pasti bisa dong!

#### Buat sebuah array pesan error kustom untuk Validator:

```php
$messages = [
    'required' => 'The :attribute field is required.',
];

$validation = Validator::make(Input::get(), $rules, $messages);
```

Sekarang pesan kustom anda akan digunakan setiap kali pemeriksaan validasi yang diperlukan gagal.
Tapi, apa sih maksudnya `:attribute` itu? Kok tiba - tiba dia ada disitu?

Untuk membuat hidup anda lebih mudah, kelas `Validator` akan mengganti placeholder `:attribute`
tersebut dengan nama atribut yang sebenarnya!

Ia bahkan juga akan menghapus garis bawah dari nama atribut sehingga lebih enak dilihat oleh user.

Anda juga boleh menggunakan placeholder `:other`, `:size`, `:min`, `:max`, dan `:values` ketika
membuat pesan error kustom:

#### Placeholder lain untuk pesan error validasi:

```php
$messages = [
    'same' => 'Bilah :attribute dan :other harus cocok.',
    'size' => 'Bilah :attribute ukurannya harus diisi tepat :size.',
    'between' => 'Bilah :attribute harus diisi antara :min - :max.',
    'in'  => 'Bilah :attribute harus berisi salah satu dari: :values',
];
```

Lalu, bagaimana jika anda perlu menentukan pesan kustom, tetapi hanya untuk atribut _email_?
Gampang. Cukup tentukan pesan menggunakan konvensi penamaan `[nama atribut]` + `_` + `[nama rule]`
seperti ini:

#### Menentukan pesan error kustom untuk atribut tertentu:

```php
$messages = [
    'email_required' => 'We need to know your e-mail address!',
];
```

Pada contoh di atas, pesan kustom yang diperlukan akan digunakan untuk atribut email, sedangkan
pesan default akan digunakan untuk semua atribut lainnya.

Namun, jika anda menggunakan banyak pesan error kustom, menulisnya secara inline di setiap
kode validasi tentu akan membuat kode anda menjadi rumit dan terkesan berantakan.

Oleh karena itu, anda dapat menaruh pesan kustom anda dalam array konfigurasi `'custom'` dalam
file bahasa validasi:

#### Menambahkan pesan error kustom via file bahasa validasi:

```php
'custom' => [
    'email_required' => 'We need to know your e-mail address!',
]
```

<a id="rule-validasi-kustom"></a>

## Rule Validasi Kustom

Rakit telah menyediakan sejumlah rule validasi yang sering dipakai banyak orang.
Namun, sangat mungkin anda perlu membuat rule validasi kustom untuk kebutuhan sendiri.

Ada dua metode sederhana untuk membuat rule validasi. Keduanya solid, jadi gunakan manapun
yang menurut anda paling sesuai dengan kebutuhan anda.

#### Mendaftarkan rule validasi kustom:

```php
Validator::register('humble', function ($attribute, $value, $params) {
    return ($value === 'humble');
});
```

Dalam contoh ini kita mendaftarkan rule validasi baru ke validator. Rule tersebut menerima
tiga parameter. Yang pertama adalah nama atribut yang hendak divalidasi,
yang kedua adalah value atribut yang hendak divalidasi, dan yang ketiga adalah array parameter
yang ditentukan untuk rule tersebut.

Berikut adalah tampilan rule validasi kustom anda saat dipanggil:

```php
$rules = [
    'attitude' => 'required|humble',
];
```

Tentu saja, anda perlu menentukan pesan error untuk rule baru anda. Anda dapat melakukan ini
baik dalam array seperti ini:

```php
$messages = [
    'humble' => 'Anda harus selalu rendah hati!',
];

$validator = Validator::make(Input::get(), $rules, $messages);
```

Atau dengan cara menambahkannya ke file `language/id/validation.php`:

```php
'humble' => 'Anda harus selalu rendah hati!',
```

Seperti disebutkan di atas, anda dapat menentukan dan menerima array parameter dalam rule kustom anda:

```php
// Pendaftaran rule kustom
Validator::register('humble', function ($attribute, $value, $params) {
    return ($value === 'yes');
});


// Cara pemakaian
$rules = [
    'attitude' => 'required|humble:yes',
];
```

Dalam kasus ini, parameter rule validasi anda akan menerima array yang berisi satu elemen: `'yes'`.

Metode lain untuk membuat dan menyimpan rule validasi kustom adalah dengan meng-extends
kelas `Validator` itu sendiri. Dengan meng-extends kelas, anda membuat versi baru validator
yang memiliki semua fungsionalitas yang sudah ada sebelumnya yang dikombinasikan dengan
penambahan rule kustom anda sendiri.

Anda bahkan dapat memilih untuk mengganti beberapa metode default jika anda mau.
Mari kita lihat contohnya!

Pertama, buat kelas yang meng-extends `Validator` dan letakkan di
direktori `application/libraries/` anda:

#### Membuat kelas validator kustom:

```php
class Validator extends \System\Validator
{
    // ..
}
```

Selanjutnya, hapus `Validator` dari array alias di file `config/aliases.php`. Hal ini
diperlukan agar tidak ada 2 class bernama Validator karena tentunya akan bentrok satu sama lain:

```php
'aliases' => [
    // ..


    'Validator' => 'System\Validator', // Hapus bagian ini

    // ..
],
```

Selanjutnya, tinggal kita pindahkan rule `'humble'` kita tadi kedalam kelas tersebut:

#### Menambahkan rule validasi kustom ke kelas:

```php
class Validator extends \System\Validator
{
    public function validate_humble($attribute, $value, $params)
    {
        return ($value === 'yes');
    }
}
```

Perhatikan bahwa nama methodnya dinamai menggunakan konvensi penamaan `validate_` + `[nama rule]`.
Rule kustom kita tadi bernama `'humble'` sehingga method tersebut harus dinamai `validate_humble`.

Seluruh method validasi di kelas Validator harus me-return `TRUE` atau `FALSE`, bukan yang lain.

Perlu diingat bahwa anda masih perlu membuat pesan error kustom untuk rule validasi baru yang anda buat.
