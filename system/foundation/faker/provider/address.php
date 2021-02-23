<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Address extends Base
{
    protected static $citySuffix = ['Ville'];
    protected static $streetSuffix = ['Street'];
    protected static $cityFormats = [
        '{{firstName}}{{citySuffix}}',
    ];

    protected static $streetNameFormats = [
        '{{lastName}} {{streetSuffix}}',
    ];

    protected static $streetAddressFormats = [
        '{{buildingNumber}} {{streetName}}',
    ];

    protected static $addressFormats = [
        '{{streetAddress}} {{postcode}} {{city}}',
    ];

    protected static $buildingNumber = ['##'];
    protected static $postcode = ['#####'];
    protected static $country = [];

    public static function citySuffix()
    {
        return static::randomElement(static::$citySuffix);
    }

    public static function streetSuffix()
    {
        return static::randomElement(static::$streetSuffix);
    }

    public static function buildingNumber()
    {
        return static::numerify(static::randomElement(static::$buildingNumber));
    }

    public static function cityPrefix()
    {
        // ..
    }

    public static function secondaryAddress()
    {
        // ..
    }

    public function city()
    {
        $format = static::randomElement(static::$cityFormats);

        return $this->generator->parse($format);
    }

    public function streetName()
    {
        $format = static::randomElement(static::$streetNameFormats);

        return $this->generator->parse($format);
    }

    public function streetAddress()
    {
        $format = static::randomElement(static::$streetAddressFormats);

        return $this->generator->parse($format);
    }

    public static function postcode()
    {
        return static::toUpper(static::bothify(static::randomElement(static::$postcode)));
    }

    public function address()
    {
        $format = static::randomElement(static::$addressFormats);

        return $this->generator->parse($format);
    }

    public static function country()
    {
        return static::randomElement(static::$country);
    }

    public static function latitude()
    {
        return static::randomFloat(6, 0, 180) - 90;
    }

    public static function longitude()
    {
        return static::randomFloat(6, 0, 360) - 180;
    }
}
