# Encryption

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypting a String](#encrypting-a-string)
-   [Decrypting a String](#decrypting-a-string)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `Crypter` component provides a simple way to handle secure two-way encryption.
This class provides strong AES-256 based encryption and decryption through
the [PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension.

> Don't forget to install the [PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension

<a id="encrypting-a-string"></a>

## Encrypting a String

#### Encrypting a string:

To encrypt data, use the `encrypt()` method as follows:

```php
$data = 'secret';

$encrypted = Crypter::encrypt($data);
// 'sGcqP0xG5qHyAJvnNa11pBOGk3c3iBUyDnFoyl81vKKPGNd4iMKVD/0NycbYBUMbwesSYi5xcKLFWD3nP6UYJA=='
```

<a id="decrypting-a-string"></a>

## Decrypting a String

#### Decrypting a string:

To decrypt data, use the `decrypt()` method as follows:

```php
$decrypted = Crypter::decrypt($encrypted); // 'secret'
```
