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

    public static function create($locale = null)
    {
        $locale = is_null($locale) ? Config::get('application.language', 'id') : $locale;
        $locales = array_map(function ($item) {
            $item = explode(DS, $item);
            return end($item);
        }, glob(path('system') . 'foundation' . DS . 'faker' . DS . 'provider' . DS . '*', GLOB_ONLYDIR));

        if (!in_array($locale, $locales)) {
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

    protected static function getProviderClassname($provider, $locale = '')
    {
        if ($class = static::findProviderClassname($provider, $locale)) {
            return $class;
        }

        // Note: the fallback is the locale-neutral provider, never the
        // application's own language. Falling back to that made fake('en') hand
        // out Indonesian data for every provider without an 'en' variant, which
        // is the opposite of what asking for a locale means.
        if ($class = static::findProviderClassname($provider)) {
            return $class;
        }

        throw new \InvalidArgumentException(sprintf("Unable to find provider '%s' with locale '%s'", $provider, $locale));
    }

    protected static function findProviderClassname($provider, $locale = '')
    {
        $locale = (!is_null($locale) && '' !== trim($locale)) ? $locale . '\\' : '';
        $class = '\\System\\Foundation\\Faker\\Provider\\' . $locale . $provider;

        if (class_exists($class, true)) {
            return $class;
        }
    }
}
