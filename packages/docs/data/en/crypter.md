# Enkripsi

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Encrypting](#enkripsi-string)
-   [Decrypting](#dekripsi-string)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

The `Crypter` component provides a simple way to handle secure two-way encryption.
This class provides strong AES-256-based encryption and decryption via
[PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension.

> Don't forget to activate the [PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php)

<a id="enkripsi-string"></a>

## Encrypting

#### Mengenkripsi sebuah string:

To encrypt data, use the `encrypt()` method as follows:

```php
$data = 'secret';

$encrypted = Crypter::encrypt($data);
// 'qQzcNnAOhCQmqRvDkAsnLR2bjrbxn5M0+aMGVXS38HtkSvBs+g+dxD5xnoBxRoNpGDpAG5Y8SB5VtWAZxwLkZA=='
```

<a id="dekripsi-string"></a>

## Decrypting

#### Mendekripsi sebuah string:

To decrypt data, use the `decrypt()` method as follows:

```php
$decrypted = Crypter::decrypt($encrypted); // 'secret'
```
