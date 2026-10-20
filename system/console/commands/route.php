<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Request;
use System\Routing\Router;

class Route extends Command
{
    /**
     * Call the route and dump the result.
     * Pass --with-middleware to run the route's before/after middleware too.
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

        dd($this->dispatch($arguments[0], $arguments[1], has_cli_flag('with-middleware')).PHP_EOL);
    }

    /**
     * Dispatch the route and return its output as a string.
     *
     * @param string $method
     * @param string $uri
     * @param bool   $with_middleware
     *
     * @return string
     */
    public function dispatch($method, $uri, $with_middleware = false)
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper((string) $method);
        $_SERVER['REQUEST_URI'] = $uri;
        Request::foundation()->setMethod($_SERVER['REQUEST_METHOD']);

        $route = Router::route(Request::method(), $_SERVER['REQUEST_URI']);

        if (is_null($route)) {
            return '404: Not Found';
        }

        if (! $with_middleware) {
            return (string) $route->response();
        }

        Request::$route = $route;
        $response = $route->call();

        return 'HTTP '.$response->status().PHP_EOL.$response->render();
    }

    /**
     * Dispatch 'route:list' to lists() below (for php 5.4 compat).
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ('list' === strtolower($method)) {
            return call_user_func_array([$this, 'lists'], $parameters);
        }

        throw new \Exception(sprintf('Command method is not callable: %s', $method));
    }

    /**
     * List every registered route.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function lists(array $arguments = [])
    {
        $grouped = Router::routes();

        // Flatten routes for easier display
        $routes = [];

        foreach ($grouped as $method => $uris) {
            foreach ($uris as $uri => $action) {
                $routes[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'action' => $action,
                    'name' => isset($action['as']) ? $action['as'] : '',
                ];
            }
        }

        usort($routes, function ($a, $b) {
            return strcmp($a['uri'], $b['uri']);
        });

        echo PHP_EOL;
        echo $this->info(
            str_pad('Method', 8).' | '.str_pad('URI', 40).' | '.str_pad('Action', 30).' | '.'Name'
        );
        echo $this->info(
            str_repeat('-', 8).'-+-'.str_repeat('-', 40).'-+-'.str_repeat('-', 30).'-+-'.str_repeat('-', 20)
        );

        $lists = '';

        foreach ($routes as $route) {
            $method = str_pad($route['method'], 8);
            $uri = str_pad($route['uri'], 40);
            $action = (is_array($route['action']) && isset($route['action']['uses']))
                ? $route['action']['uses']
                : (is_string($route['action']) ? $route['action'] : 'Closure');
            $action = str_pad($action, 30);
            $name = $route['name'];
            $lists .= $method.' | '.$uri.' | '.$action.' | '.($name ?: 'N/A').PHP_EOL;
        }

        echo $this->info($lists);
    }
}
