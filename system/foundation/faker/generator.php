<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Generator
{
    /**
     * @var array */
    protected $providers = [];

    /** @var array */
    protected $formatters = [];

    /**
     * Add a provider to the generator.
     *
     * @param object $provider
     */
    public function addProvider($provider)
    {
        array_unshift($this->providers, $provider);
    }

    /**
     * Get all providers.
     *
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Seed the random number generator.
     *
     * @param int|null $seed
     */
    public function seed($seed = null)
    {
        if (null === $seed) {
            mt_srand();
        } else {
            if (PHP_VERSION_ID < 70100) {
                mt_srand((int) $seed);
            } else {
                mt_srand((int) $seed, MT_RAND_MT19937);
            }
        }
    }

    /**
     * Format a value using the given formatter.
     *
     * @param string $formatter
     * @param array  $arguments
     *
     * @return mixed
     */
    public function format($formatter, array $arguments = [])
    {
        return call_user_func_array($this->getFormatter($formatter), $arguments);
    }

    /**
     * Get the formatter callable.
     *
     * @param string $formatter
     *
     * @return callable
     */
    public function getFormatter($formatter)
    {
        if (isset($this->formatters[$formatter])) {
            return $this->formatters[$formatter];
        }

        foreach ($this->providers as $provider) {
            if (method_exists($provider, $formatter)) {
                $this->formatters[$formatter] = [$provider, $formatter];
                return $this->formatters[$formatter];
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown formatter: %s', $formatter));
    }

    /**
     * Parse a string and replace placeholders with formatted values.
     *
     * @param string $string
     *
     * @return string
     */
    public function parse($string)
    {
        return preg_replace_callback('/\{\{\s?(\w+)\s?\}\}/u', [$this, 'callFormatWithMatches'], $string);
    }

    /**
     * Call a formatter with matches from preg_replace_callback.
     *
     * @param array $matches
     *
     * @return mixed
     */
    protected function callFormatWithMatches($matches)
    {
        return $this->format($matches[1]);
    }

    /**
     * Magic getter to format values.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        return $this->format($attribute);
    }

    /**
     * Magic call to format values.
     *
     * @param string $method
     * @param array  $attributes
     *
     * @return mixed
     */
    public function __call($method, array $attributes)
    {
        return $this->format($method, $attributes);
    }
}
