<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct script access.');

use System\Config;

class Factory
{
    protected static $defaultProviders = [
        'Address', 'Barcode', 'Biased', 'Color', 'Company',
        'Dates', 'File', 'Image', 'Internet', 'Lorem',
        'Miscellaneous', 'Payment', 'Person', 'Phone',
        'Browser', 'Uuid',
    ];

    public static function create($locale = null)
    {
        $locale = is_null($locale) ? Config::get('application.language', 'id') : $locale;
        $locales = glob(path('system').'foundation'.DS.'faker'.DS.'provider'.DS.'*', GLOB_ONLYDIR);
        $locales = array_map(function ($item) {
            $item = explode(DS, $item);
            return end($item);
        }, $locales);

        if (! in_array($locale, $locales)) {
            $locale = path('system').'foundation'.DS.'faker'.DS.'provider'.DS.$locale;
            throw new \InvalidArgumentException(sprintf('Locale folder cannot be found: %s', $locale));
        }

        $generator = new Generator();

        foreach (static::$defaultProviders as $provider) {
            $providerClassName = self::getProviderClassname($provider, $locale);
            $generator->addProvider(new $providerClassName($generator));
        }

        return $generator;
    }

    protected static function getProviderClassname($provider, $locale = '')
    {
        if ($providerClass = self::findProviderClassname($provider, $locale)) {
            return $providerClass;
        }

        $defaultLocale = Config::get('application.language', 'en');

        if ($providerClass = self::findProviderClassname($provider, $defaultLocale)) {
            return $providerClass;
        }

        if ($providerClass = self::findProviderClassname($provider)) {
            return $providerClass;
        }

        throw new \InvalidArgumentException(sprintf(
            "Unable to find provider '%s' with locale '%s'", $provider, $locale
        ));
    }

    protected static function findProviderClassname($provider, $locale = '')
    {
        $locale = (! is_null($locale) && '' !== trim($locale)) ? $locale.'\\' : '';
        $providerNs = '\\System\\Foundation\\Faker\\Provider\\';
        $providerClass = $providerNs.$locale.$provider;

        if (class_exists($providerClass, true)) {
            return $providerClass;
        }
    }
}
