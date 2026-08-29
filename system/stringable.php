<?php

namespace System;

defined('DS') or exit('No direct access.');

class Stringable
{
    /**
     * Container for the string.
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $value
     */
    public function __construct($value = '')
    {
        $stringable = is_null($value)
            || is_scalar($value)
            || (is_object($value) && method_exists($value, '__toString'));

        if (! $stringable) {
            throw new \InvalidArgumentException(sprintf(
                'Fluent strings need a value that can become one, %s given.',
                gettype($value)
            ));
        }

        $this->value = (string) $value;
    }

    /**
     * Get the underlying string.
     *
     * @return string
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Count the length of the string.
     *
     * @return int
     */
    public function length()
    {
        return Str::length($this->value);
    }

    /**
     * Take a portion of the string.
     *
     * @param int $start
     * @param int $length
     *
     * @return static
     */
    public function substr($start, $length = null)
    {
        return new static(Str::substr($this->value, $start, $length));
    }

    /**
     * Uppercase the first character.
     *
     * @return static
     */
    public function ucfirst()
    {
        return new static(Str::ucfirst($this->value));
    }

    /**
     * Lowercase the string.
     *
     * @return static
     */
    public function lower()
    {
        return new static(Str::lower($this->value));
    }

    /**
     * Uppercase the string.
     *
     * @return static
     */
    public function upper()
    {
        return new static(Str::upper($this->value));
    }

    /**
     * Uppercase the first character of every word.
     *
     * @return static
     */
    public function title()
    {
        return new static(Str::title($this->value));
    }

    /**
     * Cut the string to the given number of characters.
     *
     * @param int    $limit
     * @param string $end
     *
     * @return static
     */
    public function limit($limit = 100, $end = '...')
    {
        return new static(Str::limit($this->value, $limit, $end));
    }

    /**
     * Strip the whitespace from both ends of the string.
     *
     * @return static
     */
    public function trim()
    {
        return new static(Str::trim($this->value));
    }

    /**
     * Cut the string to the given number of words.
     *
     * @param int    $words
     * @param string $end
     *
     * @return static
     */
    public function words($words = 100, $end = '...')
    {
        return new static(Str::words($this->value, $words, $end));
    }

    /**
     * Get the singular form of the string.
     *
     * @return static
     */
    public function singular()
    {
        return new static(Str::singular($this->value));
    }

    /**
     * Get the plural form of the string.
     *
     * @param int $count
     *
     * @return static
     */
    public function plural($count = 2)
    {
        return new static(Str::plural($this->value, $count));
    }

    /**
     * Get the plural form of the last word of a studly cased string.
     *
     * @param int $count
     *
     * @return static
     */
    public function plural_studly($count = 2)
    {
        return new static(Str::plural_studly($this->value, $count));
    }

    /**
     * Turn the string into a url friendly slug.
     *
     * @param string $separator
     *
     * @return static
     */
    public function slug($separator = '-')
    {
        return new static(Str::slug($this->value, $separator));
    }

    /**
     * Turn the string into a class name.
     *
     * @return static
     */
    public function classify()
    {
        return new static(Str::classify($this->value));
    }

    /**
     * Replace the accented characters with their plain counterparts.
     *
     * @return static
     */
    public function accentless()
    {
        return new static(Str::accentless($this->value));
    }

    /**
     * Get the segments of the string.
     *
     * @return array
     */
    public function segments()
    {
        return Str::segments($this->value);
    }

    /**
     * Determine if the string matches the given pattern.
     *
     * @param string $pattern
     *
     * @return bool
     */
    public function is($pattern)
    {
        return Str::is($pattern, $this->value);
    }

    /**
     * Replace the first occurrence of the given value.
     *
     * @param string $search
     * @param string $replace
     *
     * @return static
     */
    public function replace_first($search, $replace)
    {
        return new static(Str::replace_first($search, $replace, $this->value));
    }

    /**
     * Replace the last occurrence of the given value.
     *
     * @param string $search
     * @param string $replace
     *
     * @return static
     */
    public function replace_last($search, $replace)
    {
        return new static(Str::replace_last($search, $replace, $this->value));
    }

    /**
     * Replace each occurrence of the given value with the next replacement.
     *
     * @param string $search
     * @param array  $replace
     *
     * @return static
     */
    public function replace_array($search, array $replace)
    {
        return new static(Str::replace_array($search, $replace, $this->value));
    }

    /**
     * Replace every occurrence of the given value.
     *
     * @param string|array $search
     * @param string|array $replace
     *
     * @return static
     */
    public function replace($search, $replace)
    {
        return new static(str_replace($search, $replace, $this->value));
    }

    /**
     * Mask the string with the given character.
     *
     * @param string $replacement
     *
     * @return static
     */
    public function censor($replacement = '*')
    {
        return new static(Str::censor($this->value, $replacement));
    }

    /**
     * Get what comes before the first occurrence of the given value.
     *
     * @param string $search
     *
     * @return static
     */
    public function before($search)
    {
        return new static(Str::before($this->value, $search));
    }

    /**
     * Get what comes after the first occurrence of the given value.
     *
     * @param string $search
     *
     * @return static
     */
    public function after($search)
    {
        return new static(Str::after($this->value, $search));
    }

    /**
     * Turn the string into camel case.
     *
     * @return static
     */
    public function camel()
    {
        return new static(Str::camel($this->value));
    }

    /**
     * Turn the string into studly case.
     *
     * @return static
     */
    public function studly()
    {
        return new static(Str::studly($this->value));
    }

    /**
     * Turn the string into kebab case.
     *
     * @return static
     */
    public function kebab()
    {
        return new static(Str::kebab($this->value));
    }

    /**
     * Turn the string into snake case.
     *
     * @param string $delimiter
     *
     * @return static
     */
    public function snake($delimiter = '_')
    {
        return new static(Str::snake($this->value, $delimiter));
    }

    /**
     * Determine if the string contains any of the given values.
     *
     * @param string|array $needles
     *
     * @return bool
     */
    public function contains($needles)
    {
        return Str::contains($this->value, $needles);
    }

    /**
     * Determine if the string contains all of the given values.
     *
     * @param array $needles
     *
     * @return bool
     */
    public function contains_all(array $needles)
    {
        return Str::contains_all($this->value, $needles);
    }

    /**
     * Prefix the string with the given value, unless it is already there.
     *
     * @param string $prefix
     *
     * @return static
     */
    public function start($prefix)
    {
        return new static(Str::start($this->value, $prefix));
    }

    /**
     * Determine if the string starts with the given value.
     *
     * @param string|array $needle
     *
     * @return bool
     */
    public function starts_with($needle)
    {
        return Str::starts_with($this->value, $needle);
    }

    /**
     * Determine if the string ends with the given value.
     *
     * @param string|array $needle
     *
     * @return bool
     */
    public function ends_with($needle)
    {
        return Str::ends_with($this->value, $needle);
    }

    /**
     * Suffix the string with the given value, unless it is already there.
     *
     * @param string $cap
     *
     * @return static
     */
    public function finish($cap)
    {
        return new static(Str::finish($this->value, $cap));
    }

    /**
     * Split the string into its class and method parts.
     *
     * @param string $default
     *
     * @return array
     */
    public function parse_callback($default = null)
    {
        return Str::parse_callback($this->value, $default);
    }

    /**
     * Append the given values to the string.
     *
     * @return static
     */
    public function append()
    {
        return new static($this->value . implode('', func_get_args()));
    }

    /**
     * Prepend the given values to the string.
     *
     * @return static
     */
    public function prepend()
    {
        return new static(implode('', func_get_args()) . $this->value);
    }

    /**
     * Split the string on the given delimiter.
     *
     * @param string $delimiter
     * @param int    $limit
     *
     * @return array
     */
    public function explode($delimiter, $limit = null)
    {
        // An empty delimiter throws on PHP 8 and answers FALSE on the versions
        // before it, so it is refused the same way everywhere.
        if ('' === (string) $delimiter) {
            throw new \InvalidArgumentException('The delimiter to explode on must not be empty.');
        }

        return is_null($limit)
            ? explode($delimiter, $this->value)
            : explode($delimiter, $this->value, (int) $limit);
    }

    /**
     * Determine if the string is empty.
     *
     * @return bool
     */
    public function is_empty()
    {
        return '' === $this->value;
    }

    /**
     * Determine if the string is not empty.
     *
     * @return bool
     */
    public function is_not_empty()
    {
        return '' !== $this->value;
    }

    /**
     * Run the callback when the condition holds. Whatever it returns takes the
     * place of the string, and returning nothing leaves the string alone.
     *
     * @param mixed    $condition
     * @param callable $callback
     * @param callable $default
     *
     * @return mixed
     */
    public function when($condition, $callback, $default = null)
    {
        $condition = ($condition instanceof \Closure) ? $condition($this) : $condition;

        if ($condition) {
            $result = call_user_func($callback, $this, $condition);
        } elseif (! is_null($default)) {
            $result = call_user_func($default, $this, $condition);
        } else {
            return $this;
        }

        return is_null($result) ? $this : $result;
    }

    /**
     * Run the callback unless the condition holds.
     *
     * @param mixed    $condition
     * @param callable $callback
     * @param callable $default
     *
     * @return mixed
     */
    public function unless($condition, $callback, $default = null)
    {
        $condition = ($condition instanceof \Closure) ? $condition($this) : $condition;

        return $this->when(! $condition, $callback, $default);
    }

    /**
     * Hand the string to the callback and keep whatever comes back.
     *
     * @param callable $callback
     *
     * @return static
     */
    public function pipe($callback)
    {
        return new static(call_user_func($callback, $this));
    }

    /**
     * Hand the string to the callback and carry on with the string itself.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function tap($callback)
    {
        call_user_func($callback, $this);

        return $this;
    }

    /**
     * Handle calls to the macros registered on Str. A macro that gives back a
     * string keeps the chain going.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if (! array_key_exists($method, Str::$macros)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        array_unshift($parameters, $this->value);
        $result = call_user_func_array(Str::$macros[$method], $parameters);

        return is_string($result) ? new static($result) : $result;
    }

    /**
     * Get the underlying string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
