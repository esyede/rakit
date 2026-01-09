<?php

defined('DS') or exit('No direct access.');

/**
 * Generator implementation untuk Facile\Query::cursor()
 * File ini hanya di-load di PHP 5.5.0+ yang mendukung keyword yield.
 * Return anonymous generator function.
 */

return call_user_func(function () use ($columns, $chunk_size) {
    $page = 1;

    do {
        // Ambil chunk data dari database
        $clone = clone $this;
        $results = $clone->table->take($chunk_size)->skip(($page - 1) * $chunk_size)->get($columns);
        $count = count($results);

        // Hydrate dan yield setiap model satu per satu
        foreach ($this->hydrate($this->model, $results) as $model) {
            yield $model;
        }

        $page++;
    } while ($count === $chunk_size);
});
