<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Request;
use System\Routing\Router;

class Route extends Command
{
    /**
     * Jalankan rute dan dump hasilnya.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function call(array $arguments = [])
    {
        if (2 !== count($arguments)) {
            throw new \Exception('Please specify a request method and URI.');
        }

        $_SERVER['REQUEST_METHOD'] = strtoupper((string) $arguments[0]);
        $_SERVER['REQUEST_URI'] = $arguments[1];

        $route = Router::route(Request::method(), $_SERVER['REQUEST_URI']);
        $route = is_null($route) ? '404: Not Found' : $route->response();

        dd($route . PHP_EOL);
    }
}
