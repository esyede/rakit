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
     * Buat resource route baru.
     *
     * @param string $name
     * @param array  $template
     */
    public function __construct($name, array $options = [])
    {
        $this->parent = '';
        $prefix = '';

        if (!empty($options)) {
            $this->options = $options;
        }

        $clauses = explode('.', $name);

        if (isset($clauses[1]) && !empty($clauses[1])) {
            $this->parent = $clauses[0];
            $prefix = $this->parent . '/(:any?)/';
        }

        $this->name = (isset($clauses[1]) && !empty($clauses[1])) ? $clauses[1] : $name;

        foreach ($this->options as $option) {
            $method = Str::lower($option['method']);

            if (!in_array($method, $this->methods)) {
                throw new \Exception(sprintf('Invalid request method specified: %s', $method));
            }

            $this->name = str_replace('::', '/', $this->name);
            $options = $this->options($option);

            Route::{$method}($prefix . $this->name . $option['route'], $options);
        }
    }

    /**
     * Buat resource route baru (static).
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
     * Proses opsi-opsi pada route.
     *
     * @param array $options
     *
     * @return array
     */
    protected function options(array $options)
    {
        $results = [];

        if (isset($options['as']) && !empty($options['as'])) {
            $prefix = $this->parent ? $this->parent . '.' : '';
            $results['as'] = $prefix . $this->placeholder($options['as']);
        }

        if (isset($options['uses']) && !empty($options['uses'])) {
            $prefix = $this->parent ? $this->parent . '.' : '';
            $results['uses'] = $prefix . $this->placeholder($options['uses']);
        }

        return $results;
    }

    /**
     * Replace placeholder pada route options.
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
