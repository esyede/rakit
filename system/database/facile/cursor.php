<?php

defined('DS') or exit('No direct access.');

/*
 * Generator for Query::cursor(), loaded only on PHP 5.5.0+ where yield exists.
 */

return call_user_func(function () use ($columns, $chunk_size) {
    $page = 1;

    do {
        $clone = clone $this;
        $results = $clone->table->take($chunk_size)->skip(($page - 1) * $chunk_size)->get($columns);
        $count = count($results);

        foreach ($this->hydrate($this->model, $results) as $model) {
            yield $model;
        }

        $page++;
    } while ($count === $chunk_size);
});
