# Curl

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Membuat Request](#membuat-request)
    -   [JSON Request](#json-request)
    -   [Form Request](#form-request)
    -   [Multipart Request](#multipart-request)
    -   [Multipart File](#multipart-file)
    -   [Custom Body](#custom-body)
-   [Otentikasi](#otentikasi)
-   [Cookie](#cookie)
-   [Response](#response)
-   [Konfigurasi Lanjutan](#konfigurasi-lanjutan)
    -   [JSON Decode](#json-decode)
    -   [Timeout](#timeout)
    -   [Proxy](#proxy)
    -   [Otentikasi Proxy](#otentikasi-proxy)
    -   [Default Headers](#default-headers)
    -   [Default cURL Options](#default-curl-options)
    -   [Validasi SSL](#validasi-ssl)
-   [Fungsi Tambahan](#fungsi-tambahan)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Curl adalah jenis command yang umum digunakan dalam sistem berbasis Unix. Sebetulnya,
istilah tersebut merupakan singkatan dari "Client URL".

Kegunaan dari command ini meliputi pemeriksaan konektivitas ke URL dan transfer data.
Selain itu, jenis command ini dapat digunakan dalam berbagai protokol. Curl juga dilengkapi
dengan [libcurl](https://curl.haxx.se/libcurl), library URL transfer yang bekerja pada sisi klien.

> Jangan lupa untuk menginstall ekstensi [PHP Curl](https://php.net/manual/en/book.curl.php)
> di server anda jika belum ada.

<a id="membuat-request"></a>

## Membuat Request

Rakit telah menyediakan beberapa fungionalitas yang dapat anda gunakan untuk bekerja dengan Curl.
Seperti ketika anda ingin mengambil data dari penyedia API pihak ketiga.

Berikut adalah beberapa tipe request yang dapat anda gunakan:

```php
Curl::get($url, $headers = [], $parameters = null)
Curl::post($url, $headers = [], $body = null)
Curl::put($url, $headers = [], $body = null)
Curl::patch($url, $headers = [], $body = null)
Curl::delete($url, $headers = [], $body = null)
```

Dimana:

-   `$url` - adalah endpoint tujuan pengiriman request.
-   `$headers` - adalah request header dalam format array
-   `$body` - adalah request body dalam format array

Selain itu, anda juga dapat mengirim request mengikuti
[metode standar](https://iana.org/assignments/http-methods/http-methods.xhtml)
yang telah disepakati maupun metode kustom sesuai kebutuhan anda:

```php
Curl::send(Curl::LINK, $url, $headers = [], $body);

Curl::send('CHECKOUT', $url, $headers = [], $body);
```

Sekarang, mari kita coba membuat request sederhana menggunakan komponen ini:

```php
$headers = ['Accept' => 'application/json'];
$query = ['foo' => 'hello', 'bar' => 'world'];

$response = Curl::post('https://mockbin.com/request', $headers, $query);

$response->code;        // berisi http status code
$response->headers;     // berisi object request headers
$response->body;        // berisi object request body
$response->raw_body;    // berisi string mentah body
```

<a id="json-request"></a>

### JSON Request

Untuk membuat json request silahkan gunakan method `body_json()` seperti berikut:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_json($data);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

Dengan method ini, header `'Content-Type'` akan otomatis di set ke `'application/json'`
dan body request juga akan diubah menjadi format json via [json_encode](https://php.net/json_encode).

<a id="form-request"></a>

### Form Request

Untuk membuat form request silahkan gunakan method `body_form()` seperti berikut:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_form($data);
$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

Dengan method ini, header `'Content-Type'` akan otomatis di set ke `'application/x-www-form-urlencoded'`
dan body request juga akan diubah menjadi format query string via [http_build_query](https://php.net/http_build_query).

<a id="multipart-request"></a>

### Multipart Request

Untuk membuat multipart request silahkan gunakan method `body_multipart()` seperti berikut:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_multipart($data);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

Dengan method ini, header `'Content-Type'` akan otomatis di set ke `'multipart/form-data'`
dan juga akan ditambahkan `--boundary` secara tomatis pula.

<a id="multipart-file"></a>

### Multipart File

Untuk membuat file upload request silahkan gunakan method `body_multipart()` seperti berikut:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];
$files = ['bio' => '/path/to/bio.json', 'avatar' => '/path/to/avatar.jpg'];

$body = Curl::body_multipart($data, $files);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

Tetapi jika anda ingin menyesuaikan lebih lanjut properti file yang diunggah,
anda dapat melakukannya dengan method `body_file()` seperti berikut:

```php
$headers = ['Accept' => 'application/json'];
$body = [
    'name' => 'budi',
    'age' => 28,
    'bio' => Curl::body_file('/path/to/bio.json'),
    'avatar' => Curl::body_file('/path/to/avatar.jpg', 'budi.jpg'),
];

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

> Pada contoh di atas, kita tidak menggunakan method `body_multipart()`,
> karena memeng tidak diperlukan ketika anda menambahkan file secara manual.

<a id="custom-body"></a>

### Custom Body

Anda juga bisa mengirim body request kustom tanpa menggunakan bantuan method `body_xxx` diatas,
misalnya, anda bisa menggunakan fungsi [serialize](https://php.net/serialize) untuk request body
dan juga dengan `Content-Type` kustom seperti berikut:

```php
$headers = ['Accept' => 'application/json', 'Content-Type' => 'application/x-php-serialized'];
$body = serialize(['foo' => 'hello', 'bar' => 'world']);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

<a id="otentikasi"></a>

## Otentikasi

Pada keadaan default, komponen ini akan menggunakan metode Basic Auth sehingga anda hanya perlu
mengoper username dan password _(opsional)_ untuk melakukan proses otentikasi request anda:

```php
// Basic auth (default)
Curl::auth('username', 'password');

// Custom auth
Curl::auth('username', 'password', CURLAUTH_DIGEST);
```

Pada parameter ke-3, anda dapat menentukan metode otentikasi apa yang anda butuhkan.
Berikut adalah daftar metode otentikasi yang didukung:

| Method               | Description                                                                        |
| -------------------- | ---------------------------------------------------------------------------------- |
| `CURLAUTH_BASIC`     | HTTP Basic auth (default)                                                          |
| `CURLAUTH_DIGEST`    | HTTP Digest auth ([RFC 2617](https://www.ietf.org/rfc/rfc2617.txt))                |
| `CURLAUTH_DIGEST_IE` | HTTP Digest auth IE (Internet Explorer)                                            |
| `CURLAUTH_NEGOTIATE` | HTTP Negotiate (SPNEGO) auth ([RFC 4559](https://www.ietf.org/rfc/rfc4559.txt))    |
| `CURLAUTH_NTLM`      | HTTP NTLM auth (Microsoft)                                                         |
| `CURLAUTH_NTLM_WB`   | NTLM WinBind ([dokumentasi](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)) |
| `CURLAUTH_ANY`       | Lihat: [dokumentasi](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |
| `CURLAUTH_ANYSAFE`   | Lihat: [dokumentasi](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |
| `CURLAUTH_ONLY`      | Lihat: [dokumentasi](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |

> Jika anda megoper lebih dari satu metode otentikasi (menggunakan bitmask operator misalnya),
> maka secara default, komponen ini akan terlebih dahulu melakukan request ke url tujuan
> untuk melihat metode otentikasi apa yang didukungnya, lalu menyesuaikan dengan metode yang anda oper tadi.
> _Untuk beberapa jenis metode, ini akan menyebabkan round-trip tambahan sehingga memperbeesar potensi timeout._

<a id="cookie"></a>

## Cookie

Anda juga dapat menambahkan satu atau beberapa cookie header,
cara penulisannya dipisahkan dengan semikolon dan spasi seperti berikut:

```php
$cookie = 'session=foo; logged=true';

Curl::cookie($cookie)
```

Selain menggunakan notasi sring, anda juga dapat menambahkan cookie header via file seperti berikut:

```php
$path = path('storage').'cookies.txt';

Curl::cookie_file($path)
```

Dimana isi file `cookies.txt` adalah deklarasi string cookie seperti yang telah dijelaskan diatas.

<a id="response"></a>

## Response

Setelah request dieksekusi, komponen ini akan selalu mereturn object `\stdClass` dengan property:

-   `code` - yang akan berisi http status code (misal. `200`)
-   `headers` - yang akan berisi http response headers
-   `body` - yang akan berisi response body yang telah diformat menjadi object atau array (jika memungkinkan).
-   `raw_body` - yang akan berisi response body mentah

<a id="konfigurasi-lanjutan"></a>

## Konfigurasi Lanjutan

Tentu saja, anda dapat lebih lanjut mengkonfigurasi komponen ini agar sesuai dengan kebutuhan anda.

<a id="json-decode"></a>

### JSON Decode

Untuk mengubah perilaku default json decode pada komponen ini, silahkan gunakan
method `json_options()` seperti contoh berikut:

```php
$associative = true; // Return sebagai array asosiatif
$depth = 512; // Set maximum nesting depth
$flags = JSON_NUMERIC_CHECK & JSON_FORCE_OBJECT & JSON_UNESCAPED_SLASHES; // Set decode flags

Curl::json_options($associative, $depth, $flags);
```

<a id="timeout"></a>

### Timeout

Anda juga dapat mengatur seberapa lama request harus dilakukan sampai ia time out:

```php
Curl::timeout(5); // Request timeout setelah 5 detik
```

<a id="proxy"></a>

### Proxy

Anda juga dapat mengatur proxy untuk request. Tipe proxy yang dapat digunakan antara lain:
`CURLPROXY_HTTP`, `CURLPROXY_HTTP_1_0`, `CURLPROXY_SOCKS4`,
`CURLPROXY_SOCKS5`, `CURLPROXY_SOCKS4A`, dan `CURLPROXY_SOCKS5_HOSTNAME`.

> Panduan lengkap mengenai tipe proxy bisa dilihat pada
> halaman [dokumentasi cURL](https://curl.haxx.se/libcurl/c/CURLOPT_PROXYTYPE.html)

```php
// Set proxy dengan port 1080 (port default)
Curl::proxy('10.10.10.1');

// Set proxy dan port kustom
Curl::proxy('10.10.10.1', 8080, CURLPROXY_HTTP);

// enable tunneling
Curl::proxy('10.10.10.1', 8080, CURLPROXY_HTTP, true);
```

<a id="otentikasi-proxy"></a>

### Otentikasi Proxy

Cara otentikasi proxy sama saja dengan cara [otentikasi request](#otentikasi) yang telah dijelaskan diatas:

```php
// Otentikasi proxy dengan basic auth
Curl::proxy_auth('username', 'password');

// Otentikasi proxy dengan digest auth
Curl::proxy_auth('username', 'password', CURLAUTH_DIGEST);
```

<a id="default-headers"></a>

### Default Headers

Anda juga dapat mendaklarasikan default header yang nantinya akan digunakan untuk setiap request,
sehingga anda tidak perlu mengulang - ulang deklarisikannya pada setiap request:

```php
Curl::default_header('Header1', 'Value1');
Curl::default_header('Header2', 'Value2');
```

Perlu mendeklarasikan beberapa default header sekaligus? mudah saja:

```php
Curl::default_headers([
    'Header1' => 'Value1',
    'Header2' => 'Value2',
]);
```

Selain itu, anda juga dapat menghapus seluruh default headers yang sudah anda deklarasikan tadi:

```php
Curl::clear_default_headers();
```

<a id="default-curl-options"></a>

### Default cURL Options

ANda juga dapat mendeklarasikan [cURL option](https://php.net/curl_setopt) default
yang akan digunakan pada setiap request:

```php
Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
```

Perlu mendeklarasikan beberapa default option sekaligus?

```php
Curl::curl_options([
    CURLOPT_COOKIE => 'foo=bar',
]);
```

Tentu saja, anda juga dapat menghapus seluruh default options yang sudah anda deklarasikan tadi:

```php
Curl::clear_curl_options();
```

<a id="validasi-ssl"></a>

### Validasi SSL

Dalam keadaan default, komponen ini menonaktifkan validasi SSL untuk kompatibilitas
dengan versi PHP terdahulu. Untuk mengubahnya, silahkan gunakan method berikut:

```php
// Aktifkan validasi ssl
Curl::verify_peer(true);
Curl::verify_host(true);

// Nonaktifkan validasi ssl
Curl::verify_peer(false);
Curl::verify_host(false);
```

<a id="fungsi-tambahan"></a>

## Fungsi Tambahan

```php
// Alias untuk fungsi curl_getinfo()`
Curl::info()

// Mereturn curl handler internal
Curl::handler()
```
