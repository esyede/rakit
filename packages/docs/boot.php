<?php

defined('DS') or exit('No direct access.');

use System\Autoloader;

/*
|--------------------------------------------------------------------------
| Autoload
|--------------------------------------------------------------------------
|
*/

Autoloader::map([
    'Docs\Libraries\Docs' => __DIR__ . '/libraries/docs.php',
]);
