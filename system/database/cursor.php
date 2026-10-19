<?php

defined('DS') or exit('No direct access.');

return call_user_func(function () use ($run, $map) {
    $statement = call_user_func($run);

    while (false !== ($row = $statement->fetch())) {
        $row = $map ? call_user_func($map, $row) : $row;
        yield $row;
    }
});
