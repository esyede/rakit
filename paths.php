<?php

defined('DS') or exit('No direct access.');

/*
|----------------------------------------------------------------
| Environment aplikasi
|----------------------------------------------------------------
|
| Rakit takes a dead simple approach to environments, and we
| think you'll love it. Just specify which URLs belong to a
| given environment, and when you access your application
| from a URL matching that pattern, we'll be sure to
| merge in that environment's configuration files.
|
*/

$environments = [
    'local' => ['http://localhost*', 'http://127.0.0.1*', '*.test'],
    // ..
];

// --------------------------------------------------------------
// Path ke direktori paket default.
// --------------------------------------------------------------
$paths = ['app' => 'application'];

// --------------------------------------------------------------
// Path ke file key.
// --------------------------------------------------------------
$paths['rakit_key'] = 'key.php';

// --------------------------------------------------------------
// Path ke direktori sistem.
// --------------------------------------------------------------
$paths['system'] = 'system';

// --------------------------------------------------------------
// Path ke direktori utama paket.
// --------------------------------------------------------------
$paths['package'] = 'packages';

// --------------------------------------------------------------
// Path ke direktori storage.
// --------------------------------------------------------------
$paths['storage'] = 'storage';

// --------------------------------------------------------------
// Path ke direktori aset.
// --------------------------------------------------------------
$paths['assets'] = 'assets';

// --------------------------------------------------------------
// Ubah direktori kerja ke direktori root.
// --------------------------------------------------------------
chdir(__DIR__);

// --------------------------------------------------------------
// Definisikan path ke base direktori.
// --------------------------------------------------------------
$GLOBALS['rakit_paths']['base'] = __DIR__ . DS;

// --------------------------------------------------------------
// Defininisikan konstanta lain yang belum ada.
// --------------------------------------------------------------
foreach ($paths as $name => $path) {
    if (!isset($GLOBALS['rakit_paths'][$name])) {
        $GLOBALS['rakit_paths'][$name] = ('rakit_key' === $name)
            ? $GLOBALS['rakit_paths']['base'] . $path
            : realpath($path) . DS;
    }
}

/**
 * Fungsi global untuk akses path.
 *
 * <code>
 *
 *     $storage = path('storage');
 *
 * </code>
 *
 * @param string $path
 *
 * @return string
 */
function path($path)
{
    return $GLOBALS['rakit_paths'][$path];
}

/**
 * Fungsi global untuk setting path.
 *
 * @param string $path
 * @param string $value
 */
function set_path($path, $value)
{
    $GLOBALS['rakit_paths'][$path] = $value;
}

// --------------------------------------------------------------
// Polyfill untuk atribut #[\ReturnTypeWillChange].
// --------------------------------------------------------------

if (PHP_VERSION_ID < 80000) {
    final class Attribute
    {
        const TARGET_CLASS = 1;
        const TARGET_FUNCTION = 2;
        const TARGET_METHOD = 4;
        const TARGET_PROPERTY = 8;
        const TARGET_CLASS_CONSTANT = 16;
        const TARGET_PARAMETER = 32;
        const TARGET_ALL = 63;
    }
}

if (PHP_VERSION_ID < 80100) {
    #[Attribute(Attribute::TARGET_METHOD)]
    final class ReturnTypeWillChange
    {
        public function __construct()
        {
            // ..
        }
    }
}
