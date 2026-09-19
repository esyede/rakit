<?php

namespace System\Blade;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Html;
use System\View;
use System\Package;

class Component
{
    /**
     * Contains the components being rendered, innermost last.
     *
     * @var array
     */
    protected static $stack = [];

    /**
     * Contains the classes registered for a component name.
     *
     * @var array
     */
    protected static $registrar = [];

    /**
     * Contains the attributes the tag was given.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Register a component class for a name outside the <name>_Component convention.
     *
     * @param string $name
     * @param string $class
     *
     * @return void
     */
    public static function register($name, $class)
    {
        static::$registrar[$name] = $class;
    }

    /**
     * Start a component. Whatever is printed until it closes is its slot.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return void
     */
    public static function open($name, array $attributes = [])
    {
        static::$stack[] = [
            'name' => $name,
            'attributes' => $attributes,
            'slots' => [],
            'slot' => null,
            'level' => ob_get_level(),
        ];

        ob_start();
    }

    /**
     * Start a slot of the component being built.
     *
     * @param string $name
     *
     * @return void
     */
    public static function slot($name)
    {
        if (empty(static::$stack)) {
            throw new \Exception(sprintf('Slot outside of a component: %s', $name));
        }

        $index = count(static::$stack) - 1;
        static::$stack[$index]['slot'] = $name;

        ob_start();
    }

    /**
     * End the slot that is being filled.
     *
     * @return void
     */
    public static function end_slot()
    {
        if (empty(static::$stack)) {
            throw new \Exception('Slot ended outside of a component.');
        }

        $index = count(static::$stack) - 1;
        $name = static::$stack[$index]['slot'];

        if (is_null($name)) {
            throw new \Exception('Slot ended without being started.');
        }

        static::$stack[$index]['slots'][$name] = ob_get_clean();
        static::$stack[$index]['slot'] = null;
    }

    /**
     * Close the component and get what it renders to.
     *
     * @return string
     */
    public static function close()
    {
        if (empty(static::$stack)) {
            throw new \Exception('Component closed without being opened.');
        }

        $index = count(static::$stack) - 1;

        if (! is_null(static::$stack[$index]['slot'])) {
            throw new \Exception(sprintf(
                'Component closed with the slot still open: %s',
                static::$stack[$index]['slot']
            ));
        }

        static::$stack[$index]['slots']['slot'] = ob_get_clean();

        try {
            $output = static::render_component();
        } catch (\Throwable $e) {
            array_pop(static::$stack);
            throw $e;
        } catch (\Exception $e) {
            array_pop(static::$stack);
            throw $e;
        }

        array_pop(static::$stack);

        return $output;
    }

    /**
     * Close what a component left open when its view threw, or its buffers would
     * swallow everything printed after it.
     *
     * @return void
     */
    public static function unwind()
    {
        while (! empty(static::$stack)) {
            $component = array_pop(static::$stack);

            while (ob_get_level() > $component['level']) {
                ob_end_clean();
            }
        }
    }

    /**
     * Get the props of the component being rendered, and the attributes left over.
     *
     * @param array $props
     *
     * @return array
     */
    public static function props(array $props = [])
    {
        $current = static::current();
        $attributes = $current ? $current['attributes'] : [];
        $names = [];
        $variables = [];

        foreach ($props as $key => $value) {
            $name = is_int($key) ? $value : $key;
            $default = is_int($key) ? null : $value;
            $names[] = $name;
            $variables[static::variable($name)] = array_key_exists($name, $attributes)
                ? $attributes[$name]
                : $default;
        }

        $rest = [];

        foreach ($attributes as $key => $value) {
            if (! in_array($key, $names, true)) {
                $rest[$key] = $value;
            }
        }

        $variables['attributes'] = new Attributes($rest);

        return $variables;
    }

    /**
     * Get the component that is being rendered.
     *
     * @return array|null
     */
    protected static function current()
    {
        return empty(static::$stack) ? null : static::$stack[count(static::$stack) - 1];
    }

    /**
     * Render the component that is being closed.
     *
     * @return string
     */
    protected static function render_component()
    {
        $current = static::current();
        $class = static::resolve($current['name']);

        if (is_null($class)) {
            return static::render_view(static::view($current['name']), $current, []);
        }

        $instance = new $class();
        $instance->attributes = $current['attributes'];

        foreach ($current['attributes'] as $key => $value) {
            $property = static::variable($key);

            if (property_exists($instance, $property)) {
                $instance->{$property} = $value;
            }
        }

        if (method_exists($instance, 'boot')) {
            $instance->boot();
        }

        $rendered = $instance->render();
        $data = static::properties($instance);

        // A component class returns either a view name or the output itself.
        if (! is_string($rendered) || ! View::exists($rendered)) {
            return static::interpolate((string) $rendered, $current, $data);
        }

        return static::render_view($rendered, $current, $data);
    }

    /**
     * Get the public properties of the component class.
     *
     * @param object $instance
     *
     * @return array
     */
    protected static function properties($instance)
    {
        $reflection = new \ReflectionObject($instance);
        $data = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || 'attributes' === $property->getName()) {
                continue;
            }

            $data[$property->getName()] = $property->getValue($instance);
        }

        return $data;
    }

    /**
     * Render the view of the component.
     *
     * @param string $view
     * @param array  $component
     * @param array  $data
     *
     * @return string
     */
    protected static function render_view($view, array $component, array $data)
    {
        if (! View::exists($view)) {
            throw new \Exception(sprintf('Component view is not found: %s', $view));
        }

        return View::make($view, static::data($component, $data))->render();
    }

    /**
     * Fill the slots into a string, for a component class that returns content.
     *
     * @param string $content
     * @param array  $component
     * @param array  $data
     *
     * @return string
     */
    protected static function interpolate($content, array $component, array $data)
    {
        $slots = $component['slots'];

        foreach ($slots as $name => $value) {
            $content = str_replace('{{ $' . $name . ' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Build the variables the view of the component is given.
     *
     * @param array $component
     * @param array $data
     *
     * @return array
     */
    protected static function data(array $component, array $data)
    {
        $variables = $data;

        foreach ($component['attributes'] as $key => $value) {
            $variables[static::variable($key)] = $value;
        }

        foreach ($component['slots'] as $name => $value) {
            $variables[static::variable($name)] = new Html($value);
        }

        $variables['attributes'] = new Attributes($component['attributes']);
        $variables['slot'] = isset($variables['slot']) ? $variables['slot'] : new Html('');

        return $variables;
    }

    /**
     * Get the registered component class, or the one its name implies.
     *
     * @param string $name
     *
     * @return string|null
     */
    protected static function resolve($name)
    {
        if (isset(static::$registrar[$name])) {
            return static::$registrar[$name];
        }

        $package = DEFAULT_PACKAGE;
        $component = $name;

        if (false !== strpos($name, '::')) {
            list($package, $component) = explode('::', $name, 2);
        }

        $class = Package::class_prefix($package) . Str::classify($component) . '_Component';

        if (class_exists($class, false)) {
            return $class;
        }

        // Read here, not via the autoloader: PSR-0 would look for components/badge/component.php.
        $file = Package::path($package) . 'components' . DS . str_replace('.', DS, $component) . '.php';

        if (is_file($file)) {
            require_once $file;
        }

        return class_exists($class, false) ? $class : null;
    }

    /**
     * Get the name of the view of the component.
     *
     * @param string $name
     *
     * @return string
     */
    protected static function view($name)
    {
        if (false === strpos($name, '::')) {
            return 'components.' . $name;
        }

        list($package, $component) = explode('::', $name, 2);

        return $package . '::components.' . $component;
    }

    /**
     * Turn an attribute name into a variable name, so a dashed one still lands somewhere.
     *
     * @param string $name
     *
     * @return string
     */
    protected static function variable($name)
    {
        return str_replace('-', '_', $name);
    }

    /**
     * Get what the component renders to: a view name, or the content itself.
     *
     * @return string
     */
    public function render()
    {
        return '';
    }
}
