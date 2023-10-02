<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Generator
{
    protected $providers = [];
    protected $formatters = [];

    public function addProvider($provider)
    {
        array_unshift($this->providers, $provider);
    }

    public function getProviders()
    {
        return $this->providers;
    }

    public function seed($seed = null)
    {
        if (null === $seed) {
            mt_srand();
        } else {
            if (PHP_VERSION_ID < 70100) {
                mt_srand((int) $seed);
            } else {
                mt_srand((int) $seed, MT_RAND_PHP);
            }
        }
    }

    public function format($formatter, array $arguments = [])
    {
        return call_user_func_array($this->getFormatter($formatter), $arguments);
    }

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

    public function parse($string)
    {
        return preg_replace_callback(
            '/\{\{\s?(\w+)\s?\}\}/u',
            [$this, 'callFormatWithMatches'],
            $string
        );
    }

    protected function callFormatWithMatches($matches)
    {
        return $this->format($matches[1]);
    }

    public function __get($attribute)
    {
        return $this->format($attribute);
    }

    public function __call($method, array $attributes)
    {
        return $this->format($method, $attributes);
    }

    public function __destruct()
    {
        $this->seed();
    }
}
