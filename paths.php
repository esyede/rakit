<?php

defined('DS') or exit('No direct access.');

// --------------------------------------------------------------
// Define special character constants.
// --------------------------------------------------------------
define('CRLF', "\r\n");
define('TAB', "\t");
define('CR', "\r");
define('LF', "\n");

/*
|----------------------------------------------------------------
| Application Environments
|----------------------------------------------------------------
|
| Rakit uses a simple approach to environment.
| Just define which URL belongs to which environment, when the
| application is accessed from a URL that matches the pattern,
| the content of the environment config file will be overwritten.
|
*/

$environments = [
    'local' => ['http://localhost*', 'http://127.0.0.1*', '*.test'],

    // Add another environment here if needed..
];

// --------------------------------------------------------------
// Path to default package directory.
// --------------------------------------------------------------
$paths = ['app' => 'application'];

// --------------------------------------------------------------
// Path to the application key file.
// --------------------------------------------------------------
$paths['rakit_key'] = 'key.php';

// --------------------------------------------------------------
// Path to the system directory.
// --------------------------------------------------------------
$paths['system'] = 'system';

// --------------------------------------------------------------
// Path to the packages directory.
// --------------------------------------------------------------
$paths['package'] = 'packages';

// --------------------------------------------------------------
// Path to the storage directory.
// --------------------------------------------------------------
$paths['storage'] = 'storage';

// --------------------------------------------------------------
// Path to the assets directory.
// --------------------------------------------------------------
$paths['assets'] = 'assets';

// --------------------------------------------------------------
// Change working directory to root directory.
// --------------------------------------------------------------
chdir(__DIR__);

// --------------------------------------------------------------
// Define path to base directory.
// --------------------------------------------------------------
$GLOBALS['rakit_paths']['base'] = __DIR__ . DS;

// --------------------------------------------------------------
// Define path to system directory.
// --------------------------------------------------------------
foreach ($paths as $name => $path) {
    if (!isset($GLOBALS['rakit_paths'][$name])) {
        $GLOBALS['rakit_paths'][$name] = ('rakit_key' === $name)
            ? $GLOBALS['rakit_paths']['base'] . $path
            : realpath($path) . DS;
    }
}

/**
 * Global function for accessing path.
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
 * Global function for setting path.
 *
 * @param string $path
 * @param string $value
 */
function set_path($path, $value)
{
    $GLOBALS['rakit_paths'][$path] = $value;
}

// --------------------------------------------------------------
// Polyfill for Throwable interface (PHP < 7.0).
// --------------------------------------------------------------

if (PHP_VERSION_ID < 70000) {
    interface Throwable
    {
        public function getMessage();
        public function getCode();
        public function getFile();
        public function getLine();
        public function getTrace();
        public function getTraceAsString();
        public function getPrevious();
        public function __toString();
    }
}

// --------------------------------------------------------------
// Polyfill for Attribute interface (PHP < 8.0).
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
        const IS_REPEATABLE = 64;
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
