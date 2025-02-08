# JSON Web Token (JWT)

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Encode Data](#encode-data)
-   [Decode Data](#decode-data)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Seperti namanya, komponen `JWT` menyediakan cara sederhana untuk menangani encode dan decode
[JSON Web Token](https://jwt.io/).
Komponen ini kompatibel dengan format [RFC 7519](https://tools.ietf.org/html/rfc7519)
yang banyak digunakan.

> Komponen ini hanya mendukung 3 algoritma standar saja, yaitu `HS256`, `HS384` dan `HS512`.

<a id="encode-data"></a>

## Enkripsi Data

#### Meng-encode data:

Untuk meng-encode data, gunakan method `encode()` seperti berikut:

```php
$secret = 's3cr3t';
$data = [
    'iss' => 'http://example.org',
    'aud' => 'http://example.com',
    'iat' => 1356999524,
    'nbf' => 1357000000,
];

$jwt = JWT::encode($data, $secret);
// dd($jwt);
```

### Header Tambahan

Selain dapat menentukan jenis algoritma yang ingin digunakan,
anda juga dapat mengoper header tambahan sesuai kebutuhan. Begini caranya:

```php
$headers = [
    'exp' => 3900, // kedaluwarsa dalam 65 menit
    'type' => 'bearer',
    'foo' => 'bar',
];

$jwt = JWT::encode($data, $secret, $headers);
```

### Algoritma

Secara default, proses diatas akan menggunakan algoritma `HS256`.

Namun tentu saja anda juga bisa menggantinya dengan yang lain:

```php
$jwt = JWT::encode($data, $secret, $headers, 'HS384');
// dd($jwt);
```

> Hanya mendukung algoritma berikut: `HS256` `HS384` dan `HS512`.


<a id="dekripsi-data"></a>

## Decode Data

#### Men-decode data:

Untuk men-decode data, gunakan method `decode()` seperti berikut:

```php
$decoded = JWT::decode($jwt, 's3cr3t');
// dd($decoded);
```
