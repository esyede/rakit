<?php

defined('DS') or exit('No direct access.');

if (! function_exists('help')) {
    function help()
    {
        echo 'Fiddle commands:'.PHP_EOL;
        echo '  help()        list these commands'.PHP_EOL;
        echo '  models()      list the application models'.PHP_EOL;
        echo '  db()          last executed query'.PHP_EOL;
        echo '  table($rows)  render a collection/array as a table'.PHP_EOL;
        echo '  dump($value)  dump a value without the comment prefix'.PHP_EOL;
        echo PHP_EOL.'Leave the console with: exit; / quit; / Ctrl+D'.PHP_EOL;
    }
}

if (! function_exists('models')) {
    function models()
    {
        $files = glob(path('app').'models'.DS.'*.php');
        $models = [];

        foreach ($files as $file) {
            $models[] = ucfirst(basename($file, '.php'));
        }

        sort($models);

        return $models;
    }
}

if (! function_exists('db')) {
    function db()
    {
        return \System\Database::last_query();
    }
}

if (! function_exists('table')) {
    function table($rows)
    {
        $rows = ($rows instanceof \System\Collection) ? $rows->all() : (array) $rows;
        $table = new \System\Console\Table();

        foreach ($rows as $row) {
            $row = is_object($row) && method_exists($row, 'to_array') ? $row->to_array() : (array) $row;

            if (! $table->get_headers()) {
                $table->set_headers(array_values(array_keys($row)));
            }

            $table->add_row(array_values(array_map(function ($value) {
                return is_scalar($value) || is_null($value) ? $value : json_encode($value);
            }, $row)));
        }

        if ($table->get_headers()) {
            $table->display();
        }
    }
}
