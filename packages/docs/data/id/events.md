# Event & Listener

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Menjalankan Event](#menjalankan-event)
-   [Me-listen Sebuah Event](#me-listen-sebuah-event)
-   [Antrian Event](#antrian-event)
-   [Framework Event](#framework-event)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Event memberikan cara yang bagus untuk memecah keterkaitan resource dalam aplikasi anda,
sehingga kelas, library ataupun plugin tidak akan tercampur dan mudah untuk diawasi.

Hal ini juga memungkinkan kelas, library dan plugin untuk memanfaatkan inti aplikasi anda
tanpa mengubah kodenya.

<a id="menjalankan-event"></a>

## Menjalankan Event

Untuk menjalankan event, cukup beritahukan nama event apa yang ingin anda jalankan:

#### Menjalankan sebuah event:

```php
$responses = Event::fire('loaded');
```

Perhatikan bahwa kami menyimpan hasil dari metode `fire()` ke variabel `$responses`. Karena
method `fire()` ini akan mereturn array yang berisi response dari semua listener milik si event.

Terkadang anda ingin menjalankan sebuah event, tetapi hanya ingin mengambil response
pertamanya saja. Begini caranya:

#### Menjalankan sebuah event dan hanya mengambil response pertama saja:

```php
$response = Event::first('loaded');
```

> Method `first()` ini akan tetap menjalankan seluruh listener yang dimiliki oleh si event,
> tetapi hanya response pertamanya saja yang akan direturn.

Sedangkan method `Event::until()` akan menjalankan seluruh listener yang dimiliki oleh si event,
dan akan mereturn response pertama yang nilainya bukan `NULL`.

#### Menjalankan sebuah event dan mengambil response pertama yang bukan NULL:

```php
$response = Event::until('loaded');
```

<a id="me-listen-sebuah-event"></a>

## Me-listen Sebuah Event

Tetapi, apa gunanya membuat event jika ia tidak punya listener? Jadi, mari kita daftarkan
sebuah contoh listener yang akan dipanggil ketika sebuah event dijalankan:

#### Mendaftarkan sebuah listener ke event bernama `'loaded'`:

```php
Event::listen('loaded', function () {
    // Aku akan terpanggil ketika event 'loaded' dijalankan
});
```

Kode yang anda letakkan didalam Closure diatas akan terpanggil ketika event `'loaded'` dijalankan.

<a id="antrian-event"></a>

## Antrian Event

Terkadang anda mungkin hanya ingin _"mengantrikan"_ suatu event untuk dijalankan diwaktu mendatang.
Anda boleh melakukannya via method `queue()` dan `flush()`.

Pertama, silahkan oper nama event ke parameter pertama, ingat! namanya harus unik agar tidak
saling tumpang tindih:

#### Mengantrikan sebuah event:

```php
Event::queue('foo', $user->id, [$user]);
```

Method ini menerima 3 parameter. Parameter pertama adalah nama antreannya, yang kedua adalah nama
unik untuk item ini di antrean, dan yang ketiga adalah array data yang ingin dioper ke flusher.

Selanjutnya, kita akan mendaftarkan flusher untuk antrian bernama `foo` diatas:

#### Mendaftarkan sebuah event flusher:

```php
Event::flusher('foo', function ($key, $user) {
    // ...
});
```

Perhatikan bahwa si flusher ini menerima 2 parameter. Pertama, nama unik dari event
yang diantrikan, yang pada kasus diatas adalah user ID. Lalu parameter kedua (dan sisanya)
akan menjadi item payload untuk antrian event.

Terakhir, kita sudah bisa menjalankan flusher dan mem-flush semua event yang diantrikan
menggunakan method `flush()`:

```php
Event::flush('foo');
```

<a id="framework-event"></a>

## Framework Event

Berikut adalah beberapa event yang secara default akan dijalankan oleh rakit:

#### Event yang dijalankan ketika sebuah package di-boot:

```php
Event::listen('rakit.booted: package', function () { });
```

#### Event yang dijalankan ketika sebuah query database dieksekusi:

```php
Event::listen('rakit.query', function ($sql, $bindings, $time) { });
```

#### Event yang dijalankan tepat sebelum sebuah response dikirim ke browser:

```php
Event::listen('rakit.done', function ($response) { });
```

#### Event yang dijalankan ketika sebuah pesan dicatat menggunakan kelas `Log`:

```php
Event::listen('rakit.log', function ($type, $message) { });
```

Berikut adalah daftar lengkap event bawaan famework beserta parameter - parameternya.
Anda dapat me-listen event - event berikut jika memang diperlukan:

| Command                                         | Parameter                                             |
| ----------------------------------------------- | ----------------------------------------------------- |
| `Event::fire('rakit.done',`                     | `[Response $response]);`                              |
| `Event::fire('rakit.log',`                      | `[string $type, string $message]);`                   |
| `Event::fire('rakit.query',`                    | `[string $sql, array $bindings, string $time]);`      |
| `Event::fire('rakit.resolving',`                | `[string $type, mixed $object]);`                     |
| `Event::fire('rakit.composing: [view_name]',`   | `[View $view]);`                                      |
| `Event::fire('rakit.booted: [package_name]');`  | `None`                                                |
| `Event::first('rakit.controller.factory',`      | `[string $className]);`                               |
| `Event::first('rakit.config.loader',`           | `[string $package, string $file]);`                   |
| `Event::first('rakit.language.loader',`         | `[string $package, string $language, string $file]);` |
| `Event::until('rakit.view.loader',`             | `[string $package, string $view]);`                   |
| `Event::until('rakit.view.engine',`             | `[View $view]);`                                      |
| `Event::first('rakit.view.middleware',`         | `[string $content, string $path]);`                   |
| `Event::first('rakit.auth: login');`            | `None`                                                |
| `Event::first('rakit.auth: logout');`           | `None`                                                |
| `Event::fire('facile.saving',`                  | `[Facile $model]);`                                   |
| `Event::fire('facile.saving: [class_name]',`    | `[Facile $model]);`                                   |
| `Event::fire('facile.updated',`                 | `[Facile $model]);`                                   |
| `Event::fire('facile.updated: [class_name]',`   | `[Facile $model]);`                                   |
| `Event::fire('facile.created',`                 | `[Facile $model]);`                                   |
| `Event::fire('facile.created: [class_name]',`   | `[Facile $model]);`                                   |
| `Event::fire('facile.saved',`                   | `[Facile $model]);`                                   |
| `Event::fire('facile.saved: [class_name]',`     | `[Facile $model]);`                                   |
| `Event::fire('facile.deleting',`                | `[Facile $model]);`                                   |
| `Event::fire('facile.deleting: [class_name]',`  | `[Facile $model]);`                                   |
| `Event::fire('facile.deleted',`                 | `[Facile $model]);`                                   |
| `Event::fire('facile.deleted: [class_name]',`   | `[Facile $model]);`                                   |
| `Event::fire('rakit.jobs.run: [job_name]',`     | `[string $name, array $payloads]);`                   |
| `Event::fire('rakit.jobs.ran: [job_name]',`     | `[string $name, array $payloads]);`                   |
| `Event::fire('rakit.jobs.forget: [job_name]');` | `None`                                                |
| `Event::first('500');`                          | `None`                                                |
| `Event::first('404');`                          | `None`                                                |
