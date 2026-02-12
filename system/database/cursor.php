<?php

defined('DS') or exit('No direct access.');

/**
 * Generator implementation for Query::cursor()
 * This file is only loaded in PHP 5.5.0+ which supports the yield keyword.
 * Returns an anonymous generator function.
 */

return call_user_func(function () use ($columns, $chunk_size) {
    if (is_null($this->selects)) {
        $this->select($columns);
    }

    $page = 1;

    do {
        // Clone the query to avoid modifying the original instance.
        $clone = clone $this;
        $clone->limit = $chunk_size;
        $clone->offset = ($page - 1) * $chunk_size;

        $sql = $clone->grammar->select($clone);
        $results = $clone->connection->query($sql, $clone->bindings);
        $count = count($results);

        // Yield every result in the current chunk.
        foreach ($results as $result) {
            yield $result;
        }

        $page++;
    } while ($count === $chunk_size);
});
