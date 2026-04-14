<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Inspector
{
    private $fallback;
    private $colorizers = [];

    private static $colors = [
        'black' => "\033[0;30m",
        'white' => "\033[1;37m",
        'none' => "\033[1;30m",
        'dark_grey' => "\033[1;30m",
        'light_grey' => "\033[0;37m",
        'dark_red' => "\033[0;31m",
        'light_red' => "\033[1;31m",
        'dark_green' => "\033[0;32m",
        'light_green' => "\033[1;32m",
        'dark_yellow' => "\033[0;33m",
        'light_yellow' => "\033[1;33m",
        'dark_blue' => "\033[0;34m",
        'light_blue' => "\033[1;34m",
        'dark_purple' => "\033[0;35m",
        'light_purple' => "\033[1;35m",
        'dark_cyan' => "\033[0;36m",
        'light_cyan' => "\033[1;36m",
    ];

    /**
     * Initialize a new Inspector.
     */
    public function __construct()
    {
        $this->fallback = new Dumper();
        $this->colorizers = $this->colors();
    }

    public function inspect($variable)
    {
        return preg_replace('/^/m', $this->colorize('comment', '// '), $this->dump($variable));
    }

    /**
     * Returns an associative array of an object's properties.
     * This method is public so that subclasses may override it.
     *
     * @param object $value
     *
     * @return array
     * */
    public function object_vars($value)
    {
        return get_object_vars($value);
    }

    public function dump($value)
    {
        $tests = [
            'is_null' => 'type_null',
            'is_string' => 'type_string',
            'is_bool' => 'type_bool',
            'is_integer' => 'type_int',
            'is_float' => 'type_float',
            'is_array' => 'type_array',
            'is_object' => 'type_object',
        ];

        foreach ($tests as $predicate => $method) {
            if (call_user_func($predicate, $value)) {
                return call_user_func([$this, $method], $value);
            }
        }

        return $this->fallback->inspect($value);
    }

    private function type_null($value)
    {
        return $this->colorize('keyword', 'NULL');
    }

    private function type_string($value)
    {
        return $this->colorize('string', var_export($value, true));
    }

    private function type_bool($value)
    {
        return $this->colorize('bool', var_export($value, true));
    }

    private function type_int($value)
    {
        return $this->colorize('integer', var_export($value, true));
    }

    private function type_float($value)
    {
        return $this->colorize('float', var_export($value, true));
    }

    private function type_array($value)
    {
        return $this->type_structure('array', $value);
    }

    private function type_object($value)
    {
        return $this->type_structure(sprintf('object(%s)', get_class($value)), $this->object_vars($value));
    }

    private function type_structure($type, $value)
    {
        return $this->stringify($this->ast($type, $value));
    }

    public function ast($type, $value, array $seen = [])
    {
        // FIXME: Improve this AST so it doesn't require access to dump() or colorize()
        if ($this->seen($value, $seen)) {
            return $this->colorize('default', '*** RECURSION ***');
        }

        $next = array_merge($seen, [$value]);
        $vars = is_object($value) ? $this->object_vars($value) : $value;
        $self = $this;

        return [
            'name' => $this->colorize('keyword', $type),
            'children' => empty($vars) ? [] : array_combine(array_map([$self, 'dump'], array_keys($vars)), array_map(function ($v) use ($self, $next) {
                if (is_object($v)) {
                    return $self->ast(sprintf('object(%s)', get_class($v)), $v, $next);
                }

                if (is_array($v)) {
                    return $self->ast('array', $v, $next);
                }

                return $self->dump($v);
            }, array_values($vars)))
        ];
    }

    public function stringify($node, $indent = 0)
    {
        $children = $node['children'];
        $self = $this;

        return implode("\n", [
            sprintf('%s(', $node['name']),
            implode(",\n", array_map(function ($k) use ($self, $children, $indent) {
                if (is_array($children[$k])) {
                    return sprintf('%s%s => %s', str_repeat(' ', ($indent + 1) * 2), $k, $self->stringify($children[$k], $indent + 1));
                } else {
                    return sprintf('%s%s => %s', str_repeat(' ', ($indent + 1) * 2), $k, $children[$k]);
                }
            }, array_keys($children))),
            sprintf('%s)', str_repeat(' ', $indent * 2))
        ]);
    }

    private function colors()
    {
        return [
            'integer' => 'light_green',
            'float' => 'light_yellow',
            'string' => 'light_red',
            'bool' => 'light_purple',
            'keyword' => 'light_cyan',
            'comment' => 'dark_grey',
            'default' => 'none',
        ];
    }

    private function colorize($type, $value)
    {
        $name = empty($this->colorizers[$type]) ? $this->colorizers['default'] : $this->colorizers[$type];
        return sprintf("%s%s\033[0m", static::$colors[$name], $value);
    }

    private function seen($value, $seen)
    {
        foreach ($seen as $v) {
            if ($v === $value) {
                return true;
            }
        }

        return false;
    }
}
