<?php

defined('DS') or exit('No direct access.');

// Note: initialised here rather than relying on a test having done it, so
// booting this package on its own does not warn about an undefined key.
$_SERVER['package.dummy.boot'] = isset($_SERVER['package.dummy.boot'])
    ? $_SERVER['package.dummy.boot'] + 1
    : 1;
