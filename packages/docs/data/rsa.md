# RSA Encryption

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypt Data](#encrypt-data)
-   [Decrypt Data](#decrypt-data)
-   [Load Keys](#load-keys)
-   [Export Keys](#export-keys)
-   [Padding](#padding)
-   [Data Details](#data-details)
-   [Large Data](#large-data)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`RSA` encrypts data with a public key and decrypts it with the matching private key, via the OpenSSL extension. Key pairs it generates are always RSA 2048-bit.

<a id="encrypt-data"></a>

## Encrypt Data

`encrypt()` returns raw binary, so base64-encode it before storing or sending it:

```php
$encrypted = RSA::encrypt('hello world');
$stored = base64_encode($encrypted);
```

> If no keys have been loaded, a key pair is generated on the first operation and reused for the rest of the request. It only lives in memory, so to decrypt in another request, [export](#export-keys) the keys and [load](#load-keys) them back later.

<a id="decrypt-data"></a>

## Decrypt Data

```php
$decrypted = RSA::decrypt($encrypted); // 'hello world'
```

Both methods throw an exception when OpenSSL fails (wrong key, corrupted data ...).

<a id="load-keys"></a>

## Load Keys

`load_keys()` uses your own PEM keys instead of generating a pair. If you only pass the private key, the public key is derived from it:

```php
RSA::load_keys(Storage::get('keys/private.pem'), Storage::get('keys/public.pem'));

// or
RSA::load_keys(getenv('RSA_PRIVATE_KEY'));
```

Keep private keys out of version control (environment variables, a secure vault, or a non-public file).

<a id="export-keys"></a>

## Export Keys

`export_private()` and `export_public()` return the keys in PEM format, generating a pair first if none exists yet:

```php
Storage::put('keys/private.pem', RSA::export_private());
Storage::put('keys/public.pem', RSA::export_public());
```

<a id="padding"></a>

## Padding

The default padding is `OPENSSL_PKCS1_OAEP_PADDING`. Pass another as the second argument, and use the same one when decrypting. Data encrypted with the previous default (PKCS#1 v1.5) is decrypted with:

```php
$decrypted = RSA::decrypt($encrypted, OPENSSL_PKCS1_PADDING);
```

Avoid PKCS#1 v1.5 for new data: where an attacker can submit ciphertext and see whether it decrypted, it allows recovering the plaintext (Bleichenbacher's attack). A failed decryption always throws the same `Failed to decrypt the data.` message for that reason.

<a id="data-details"></a>

## Data Details

`details()` returns the current key state:

```php
$details = RSA::details();

// private_key : private key in PEM format
// public_key  : public key in PEM format
// config      : contents of the temporary openssl config used while generating
// options     : options passed to openssl (private_key_bits, private_key_type, config)

echo $details['options']['private_key_bits']; // 2048
```

> `details()` does not generate keys by itself, so everything is empty until a key pair exists. `config` and `options` are only filled when Rakit generated the pair; they stay empty when the keys came from [RSA::load_keys()](#load-keys).

<a id="large-data"></a>
<a id="practical-examples"></a>

## Large Data

A 2048-bit RSA block holds only about 245 bytes, so `encrypt()` splits larger data into many blocks, which is slow. For large data, encrypt it with a random AES key and encrypt only that key with RSA:

```php
$aes_key = Str::random(32);
$iv = Str::bytes(16);

Storage::put('data.enc', json_encode([
    'key' => base64_encode(RSA::encrypt($aes_key)),
    'iv' => base64_encode($iv),
    'data' => openssl_encrypt($large_data, 'aes-256-cbc', $aes_key, 0, $iv),
]));
```
