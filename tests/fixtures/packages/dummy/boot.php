<?php

defined('DS') or exit('No direct access.');

$_SERVER['package.dummy.boot'] = isset($_SERVER['package.dummy.boot'])
    ? $_SERVER['package.dummy.boot'] + 1
    : 1;
