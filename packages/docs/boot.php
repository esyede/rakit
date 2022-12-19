<?php

defined('DS') or exit('No direct script access.');

use System\Autoloader;

/*
|--------------------------------------------------------------------------
| Autoload
|--------------------------------------------------------------------------
|
*/

Autoloader::namespaces([
    'Docs\Libraries' => __DIR__ . '/libraries',
]);
