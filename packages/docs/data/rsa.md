# RSA Encryption

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypt Data](#encrypt-data)
-   [Decrypt Data](#decrypt-data)
-   [Load Keys](#load-keys)
-   [Export Keys](#export-keys)
-   [Data Details](#data-details)
-   [Practical Examples](#practical-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

As the name suggests, the `RSA` component provides a simple way to handle encryption and decryption
of data using private and public keys for security.

> This component only supports RSA 2048-bit private key type.

<a id="encrypt-data"></a>

## Encrypt Data

#### Encrypt data:

To encrypt data, use the `encrypt()` method as follows:

```php
$data = 'hello world';

$encrypted = RSA::encrypt($data);
// dd(base64_encode($encrypted));
```

> Private key and public key are automatically generated each time an encryption operation is performed
> so you don't need to bother storing the public key and private key anymore.

<a id="decrypt-data"></a>

## Decrypt Data

#### Decrypt data:

To decrypt data, use the `decrypt()` method as follows:

```php
$decrypted = RSA::decrypt($encrypted);
// dd($decrypted);
```

<a id="data-details"></a>

## Data Details

#### Encryption data details:

To see detailed encryption info, use the `details()` method as follows:

```php
$details = RSA::details();
// dd($details);
```

This will return an array containing information about private key, public key, and encryption metadata.

<a id="load-keys"></a>

## Load Keys

By default, RSA will generate a new key pair each time encryption is performed. However, you can load an existing key pair using the `load_keys()` method:

#### Load existing private and public keys:

```php
$private_key = '-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...
-----END PRIVATE KEY-----';

$public_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvZ...
-----END PUBLIC KEY-----';

RSA::load_keys($private_key, $public_key);

// Now encryption will use the loaded keys
$encrypted = RSA::encrypt('hello world');
$decrypted = RSA::decrypt($encrypted);
```

#### Load only private key:

```php
// Public key will be automatically generated from private key
RSA::load_keys($private_key);

$encrypted = RSA::encrypt('hello world');
$decrypted = RSA::decrypt($encrypted);
```

**Use case:**
- Using the same key pair for multiple requests
- Reading keys from file or database
- Integration with external systems that require specific keys

<a id="export-keys"></a>

## Export Keys

After generating or loading keys, you can export keys for storage or sharing:

#### Export private key:

```php
RSA::encrypt('test data'); // Generate keys

$private_key = RSA::export_private();
// dd($private_key);

// Save to file
Storage::put('keys/private.pem', $private_key);
```

#### Export public key:

```php
$public_key = RSA::export_public();
// dd($public_key);

// Save to file
Storage::put('keys/public.pem', $public_key);
```

#### Export both keys at once:

```php
RSA::encrypt('test data');

$keys = [
    'private' => RSA::export_private(),
    'public' => RSA::export_public(),
];

// Save to database
DB::table('encryption_keys')->insert([
    'private_key' => $keys['private'],
    'public_key' => $keys['public'],
    'created_at' => time(),
]);
```

<a id="data-details"></a>

## Data Details

#### Encryption data details:

To see complete information about the key pair used:

```php
$details = RSA::details();

/*
Array contains:
- private_key: Private key in PEM format
- public_key: Public key in PEM format
- bits: Key size (2048 bits)
- type: Key type (RSA)
*/

echo $details['bits']; // 2048
echo $details['type']; // RSA
```

<a id="practical-examples"></a>

## Practical Examples

### 1. Encrypt Sensitive User Data

```php
// Encrypt sensitive user data
$sensitive_data = json_encode([
    'ssn' => '123-45-6789',
    'credit_card' => '4111-1111-1111-1111',
    'cvv' => '123',
]);

$encrypted = RSA::encrypt($sensitive_data);

// Save encrypted data to database
DB::table('users')->where('id', $user_id)->update([
    'sensitive_data' => base64_encode($encrypted),
]);

// Export keys for backup
$private_key = RSA::export_private();
$public_key = RSA::export_public();

Storage::put('backups/keys_' . $user_id . '.json', json_encode([
    'private' => $keys['private'],
    'public' => $keys['public'],
]));
```

### 2. Decrypt with Loaded Keys

```php
// Load keys from backup
$keys = json_decode(Storage::get('backups/keys_123.json'), true);
RSA::load_keys($keys['private'], $keys['public']);

// Get encrypted data from database
$user = DB::table('users')->where('id', 123)->first();
$encrypted = base64_decode($user->sensitive_data);

// Decrypt
$decrypted = RSA::decrypt($encrypted);
$data = json_decode($decrypted, true);

echo $data['ssn']; // 123-45-6789
```

### 3. Key Management System

```php
class KeyManager
{
    protected static $current_keys = null;

    public static function init()
    {
        // Check if there are saved keys
        $keys = DB::table('system_keys')
            ->where('active', 1)
            ->first();

        if ($keys) {
            // Load existing keys
            RSA::load_keys($keys->private_key, $keys->public_key);
            static::$current_keys = $keys;
        } else {
            // Generate new keys
            RSA::encrypt('init'); // Trigger key generation

            $keys = [
                'private_key' => RSA::export_private(),
                'public_key' => RSA::export_public(),
                'created_at' => time(),
                'active' => 1,
            ];

            DB::table('system_keys')->insert($keys);
            static::$current_keys = (object) $keys;
        }
    }

    public static function encrypt($data)
    {
        static::init();
        return RSA::encrypt($data);
    }

    public static function decrypt($encrypted)
    {
        static::init();
        return RSA::decrypt($encrypted);
    }

    public static function rotate_keys()
    {
        // Deactivate old keys
        DB::table('system_keys')->update(['active' => 0]);

        // Generate new keys
        RSA::encrypt('rotate');

        // Save new keys
        DB::table('system_keys')->insert([
            'private_key' => RSA::export_private(),
            'public_key' => RSA::export_public(),
            'created_at' => time(),
            'active' => 1,
        ]);
    }
}

// Usage
KeyManager::init();
$encrypted = KeyManager::encrypt('secret data');
$decrypted = KeyManager::decrypt($encrypted);
```

### 4. API Secure Communication

```php
// Server side - Generate and share public key
Route::get('api/public-key', function () {
    RSA::encrypt('init');

    return Response::json([
        'public_key' => RSA::export_public(),
    ]);
});

// Server side - Decrypt data from client
Route::post('api/secure-data', function () {
    $encrypted = base64_decode(Input::get('data'));

    try {
        $decrypted = RSA::decrypt($encrypted);
        $data = json_decode($decrypted, true);

        // Process data
        return Response::json(['status' => 'success']);

    } catch (\Exception $e) {
        return Response::json(['error' => 'Decryption failed'], 400);
    }
});
```

### 5. File Encryption

```php
class SecureFile
{
    public static function encrypt_file($source, $destination)
    {
        // Read file
        $content = Storage::get($source);

        // Encrypt
        $encrypted = RSA::encrypt($content);

        // Save encrypted file
        Storage::put($destination, $encrypted);

        // Save keys for this file
        return [
            'private_key' => RSA::export_private(),
            'public_key' => RSA::export_public(),
        ];
    }

    public static function decrypt_file($source, $destination, $private_key)
    {
        // Read encrypted file
        $encrypted = Storage::get($source);

        // Load key
        RSA::load_keys($private_key);

        // Decrypt
        $decrypted = RSA::decrypt($encrypted);

        // Save decrypted file
        Storage::put($destination, $decrypted);
    }
}

// Encrypt file
$keys = SecureFile::encrypt_file(
    'documents/sensitive.pdf',
    'encrypted/sensitive.enc'
);

// Store keys securely
Storage::put('keys/sensitive_key.json', json_encode($keys));

// Decrypt file later
$keys = json_decode(Storage::get('keys/sensitive_key.json'), true);
SecureFile::decrypt_file(
    'encrypted/sensitive.enc',
    'documents/decrypted.pdf',
    $keys['private_key']
);
```

### Best Practices

**1. Store keys securely:**

```php
// DO NOT save in version control
// Use environment variables or secure vault
$private_key = getenv('RSA_PRIVATE_KEY');
RSA::load_keys($private_key);
```

**2. Rotate keys periodically:**

```php
// Schedule key rotation every 90 days
if (time() - $last_rotation > 7776000) { // 90 days
    KeyManager::rotate_keys();
}
```

**3. Use appropriate padding:**

```php
// Default uses OPENSSL_PKCS1_PADDING
$encrypted = RSA::encrypt($data);

// Or specify padding type
$encrypted = RSA::encrypt($data, OPENSSL_PKCS1_OAEP_PADDING);
```

**4. Handle errors properly:**

```php
try {
    $decrypted = RSA::decrypt($encrypted);
} catch (\Exception $e) {
    Log::error('RSA Decryption failed: ' . $e->getMessage());
    return Response::error('500');
}
```

**5. Limit data size:**

```php
// RSA 2048-bit can encrypt max ~245 bytes
// For large data, encrypt AES key with RSA
// then encrypt data with AES

$aes_key = Str::random(32);
$encrypted_key = RSA::encrypt($aes_key);
$encrypted_data = Crypter::encrypt($large_data, $aes_key);

// Save both
Storage::put('data.enc', json_encode([
    'key' => base64_encode($encrypted_key),
    'data' => $encrypted_data,
]));
```
