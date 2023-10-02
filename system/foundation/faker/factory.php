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

        foreach (static::$providers as $provider) {
            $class = self::getProviderClassname($provider, $locale);
            $generator->addProvider(new $class($generator));
        }

        return $generator;
    }

    protected static function getProviderClassname($provider, $locale = '')
    {
        if ($class = self::findProviderClassname($provider, $locale)) {
            return $class;
        }

        $language = Config::get('application.language', 'en');

        if ($class = self::findProviderClassname($provider, $language)) {
            return $class;
        }

        if ($class = self::findProviderClassname($provider)) {
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
