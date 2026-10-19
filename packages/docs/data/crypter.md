# Encryption

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypting a String](#encrypting-a-string)
-   [Decrypting a String](#decrypting-a-string)
-   [Comparing Strings Safely](#comparing-strings-safely)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Crypter` provides two-way encryption using AES-256-CBC with an HMAC-SHA256 signature, via the
[PHP OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension (which must be installed).

> **Security:** encryption and MAC use separate keys, each derived as `HMAC-SHA256(RAKIT_KEY, purpose)`. Payloads carry `v=1`; older payloads without it still decrypt.

<a id="encrypting-a-string"></a>

## Encrypting a String

```php
$encrypted = Crypter::encrypt('secret');
// 'eyJpdiI6Ij...' (base64-encoded JSON containing iv, value, mac and v)
```

<a id="decrypting-a-string"></a>

## Decrypting a String

```php
$decrypted = Crypter::decrypt($encrypted); // 'secret'
```

If the payload is malformed, has been tampered with (invalid MAC), or cannot be decrypted, a `System\Exceptions\DecryptException` is thrown.

<a id="comparing-strings-safely"></a>

## Comparing Strings Safely

`equals()` compares two strings in constant time, which prevents timing attacks when checking tokens or signatures. It returns `false` if either value is not a string:

```php
if (Crypter::equals($known_token, $given_token)) {
    // match
}
```
