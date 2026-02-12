<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Request;
use System\Routing\Router;

class Route extends Command
{
    /**
     * Call the route and dump the result.
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

    public function list()
    {
        $routes = Router::routes();

        // Flatten routes for easier display
        $routes = [];
        foreach ($routes as $method => $uris) {
            foreach ($uris as $uri => $action) {
                $routes[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'action' => $action,
                    'name' => isset($action['as']) ? $action['as'] : '',
                ];
            }
        }

        // Sort by URI
        usort($routes, function ($a, $b) {
            return strcmp($a['uri'], $b['uri']);
        });

        // Display header
        echo PHP_EOL;
        echo $this->info(str_pad('Method', 8) . ' | ' . str_pad('URI', 40) . ' | ' . str_pad('Action', 30) . ' | ' . 'Name');
        echo $this->info(str_repeat('-', 8) . '-+-' . str_repeat('-', 40) . '-+-' . str_repeat('-', 30) . '-+-' . str_repeat('-', 20));
        $lists = '';

        // Display routes
        foreach ($routes as $route) {
            $method = str_pad($route['method'], 8);
            $uri = str_pad($route['uri'], 40);
            $action = is_array($route['action']) && isset($route['action']['uses'])
                ? $route['action']['uses']
                : (is_string($route['action']) ? $route['action'] : 'Closure');
            $action = str_pad($action, 30);
            $name = $route['name'];

            $lists .= $method . ' | ' . $uri . ' | ' . $action . ' | ' . ($name ?: 'N/A') . PHP_EOL;
        }

        echo $this->info($lists);
    }
}
