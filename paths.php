<?php

defined('DS') or exit('No direct script access.');

// --------------------------------------------------------------
// Path ke direktori paket default.
// --------------------------------------------------------------
$paths['app'] = 'application';

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
$GLOBALS['rakit_paths']['base'] = __DIR__.DS;

// --------------------------------------------------------------
// Defininisikan konstanta lain yang belum ada.
// --------------------------------------------------------------
foreach ($paths as $name => $path) {
    if (! isset($GLOBALS['rakit_paths'][$name])) {
        $GLOBALS['rakit_paths'][$name] = realpath($path).DS;
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
