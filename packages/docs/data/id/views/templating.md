# Templating

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Section](#section)
- [Blade Template Engine](#blade-template-engine)
- [Blade Kondisional & Looping](#blade-kondisional--looping)
- [Blade Layout](#blade-layout)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Aplikasi anda mungkin menggunakan layout umum di sebagian besar halamannya. Beruang kali membuat
layout ini secara manual dalam setiap action di controller pastinya akan cukup menyebalkan.

Mendefinisikan layout sebuah controller akan membuat pengembangan anda jauh lebih menyenangkan. Begini caranya:


#### Tambahkan property `$layout` ke controller anda:

```php
class Base_Controller extends Controller
{
	public $layout = 'layouts.common';

	// ..
}
```

> Setelah property `$layout` diisi, rakit akan secara cerdas mengubahnya menjadi instance kelas `View`.


#### Akses layoutnya dari action di controller:

```php
public function action_profile()
{
	$this->layout->nest('content', 'user.profile');
}
```

>  Ketika controller anda menggunakan fitur layout ini, action tidak perlu mereturn apapun untuk menampilkan view.


<a id="section"></a>
## Section

View section (atau bagian dari view) menyediakan cara sederhana untuk menyisipkan konten
ke dalam layout dari nested view. Misalnya, anda mungkin ingin menyisipkan JavaScript
yang diperlukan nested view ke header layout anda. Begini caranya:


#### Membuat section dalam view:

```php
<?php Section::start('scripts'); ?>
	<script src="jquery.js"></script>
<?php Section::stop(); ?>
```


#### Menampilkan isi dari sebuah section:

```php
<head>
	<?php echo Section::yield_content('scripts'); ?>
</head>
```


#### Menggunakan sintaks Blade untuk membuat section:

```php
@section('scripts')
	<script src="jquery.js"></script>
@endsection

<head>
	@yield('scripts')
</head>
```


<a id="blade-template-engine"></a>
## Blade Template Engine

Blade membuat penulisan view anda menjadi semakin menyenangkan. Untuk membuat view menggunakan blade,
cukup gunakan ekstensi `.blade.php` pada file view anda.

Blade memungkinkan anda untuk menggunakan sintaks yang lebih sederhana dan elegan untuk menulis
dan menampilkan data.


#### Menampilkan variabel menggunakan Blade:

```php
Hello, {{ $name }}.
```


#### Menampilkan hasil fungsi menggunakan Blade:

```php
{{ now() }}
```

 >  Sintaks `{{` `}}` sudah secara otomatis di-escape melalui fungsi
   [htmlentities()](https://www.php.net/manual/en/function.htmlentities.php) sehingga
   aman dari serangan XSS.


#### Blade & Framework JavaScript

Karena banyak framework JavaScript juga menggunakan tanda kurung kurawal untuk menunjukkan
sintaks yang diberikan harus ditampilkan di browser, anda dapat menggunakan simbol `@` untuk
memberi tahu mesin rendering Blade agar sintaks ini diabaikan. Sebagai contoh:

```php
Halo, @{{ $name }}.
```

Dalam contoh diatas, simbol `@` akan dihapus oleh Blade; namun, sintaks `{{ name }}` akan
tetap tidak tersentuh oleh mesin Blade, sehingga sintaks ini bisa di-render oleh
framework JavaScript anda.


#### Menampilkan data dengan default value

Terkadang anda mungkin ingin menampilkan suatu variabel, tetapi anda tidak yakin apakah variabel
tersebut telah di definisikan atau belum. Memang, anda dapat menulisnya secara verbose seperti:

```php
{{ isset($name) ? $name : 'Tamu' }}
```

Namun, alih-alih menulis menggunakan ternary operator seperti diatas, Blade memberi anda
shortcut yang lebih mudah:

```php
{{ $name or 'Tamu' }}
```

Dalam contoh diatas, jika variabel `$name` ada, valuenya akan ditampilkan. Namun, jika tidak ada,
kata `Tamu` akan ditampilkan.


#### Menampilkan data tanpa escape

Secara default, data yang diapit oleh sintaks `{{ }}` akan secara otomatis di-escape menggunakan
fungsi [htmlentities](https://www.php.net/manual/en/function.htmlentities.php) untuk
mencegah serangan XSS.

Jika anda tidak ingin data anda di-escape, anda dapat menggunakan sintaks berikut:

```php
Hello, {!! $name !!}
```

>  Berhati-hatilah saat menampilkan data hasil dari input user. Selalu gunakan
   sintaks `{{` `}}` untuk menghindari entitas HTML apapun dalam data.


#### Menampilkan sebuah view:

Gunakan sintaks `@include()` untuk mengimpor view ke view lain. View yang diimpor akan secara
otomatis mewarisi semua data dari view saat ini.

```php
<h1>Profile</hi>

@include('user.profile')
```

Demikian pula, anda dapat menggunakan `@render()`, yang berperilaku hampir sama
dengan `@include()` kecuali view yang di-render **tidak mewarisi** data dari view saat ini.

```php
@render('admin.list')
```


#### Membuat komentar:

```php
{{-- Ini adalah sebaris komentar --}}

{{--
	Ini adalah
	beberapa baris komentar.
	tulis sebanyak yang anda butuhkan.
--}}
```

>  Tidak seperti komentar di HTML, komentar Blade tidak terlihat ketia di view-source.



<a id="blade-kondisional--looping"></a>
## Blade Kondisional & Looping


#### If Statement:

```php
@if (5000 === $price)
    Wah, harganya 5 ribu!
@endif
```


#### If Else Statement:

```php
@if (count($messages) > 0)
    Ada pesan baru!
@else
    Tidak ada pesan baru!
@endif
```


#### Else If Statement:

```php
@if ('male' === $gender)
    Halo mas!
@elseif ('female' === $gender)
    Halo mbak!
@else
    Eh? mahluk apa ini?
@endif
```


#### Unless Statement:

```php
@unless(Auth::check())
    Silahkan login dulu!
@endunless

// sama dengan..

<?php if (! Auth::check()): ?>
    Silahkan login dulu!
<?php endif; ?>
```


#### Set Statement:

```php
@set('name', 'Budi')

// sama dengan..

<?php $name = 'Budi'; ?>
```


#### For Loop:

```php
@for ($i = 0; $i < 10; $i++)
    Angka saat ini adalah: {{ $i }}
@endfor
```


#### Foreach Loop:

```php
@foreach ($users as $user)
    <p>ID user saat ini adalah: {{ $user->id }}</p>
@endforeach
```


#### For Else Loop:

```php
@forelse ($users as $user)
    <li>{{ $user->name }}</li>
@empty
    <p>Tidak ada user</p>
@endforelse
```


#### While Loop:

```php
@while (true)
    <p>Aku adalah infinite loop. Hahaha</p>
@endwhile
```


#### PHP Block:

```php
@php
	$name = 'Angga';
	echo 'Halo '.$name;
@endphp

// sama dengan..

<?php
	$name = 'Angga';
	echo 'Halo '.$name;
?>
```


<a id="blade-layout"></a>
## Blade Layout

Blade tidak hanya menyediakan sintaks yang bersih dan elegan untuk struktur kontrol PHP yang umum,
tetapi juga memberi anda metode yang indah dalam menggunakan layout untuk view anda.

Misalnya, jika aplikasi anda menggunakan view `'master'` untuk memberikan tampilan yang konsisten
untuk aplikasi anda. Contohnya seperti ini:

```php
<html>
	<ul class="navigation">
		@section('navigation')
			<li><a href="home">Beranda</a></li>
			<li><a href="profile">Profil</a></li>
		@endsection
	</ul>

	<div class="content">
		@yield('content')
	</div>
</html>
```

Perhatikan bagian `'content'` yang di-`yield`. Kita perlu mengisi bagian ini dengan beberapa teks,
jadi mari kita buat view lain yang menggunakan view ini:

```php
@layout('master')

@section('content')
	Selamat datang di halaman profil!
@endsection
```

Mantap! Sekarang, kita cukup mereturn view `profile` dari route kita:

```php
return View::make('profile');
```

View `profile` akan secara otomatis menggunakan template `master` berkat bantuan tag `@layout()`.

> Pemanggilan `@layout()` harus selalu berada di baris paling pertama file view,
  tanpa whitespace maupun newline.


#### Menambahkan konten menggunakan `@parent`

Terkadang anda mungkin hanya ingin menambahkan sesuatu ke bagian layout daripada menimpanya.
Misalnya, perhatikan list navigasi di layout `master` [diatas tadi](#blade-layout).

Anggap saja kita hanya ingin menambahkan link `Kontak` ke list navigasi tadi. Begini caranya:

```php
@layout('master')

@section('navigation')
	@parent
	<li><a href="contact">Kontak</a></li>
@endsection

@section('content')
	Selamat datang di halaman profil!
@endsection
```

Tag `@parent` akan diganti dengan konten bagian _navigasi_ milik si layout, sehingga anda lebih
leluasa untuk meng-extend dan meng-inherit layout tersebut.
