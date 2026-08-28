<?php

namespace System\Routing;

defined('DS') or exit('No direct access.');

use System\Str;

class Resource
{
    protected $name;

    protected $parent;

    protected $methods = ['get', 'post', 'put', 'delete'];

    protected $only = [];

    protected $except = [];

    /**
     * Contains the controller handling the resource, when it is not the
     * resource name itself.
     *
     * @var string|null
     */
    protected $controller;

    protected $options = [
        [
            'method' => 'get',
            'route' => '',
            'as' => ':name.index',
            'uses' => ':name@index',
        ],
        [
            'method' => 'get',
            'route' => '/create',
            'as' => ':name.create',
            'uses' => ':name@create',
        ],
        [
            'method' => 'post',
            'route' => '',
            'as' => ':name.store',
            'uses' => ':name@store',
        ],
        [
            'method' => 'get',
            'route' => '/(:any)',
            'as' => ':name.show',
            'uses' => ':name@show',
        ],
        [
            'method' => 'get',
            'route' => '/(:any)/edit',
            'as' => ':name.edit',
            'uses' => ':name@edit',
        ],
        [
            'method' => 'put',
            'route' => '/(:any)',
            'as' => ':name.update',
            'uses' => ':name@update',
        ],
        [
            'method' => 'delete',
            'route' => '/(:any)',
            'as' => ':name.delete',
            'uses' => ':name@delete',
        ],
    ];

    /**
     * Constructor.
     *
     * @param string $name
     * @param array  $options
     */
    public function __construct($name, array $options = [])
    {
        $this->parent = '';
        $prefix = '';

        $this->only = isset($options['only']) ? (array) $options['only'] : [];
        $this->except = isset($options['except']) ? (array) $options['except'] : [];
        $this->controller = isset($options['controller']) ? (string) $options['controller'] : null;

        // Anything left is a route table of its own, replacing the default one.
        $custom = array_diff_key($options, ['only' => 0, 'except' => 0, 'controller' => 0]);

        if (! empty($custom)) {
            $this->options = $custom;
        }

        $clauses = explode('.', $name);

        if (isset($clauses[1]) && ! empty($clauses[1])) {
            $this->parent = $clauses[0];
            $prefix = $this->parent.'/(:any?)/';
        }

        $this->name = (isset($clauses[1]) && ! empty($clauses[1])) ? $clauses[1] : $name;

        foreach ($this->options as $option) {
            if (! isset($option['method'])) {
                throw new \Exception('Each resource route needs a "method".');
            }

            $method = Str::lower($option['method']);

            if (! in_array($method, $this->methods)) {
                throw new \Exception(sprintf('Invalid request method specified: %s', $method));
            }

            if (! $this->wanted($option)) {
                continue;
            }

            $this->name = str_replace('::', '/', $this->name);
            $options = $this->options($option);

            Route::{$method}($prefix.$this->name.$option['route'], $options);
        }
    }

    /**
     * Create a new resource instance.
     *
     * @param string $name
     * @param array  $options
     *
     * @return void
     */
    public static function make($name, array $options = [])
    {
        return new static($name, $options);
    }

    /**
     * Parse route options.
     *
     * @param array $options
     *
     * @return array
     */
    /**
     * Check whether a route survives the 'only' and 'except' options.
     *
     * @param array $option
     *
     * @return bool
     */
    protected function wanted(array $option)
    {
        $action = $this->action($option);

        if (is_null($action)) {
            return true;
        }

        if (count($this->only) > 0 && ! in_array($action, $this->only)) {
            return false;
        }

        return ! (count($this->except) > 0 && in_array($action, $this->except));
    }

    /**
     * Get the controller action a route points at, ':name@index' -> 'index'.
     *
     * @param array $option
     *
     * @return string|null
     */
    protected function action(array $option)
    {
        if (! isset($option['uses']) || false === strpos((string) $option['uses'], '@')) {
            return null;
        }

        return substr((string) $option['uses'], strpos((string) $option['uses'], '@') + 1);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function options(array $options)
    {
        $results = [];

        if (isset($options['as']) && ! empty($options['as'])) {
            $prefix = $this->parent ? $this->parent.'.' : '';
            $results['as'] = $prefix.$this->placeholder($options['as']);
        }

        if (isset($options['uses']) && ! empty($options['uses'])) {
            $prefix = $this->parent ? $this->parent.'.' : '';
            $uses = $this->placeholder($options['uses']);

            // A 'controller' option points the routes somewhere other than the
            // controller the resource is named after.
            if (! is_null($this->controller)) {
                $uses = $this->controller.substr($uses, strpos($uses, '@'));
                $prefix = '';
            }

            $results['uses'] = $prefix.$uses;
        }

        return $results;
    }

    /**
     * Replace :name placeholder in a string.
     *
     * @param string $placeholder
     *
     * @return string
     */
    protected function placeholder($placeholder)
    {
        return str_replace(':name', $this->name, $placeholder);
    }
}
