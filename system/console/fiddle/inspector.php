<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Inspector
{
    /**
     * The fallback dumper to use when no colorizer is available.
     *
     * @var Dumper
     */
    private $fallback;

    /**
     * The colorizers to use for inspecting variables.
     *
     * @var array
     */
    private $colorizers = [];

    /**
     * The colors to use for inspecting variables.
     *
     * @var array
     */
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

    /**
     * Inspect a variable and return a formatted string.
     *
     * @param mixed $variable
     *
     * @return string
     */
    public function inspect($variable)
    {
        return preg_replace('/^/m', $this->colorize('comment', '// '), $this->dump($variable));
    }

    /**
     * Get an object's properties. Public so subclasses may override it.
     *
     * @param object $value
     *
     * @return array
     * */
    public function object_vars($value)
    {
        if ($value instanceof \System\Database\Facile\Model) {
            return $value->to_array();
        }

        if ($value instanceof \System\Collection) {
            return $value->all();
        }

        return get_object_vars($value) ?: $this->hidden_vars($value);
    }

    /**
     * Get an object's non-public properties via reflection.
     *
     * @param object $value
     *
     * @return array
     * */
    private function hidden_vars($value)
    {
        $vars = [];
        $class = new \ReflectionObject($value);

        do {
            foreach ($class->getProperties() as $property) {
                if ($property->isStatic() || isset($vars[$property->getName()])) {
                    continue;
                }

                $property->setAccessible(true);
                $vars[$property->getName()] = $property->getValue($value);
            }
        } while ($class = $class->getParentClass());

        return $vars;
    }

    /**
     * Dump a value and return a formatted string.
     *
     * @param mixed $value
     *
     * @return string
     */
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

    /**
     * Format a null value.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function type_null($value)
    {
        return $this->colorize('keyword', 'NULL');
    }

    /**
     * Format a string value.
     *
     * @param string $value
     *
     * @return string
     */
    private function type_string($value)
    {
        return $this->colorize('string', var_export($value, true));
    }

    /**
     * Format a boolean value.
     *
     * @param bool $value
     *
     * @return string
     */
    private function type_bool($value)
    {
        return $this->colorize('bool', var_export($value, true));
    }

    /**
     * Format an integer value.
     *
     * @param int $value
     *
     * @return string
     */
    private function type_int($value)
    {
        return $this->colorize('integer', var_export($value, true));
    }

    /**
     * Format a float value.
     *
     * @param float $value
     *
     * @return string
     */
    private function type_float($value)
    {
        return $this->colorize('float', var_export($value, true));
    }

    /**
     * Format an array value.
     *
     * @param array $value
     *
     * @return string
     */
    private function type_array($value)
    {
        return $this->type_structure('array', $value);
    }

    /**
     * Format an object value.
     *
     * @param object $value
     *
     * @return string
     */
    private function type_object($value)
    {
        return $this->type_structure($this->label($value), $this->object_vars($value));
    }

    /**
     * Get the display label for an object.
     *
     * @param object $value
     *
     * @return string
     */
    private function label($value)
    {
        if ($value instanceof \System\Collection) {
            return sprintf('%s(%d)', get_class($value), count($value));
        }

        return sprintf('object(%s)', get_class($value));
    }

    /**
     * Format a caught exception as a single line.
     *
     * @param \Throwable|\Exception $ex
     *
     * @return string
     */
    public function inspect_error($ex)
    {
        $message = sprintf('%s: %s', get_class($ex), $ex->getMessage());

        if (strpos($ex->getFile(), "eval()'d code") === false) {
            $message .= sprintf(' in %s:%s', $ex->getFile(), $ex->getLine());
        }

        return $this->colorize('error', $message);
    }

    /**
     * Format a structured value.
     *
     * @param string $type
     * @param mixed  $value
     *
     * @return string
     */
    private function type_structure($type, $value)
    {
        return $this->stringify($this->ast($type, $value));
    }

    /**
     * Generate an AST for a value.
     *
     * @param string $type
     * @param mixed  $value
     * @param array  $seen
     *
     * @return array|string
     */
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
            'children' => empty($vars)
                ? []
                : array_combine(
                    array_map(function ($key) use ($self) {
                        return is_int($key) ? (string) $key : $self->dump($key);
                    }, array_keys($vars)),
                    array_map(function ($v) use ($self, $next) {
                        if (is_object($v)) {
                            return $self->ast($self->label($v), $v, $next);
                        }

                        if (is_array($v)) {
                            return $self->ast('array', $v, $next);
                        }

                        return $self->dump($v);
                },
                array_values($vars))
            ),
        ];
    }

    /**
     * Stringify an AST node.
     *
     * @param array $node
     * @param int   $indent
     *
     * @return string
     */
    public function stringify($node, $indent = 0)
    {
        $children = $node['children'];
        $self = $this;

        return implode("\n", [
            sprintf('%s(', $node['name']),
            implode(",\n", array_map(function ($k) use ($self, $children, $indent) {
                if (is_array($children[$k])) {
                    return sprintf(
                        '%s%s => %s',
                        str_repeat(' ', ($indent + 1) * 2),
                        $k,
                        $self->stringify($children[$k], $indent + 1)
                    );
                }

                return sprintf('%s%s => %s', str_repeat(' ', ($indent + 1) * 2), $k, $children[$k]);
            }, array_keys($children))),
            sprintf('%s)', str_repeat(' ', $indent * 2)),
        ]);
    }

    /**
     * Get the color mappings.
     *
     * @return array
     */
    private function colors()
    {
        return [
            'integer' => 'light_green',
            'float' => 'light_yellow',
            'string' => 'light_red',
            'bool' => 'light_purple',
            'keyword' => 'light_cyan',
            'comment' => 'dark_grey',
            'error' => 'light_red',
            'default' => 'none',
        ];
    }

    /**
     * Colorize a value.
     *
     * @param string $type
     * @param string $value
     *
     * @return string
     */
    private function colorize($type, $value)
    {
        $name = empty($this->colorizers[$type])
            ? $this->colorizers['default']
            : $this->colorizers[$type];

        return sprintf("%s%s\033[0m", static::$colors[$name], $value);
    }

    /**
     * Check if a value has been seen before.
     *
     * @param mixed $value
     * @param array $seen
     *
     * @return bool
     */
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
