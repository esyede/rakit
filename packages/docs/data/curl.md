# Bekerja dengan cURL

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Membuat Request](#membuat-request)
- [Opsi Kustom](#opsi-kustom)
- [Mengolah Response](#mengolah-response)
    - [Response Header](#response-header)
    - [Response Body](#response-body)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Curl adalah jenis command yang umum digunakan dalam sistem berbasis Unix. Sebetulnya,
istilah tersebut merupakan singkatan dari "Client URL".

Kegunaan dari command ini meliputi pemeriksaan konektivitas ke URL dan transfer data.
Selain itu, jenis command ini dapat digunakan dalam berbagai protokol. Curl juga dilengkapi
dengan [libcurl](https://curl.se/libcurl), library URL transfer yang bekerja pada sisi klien.

>  Jangan lupa untuk menginstall ekstensi [PHP Curl](http://php.net/manual/en/book.curl.php)
   di server anda jika belum ada.



<a id="membuat-request"></a>
## Membuat Request

Rakit telah menyediakan beberapa fungionalitas yang dapat anda gunakan untuk bekerja dengan Curl.
Seperti ketika anda ingin mengambil data melalui API pihak ketiga, mendownload file dan lain - lain.

Sekarang, mari kita coba membuat request sederhana menggunakan komponen ini:


#### Membuat GET request
Untuk membuat request bertipe `GET`, silahkan gunakan method `get()` seperti ini:

```php
$response = Curl::get('https://reqres.in/api/users?page=2');
```


#### Membuat POST request
Untuk membuat request bertipe `POST`, silahkan gunakan method `post()` seperti ini:

```php
$parameters = ['name' =>  'Danang', 'age' => 25];

$response = Curl::post('https://reqres.in/api/users', $parameters);
```


#### Membuat PUT request
Untuk membuat request bertipe `PUT`, silahkan gunakan method `put()` seperti ini:

```php
$parameters = ['name' =>  'Agus', 'age' => 24];

$response = Curl::put('https://reqres.in/api/users', $parameters);
```


#### Membuat DELETE request
Untuk membuat request bertipe `DELETE`, silahkan gunakan method `delete()` seperti ini:

```php
$parameters = ['id' => 6];

$response = Curl::delete('https://reqres.in/api/users', $parameters);
```

Selain menggunakan cara spesifik diatas, anda juga dapat melakukan request via
method `request()` seperti ini:

```php
$response = Curl::request($method = 'get', $url, $params, $options);
```


#### Download file
Anda juga dapat mendownoad file menggunakan komponen ini. Caranya juga sangat mudah:

```php
$target = 'https://github.com/esyede/rakit/archive/master.zip';
$destination = path('storage').'rakit.zip';

if (Curl::download($target, $destination)) {
	// Yay! download berhasil!
}
```


<a id="opsi-kustom"></a>
## Opsi Kustom

Dalam penggunaannya, setiap orang tentu perlu mengirimkan request curl dengan konfigurasi yang
berbeda satu dengan yang lain. Untuk itu, kami telah menyediakan fungsi set opsi untuk
mengakomodir kebutuhan tersebut.

```php
$parameters =[];
$custom_options = [
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTPHEADER => [
		'Cache-Control: no-cache',
		'Accept-Encoding: gzip, deflate',
		'Accept-Language: en-US,en;q=0.5',
	],
];

$response = Curl::get('https://foobar.com', $parameters, $custom_options);
```

List cURL option secara lengkap bisa anda baca di
[dokumentasi resminya](https://www.php.net/manual/en/function.curl-setopt.php).



<a id="mengolah-response"></a>
## Mengolah Response

Setelah request dieksekusi, komponen ini akan mereturn object `stdClass` berisi respon dari
request yang anda buat. Respon inilah yang kemudian dapat anda olah untuk kebutuhan aplikasi anda.


<a id="response-header"></a>
### Response Header

```php
dd($response->header);
```

<a id="response-body"></a>
### Response Body

```php
dd($response->body);
```
