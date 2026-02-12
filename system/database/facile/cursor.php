<?php

defined('DS') or exit('No direct access.');

/**
 * Generator implementation for Query::cursor()
 * This file is only loaded in PHP 5.5.0+ which supports the yield keyword.
 * Returns an anonymous generator function.
 */

return call_user_func(function () use ($columns, $chunk_size) {
    $page = 1;

    do {
        // Clone the query to avoid modifying the original instance.
        $clone = clone $this;
        $results = $clone->table->take($chunk_size)->skip(($page - 1) * $chunk_size)->get($columns);
        $count = count($results);

        // Hydrate and yield every result in the current chunk.
        foreach ($this->hydrate($this->model, $results) as $model) {
            yield $model;
        }

        $page++;
    } while ($count === $chunk_size);
});
