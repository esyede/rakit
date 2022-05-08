# Container

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Mendaftarkan Object](#mendaftarkan-object)
- [Me-resolve Object](#me-resolve-object)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Container hanyalah cara mengelola pembuatan object. Anda dapat menggunakannya untuk menentukan pembuatan objek kompleks,
memungkinkan anda me-resolvenya di seluruh aplikasi anda hanya dengan satu baris kode.
Anda juga dapat menggunakannya untuk _'meng-inject'_ dependensi ke kelas dan controller anda.

Container membantu membuat aplikasi anda lebih fleksibel dan mudah diuji.
Karena anda dapat mendaftarkan implementasi alternatif interface bia container ini,
anda dapat mengisolasi kode yang anda uji dari dependensi eksternal
menggunakan teknik [stub dan mocking](http://martinfowler.com/articles/mocksArentStubs.html).

<a id="mendaftarkan-object"></a>
## Mendaftarkan Object


#### Mendaftarkan resolver ke container:

```php
Container::register('mailer', function () {
    $transport = Swift_MailTransport::newInstance();

    return Swift_Mailer::newInstance($transport);
});
```

Mantap! Sekarang kita telah mendaftarkan resolver untuk SwiftMailer ke container kita.
Namun, bagaimana jika kita tidak ingin container membuat instance `mailer` baru setiap kali kita membutuhkannya?

Mungkin kita hanya ingin container mereturn instance yang sama setelah instance awal dibuat.
Mudah saja, cukup beri tahu si container bahwa objectnya harus singleton:

#### Mendaftarkan singleton object ke container:

```php
Container::singleton('mailer', function () {
    // ..
});
```

Anda juga dapat mendaftarkan instance object yang sudah ada sebelumnya sebagai singleton ke container.


#### Mendaftarkan instance yang ada ke container:

```php
Container::instance('mailer', $instance);
```

<a id="me-resolve-object"></a>
## Me-resolve Object

Setelah SwiftMailer terdaftar ke container, kita dapat dengan mudah me-resolvenya:

```php
$mailer = Container::resolve('mailer');
```

>  Anda juga boleh [mendaftarkan controller ke container](/docs/id/controllers#dependency-injection).
