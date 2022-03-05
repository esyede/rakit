# Paginasi Data

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Menggunakan Query Builder](#menggunakan-query-builder)
- [Menambahkan Link Paginasi](#menambahkan-link-paginasi)
- [Membuat Paginasi Manual](#membuat-paginasi-manual)
- [Mempercantik Paginasi](#mempercantik-paginasi)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Library ini dibuat untuk memudahkan anda dalam pembuatan navigasi pada banyak data.



<a id="menggunakan-query-builder"></a>
## Menggunakan Query Builder

Mari kita telusuri contoh lengkap pagination menggunakan [Query Builder](/docs/en/database/magic):


#### Ambil hasil paginasi dari kueri:

```php
$perpage = 10;

$orders = DB::table('orders')->paginate($perpage);
```

Anda juga bisa mengoper array nama kolom tabel yang ingin diambil dalam kueri:

```php
$orders = DB::table('orders')->paginate($perpage, ['id', 'name', 'created_at']);
```


#### Tampilkan hasilnya ke view:

```php
<?php foreach ($orders->results as $order): ?>
	<?php echo $order->id; ?>
<?php endforeach; ?>
```


#### Tampilkan juga link paginasinya:

```php
<?php echo $orders->links(); ?>
```

Metode `links()` diatas akan membuat daftar link halaman yang terlihat seperti ini:

```ini
Previous 1 2 ... 24 25 26 27 28 29 30 ... 78 79 Next
```

Paginator juga akan secara otomatis menentukan halaman mana anda saat ini dan memperbarui data
dan linknya. Anda juga dapat membuat link _"next"_ dan _"previous"_ tentunya:


#### Membuat link "next" dan "previous" sederhana:

```php
<?php echo $orders->previous().' '.$orders->next(); ?>
```

_Bacaan lebih lanjut:_

- _[Query Builder](/docs/en/database/magic)_



<a id="menambahkan-link-paginasi"></a>
## Menambahkan Link Paginasi

Anda juga dapat menambahkan lebih banyak item ke string kueri link paginasi, seperti
kolom yang anda sortir.


#### Menambahkan query string ke link paginasi:

```php
<?php echo $orders->appends(['sort' => 'votes'])->links(); ?>
```

Contoh diatas akan menghasilkan URL yang terlihat seperti ini:

```ini
mysite.com/movies?page=2&sort=votes
```


<a id="membuat-paginasi-manual"></a>
## Membuat Paginasi Manual

Terkadang anda mungkin perlu membuat paginasi secara manual, tanpa menggunakan query builder.
Begini caranya:


#### Membuat paginasi secara manua:

```php
$orders = Paginator::make($orders, $total, $perpage);
```


<a id="mempercantik-paginasi"></a>
## Mempercantik Paginasi

Semua elemen link paginasi dapat ditata menggunakan CSS. Berikut adalah contoh elemen HTML
yang dihasilkan oleh method `links()`:

```html
<div class="pagination">
	<ul>
		<li class="previous_page"><a href="foo">Previous</a></li>

		<li><a href="foo">1</a></li>
		<li><a href="foo">2</a></li>

		<li class="dots disabled"><a href="#">…</a></li>

		<li><a href="foo">11</a></li>
		<li><a href="foo">12</a></li>

		<li class="active"><a href="#">13</li>

		<li><a href="foo">14</a></li>
		<li><a href="foo">15</a></li>

		<li class="dots disabled"><a href="#">…</a></li>

		<li><a href="foo">25</a></li>
		<li><a href="foo">26</a></li>

		<li class="next_page"><a href="foo">Next</a></li>
	</ul>
</div>
```

Saat anda berada di halaman pertama, link "Previous" akan dinonaktifkan. Demikian juga,
link "Next" akan dinonaktifkan ketika anda berada di halaman terakhir.

HTML yang dihasilkan akan terlihat seperti ini:

```html
<li class="disabled previous_page"><a href="#">Previous</a></li>
```
