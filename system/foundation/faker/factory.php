<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

use System\Config;

class Factory
{
    protected static $providers = [
        'Address', 'Barcode', 'Biased', 'Color', 'Company',
        'Dates', 'File', 'Image', 'Internet', 'Lorem',
        'Miscellaneous', 'Payment', 'Person', 'Phone',
        'Browser', 'Uuid',
    ];

    /**
     * Create a new Generator with providers for the given locale.
     *
     * @param string|null $locale
     *
     * @return \System\Foundation\Faker\Generator
     */
    public static function create($locale = null)
    {
        $locale = is_null($locale) ? Config::get('application.language', 'en') : $locale;
        $locales = array_map(function ($item) {
            $item = explode(DS, $item);
            return end($item);
        }, glob(path('system').'foundation'.DS.'faker'.DS.'provider'.DS.'*', GLOB_ONLYDIR));

        if (! in_array($locale, $locales)) {
            throw new \InvalidArgumentException(sprintf('Locale folder cannot be found: %s', $locale));
        }

        $generator = new Generator();
        $providers = static::$providers;

        foreach ($providers as $provider) {
            $class = static::getProviderClassname($provider, $locale);
            $generator->addProvider(new $class($generator));
        }

        return $generator;
    }

    /**
     * Get the provider classname for the given provider and locale.
     *
     * @param string $provider
     * @param string $locale
     *
     * @return string
     */
    protected static function getProviderClassname($provider, $locale = '')
    {
        if ($class = static::findProviderClassname($provider, $locale)) {
            return $class;
        }

        if ($class = static::findProviderClassname($provider)) {
            return $class;
        }

        throw new \InvalidArgumentException(sprintf("Unable to find provider '%s' with locale '%s'", $provider, $locale));
    }

    /**
     * Find the provider classname.
     *
     * @param string $provider
     * @param string $locale
     *
     * @return string|null
     */
    protected static function findProviderClassname($provider, $locale = '')
    {
        $locale = (! is_null($locale) && '' !== trim($locale)) ? $locale.'\\' : '';
        $class = '\\System\\Foundation\\Faker\\Provider\\'.$locale.$provider;

        if (class_exists($class, true)) {
            return $class;
        }
    }
}
