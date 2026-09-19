# Encryption

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypting a String](#encrypting-a-string)
-   [Decrypting a String](#decrypting-a-string)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Crypter` handles two-way encryption: AES-256-CBC with HMAC-SHA256, through
[PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php).

> Don't forget to install the [PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension

> **Security:** encryption and MAC use separate keys, each derived as `HMAC-SHA256(RAKIT_KEY, purpose)`. Payloads carry `v=1`; older ones without it still decrypt.

<a id="encrypting-a-string"></a>

## Encrypting a String

#### Encrypting a string:

```php
$data = 'secret';

$encrypted = Crypter::encrypt($data);
// 'eyJpdiI6Ij...' (base64-encoded JSON containing iv, value, mac and v)
```

<a id="decrypting-a-string"></a>

## Decrypting a String

#### Decrypting a string:

```php
$decrypted = Crypter::decrypt($encrypted); // 'secret'
```
