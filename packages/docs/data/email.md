# Email

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Konfigurasi](#konfigurasi)
- [Mengirim Email](#mengirim-email)
    - [Set Pengirim](#set-pengirim)
    - [Set Penerima](#set-penerima)
    - [Set CC dan BCC](#set-cc-dan-bcc)
    - [Set Body](#set-body)
    - [Alt Body](#alt-body)
    - [Set Subyek](#set-subyek)
    - [Prioritas](#prioritas)
    - [Lampiran](#lampiran)
    - [Siap Kirim](#siap-kirim)
- [Driver Kustom](#driver-kustom)

<!-- /MarkdownTOC -->


# Selayang Pandang
Komponen `Email` disediakan untuk membantu pekerjaan anda mengirim ini ke klien.
Komponen email mendukung beberapa fitur dasar seperti multi-protokol (mail, sendmail, dan SMTP);
enkripsi TLS dan SSL untuk SMTP; multi-penerima; CC dan BCC; email HTML atau plain-text;
lampiran serta prioritas email.


<a id="konfigurasi"></a>
## Konfigurasi

Sangat mudah untuk mengkonfigurasikan komponen ini karena telah disediiakan konfigurasi
default yang dapat anda temukan di file `application/config/email.php`.

Dalam keadaan default, rakit dikonfurasikan menggunakan driver `'mail'` yang berarti ia
akan menggunakan fungsi [mail()](https://www.php.net/manual/en/function.mail.php) untuk
transmisinya. Namun tentu saja anda boleh mengubahnya sesuai kebutuhan:

```php
'driver' => 'sendmail',
```

> Buka dan bacalah file `application/config/email.php.` agar anda mempunyai gambaran
  tentang preferensi apa saja yang dapat anda ubah.


<a id="mengirim-email"></a>
## Mengirim Email

Setelah selesai membaca file konfigurasi, mari kita lihat contoh mengirim email sederhana:

```php
$email = Email::make();

$email->from('admin@situs.com')
    ->to('eka@situs.com')
    ->cc('farida@situs.com')
    ->bcc('nando@situs.com')
    ->body('Dokumen PDF mengenai laporan keuangan bulan ini')
    ->alt_body('Laporan bulan ini')
    ->subject('Laporan Bulanan')
    ->priority(Email::HIGH)
    ->attach(path('storage').'laporan_bulanan.pdf');

try {
    $email->send();
} catch (\Exception $e) {
    echo 'Email gagal dikirim: '.$e->getMessage();
}
```


<a id="set-pengirim"></a>
### Set Pengirim

Method `from()` digunakan untuk menyetel pengirim email:

```php
$email->from('admin@situs.com');
```

Anda juga boleh menambahkan nama pengirim email:

```php
$email->from('admin@situs.com', 'Administrator');
```


<a id="set-penerima"></a>
### Set Penerima

Method `to()` digunakan untuk menyetel penerima email:

```php
$email->to('eka@situs.com');
```

Anda juga boleh menambahkan nama penerima email:

```php
$email->to('eka@situs.com', 'Eka Ramadhan');
```

Selain itu, anda juga boleh menambahkan beberapa penerima sekaligus:

```php
$email->to('eka@situs.com', 'Eka Ramadhan');
$email->to('budi@situs.com');
$email->to('dewi@situs.com');

// atau via array seperti ini:

$email->to([
    'eka@situs.com' => 'Eka Ramadhan',
    'budi@situs.com',
    'dewi@situs.com',
]);
```


<a id="set-cc-dan-bcc"></a>
### Set CC dan BCC
Cara penulisan CC dan BCC sama persis seperti set penerima diatas:

```php
$email->cc('farida@situs.com');
$email->cc('putri@situs.com', 'Putri Anggraini');

$email->bcc('hilman@situs.com');
$email->bcc('rachel@situs.com', 'Rachel Putri Toar');
```


<a id="set-body"></a>
### Set Body

Terdapat 2 opsi untuk menyetel body email anda, yaitu HTML dan plain-text:

#### 1. Plain Text
Gunakan opsi ini jika anda tahu email klien user anda terlalu usang sehingga hanya
bisa me-render email berisi teks saja:

```php
$email->body('Dokumen PDF mengenai laporan keuangan bulan ini');
```


#### 2. HTML Body

Gunakan opsi ini jika anda tahu email klien user anda bisa me-render email berisi tag HTML.
Opsi ini lebih disukai karena anda dapat membuat tampilan email yang cantik
dengan bantuan tag-tag HTML dan CSS:

```php
$email->html_body('<b>Dokumen PDF mengenai laporan keuangan bulan ini</b>');
```

Anda juga dapat memanfaatkan komponen `Markdown` jika diperlukan:

```php
$markdown = Markdown::render(path('base').'README.md');

$email->html_body($markdown);
```

Selain markdown, anda juga dapat memanfaatkan komponen `View` seperti berikut:

```php
$data = ['registered_at' => Date::now()];
$view = View::make('emails.registration_success', $data)->render();

$email->html_body($view);
```

Menyenangkan bukan?


<a id="alt-body"></a>
### Alt Body

Alt-Body atau alternatve body berisi ringkasan pendek tentng isi body email anda.
Meskipun sifatnya opsional (tidak harus ada), namun anda tetap dapat menambahkannya
jika memang diperlukan.

```php
$email->alt_body('Laporan bulan ini');
```

> Ketika menggunakan `html_body()`, anda bahkan tidak harus menambahkan alt-body
  karena ia akan ditambahkan secara otomatis oleh rakit.


<a id="set-subyek"></a>
### Set Subyek

Untuk menambahkan subyek atau judul email, gunakan method `subject()` seperti berikut:

```php
$email->subject('Laporan Bulanan');
```



<a id="prioritas"></a>
### Prioritas

Anda juga dapat menyetel prioritas email:

```php
$email->priority(Email::HIGH);
```

Konstanta prioritas email harus mengikuti tabel berikut:

| Konstanta         | Nilai                |
| ----------------- | -------------------- |
| `Email::LOWEST`   | 1 (Lowest)           |
| `Email::LOW`      | 2 (Low)              |
| `Email::NORMAL`   | 3 (Normal) - default |
| `Email::HIGH`     | 4 (High)             |
| `Email::HIGHEST`  | 5 (Highest)          |


<a id="lampiran"></a>
### Lampiran

Tersedia dua cara untuk menambahkan lapiran ke email, yaitu:

#### 1. Lampiran File:

```php
$file = path('storage').'laporan_bulanan.pdf';

$email->attach($file);
```
Anda juga dapat menambahkan lampiran secara inline dengan mengoper `TRUE` ke parameter
ke-dua dan `cid:<tag_id_html>` ke parameter ke-tiga sebagai penunjuk atributnya.

```php
$file = path('storage').'laporan_bulanan.pdf';

$email->attach($file, true, 'cid:my_content_id');
```


#### 2. Lampiran String:

```php
$contents = Storage::get(path('storage').'laporan_bulanan.pdf');

$email->string_attach($contents, 'laporan_bulanan.pdf');
```

Secara default, gambar di html akan dimuat secara otomatis, tetapi hanya jika filenya
berada di penyimpanan lokal. Lihatlah contoh berikut untuk memahami perbedaannya:

```php
// Ini akan dimuat secara otomatis
<img src="<?php echo asset('images/kitty.png'); ?>" />

// Ini tidak akan dimuat
<img src="https://situs-lain.com/images/kitty.jpg" />
```


<a id="siap-kirim"></a>
### Siap Kirim

Setelah seluruh data selesai disusun, langkah terakhir yang perlu dilakukan adalah
mengirimkan email anda:

```php
try {
    $email->send();
} catch (\Exception $e) {
    echo 'Email gagal dikirim: '.$e->getMessage();
}
```

> Pada contooh diatas kami membungkus eksekusi method `send()` kedalam blok try-catch
  agar ketika terjadi error saat pengiriman email, aplikasi anda akan terus berjalan.


<a id="driver-kustom"></a>
## Driver Kustom

Anda juga dapat mendaftarkan driver email lain jika 3 driver bawaan rakit tidak
sesuai dengan kebutuhan anda.

Buat kelas driver baru anda:

```php
// application/libraries/driverku.php

class Driverku extends Mailer
{
    protected function transmit()
    {
        // Logic pengiriman email anda ..
    }
}
```

Daftarkan driver baru anda ke rakit:

```php
// application/boot.php

$config = [
    // ..
];

Email::extend('driverku', function () use ($config) {
    return new Driverku($config);
});
```

Lalu tinggal ubah konfigurasi driver default ke driver yang baru saja anda buat:

```php
Config::set('email.driver', 'driverku');
```
