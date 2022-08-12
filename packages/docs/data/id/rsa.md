# RSA

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Enkripsi Data](#enkripsi-data)
- [Dekripsi Data](#dekripsi-data)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Seperti namanya, komponen `RSA` menyediakan cara sederhana untuk menangani enkripsi dan dekripsi
data menggunakan private dan public key sebagai pengamannya.

> Komponen ini hanya mendukung tipe private key RSA 2048 bit saja.


<a id="enkripsi-data"></a>
## Enkripsi Data


#### Mengenkripsi data:

Untuk mengenkripsi data, gunakan method `encrypt()` seperti berikut:

```php
$data = 'hello world';

$encrypted = RSA::encrypt($data);
// dd(base64_encode($encrypted));
```

Setelah operasi enkripsi data dilakukan, anda akan menemukan 2 buah file sertifikat
di folder `storage/` aplikasi anda, yaitu:
  - `rsa-public.pem` yang merupakan public key anda, dan
  - `rsa-private.pem` yang merupakan private key.

Kedua file tersebut akan digunakan oleh rakit untuk melakukan proses enkripsi dan dekripsi
melalui komponen ini.

Jadi, pastikan anda tidak menghapus atau membagikan file tersebut kepada orang lain
sehingga data anda aman dari pencurian.


<a id="dekripsi-string"></a>
## Dekripsi Data


#### Mendekripsi data:

Untuk mendenkripsi data, gunakan method `decrypt()` seperti berikut:

```php
$decrypted = RSA::decrypt($encrypted);
// dd($decrypted);
```

> Pastikan anda tidak menghapus atau membagikan file tersebut kepada orang lain
  sehingga data anda aman dari pencurian.
