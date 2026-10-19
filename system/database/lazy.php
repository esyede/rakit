<?php

defined('DS') or exit('No direct access.');

return call_user_func(function () use ($fetch, $size) {
    $page = 0;
    $rows = [];

    do {
        $rows = call_user_func($fetch, $page++, $rows);

        foreach ($rows as $row) {
            yield $row;
        }
    } while (count($rows) === $size);
});
