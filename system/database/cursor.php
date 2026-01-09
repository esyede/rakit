<?php

defined('DS') or exit('No direct access.');

/**
 * Generator implementation untuk Query::cursor()
 * File ini hanya di-load di PHP 5.5.0+ yang mendukung keyword yield.
 * Return anonymous generator function.
 */

return call_user_func(function () use ($columns, $chunk_size) {
    if (is_null($this->selects)) {
        $this->select($columns);
    }

    $page = 1;

    do {
        // Clone query untuk setiap chunk agar tidak mengubah query asli
        $clone = clone $this;
        $clone->limit = $chunk_size;
        $clone->offset = ($page - 1) * $chunk_size;

        $sql = $clone->grammar->select($clone);
        $results = $clone->connection->query($sql, $clone->bindings);
        $count = count($results);

        // Yield setiap record satu per satu
        foreach ($results as $result) {
            yield $result;
        }

        $page++;
    } while ($count === $chunk_size);
});
