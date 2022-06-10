# Enkripsi

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Encrypting](#enkripsi-string)
- [Decrypting](#dekripsi-string)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

The `Crypter` component provides a simple way to handle secure two-way encryption.
This class provides strong AES-256-based encryption and decryption via
[PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension.


>  Don't forget to activate the [PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php)
   extension on your server, and make sure the application key is filled in.



<a id="enkripsi-string"></a>
## Encrypting


#### Mengenkripsi sebuah string:

To encrypt data, use the `encrypt()` method as follows:


```php
$data = 'rahasia';

$encrypted = Crypter::encrypt($data);
// 'sGcqP0xG5qHyAJvnNa11pBOGk3c3iBUyDnFoyl81vKKPGNd4iMKVD/0NycbYBUMbwesSYi5xcKLFWD3nP6UYJA=='
```

<a id="dekripsi-string"></a>
## Decrypting


#### Mendekripsi sebuah string:

To decrypt data, use the `decrypt()` method as follows:


```php
$decrypted = Crypter::decrypt($encrypted); // 'rahasia'
```

>  It's important to note that this method will only decrypt the encrypted string
   using the same `application key`.

