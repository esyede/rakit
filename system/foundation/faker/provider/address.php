<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Address extends Base
{
    /**
     * The list of city suffixes.
     *
     * @var array
     */
    protected static $citySuffix = ['Ville'];

    /**
     * The list of street suffixes.
     *
     * @var array
     */
    protected static $streetSuffix = ['Street'];

    /**
     * The list of city formats.
     *
     * @var array
     */
    protected static $cityFormats = ['{{firstName}}{{citySuffix}}'];

    /**
     * The list of street name formats.
     *
     * @var array
     */
    protected static $streetNameFormats = ['{{lastName}} {{streetSuffix}}'];

    /**
     * The list of street address formats.
     *
     * @var array
     */
    protected static $streetAddressFormats = ['{{buildingNumber}} {{streetName}}'];

    /**
     * The list of address formats.
     *
     * @var array
     */
    protected static $addressFormats = ['{{streetAddress}} {{postcode}} {{city}}'];

    /**
     * The list of building numbers.
     *
     * @var array
     */
    protected static $buildingNumber = ['##'];

    /**
     * The list of postcodes.
     *
     * @var array
     */
    protected static $postcode = ['#####'];

    /**
     * The list of countries.
     *
     * @var array
     */
    protected static $country = [];

    /**
     * Get a random city suffix.
     *
     * @return string
     */
    public static function citySuffix()
    {
        return static::randomElement(static::$citySuffix);
    }

    /**
     * Get a random street suffix.
     *
     * @return string
     */
    public static function streetSuffix()
    {
        return static::randomElement(static::$streetSuffix);
    }

    /**
     * Get a random building number.
     *
     * @return string
     */
    public static function buildingNumber()
    {
        return static::numerify(static::randomElement(static::$buildingNumber));
    }

    /**
     * Get a random city prefix.
     *
     * @return string
     */
    public static function cityPrefix()
    {
        // ..
    }

    /**
     * Get a random secondary address.
     *
     * @return string
     */
    public static function secondaryAddress()
    {
        // ..
    }

    /**
     * Get a random city name.
     *
     * @return string
     */
    public function city()
    {
        return $this->generator->parse(static::randomElement(static::$cityFormats));
    }

    /**
     * Get a random street name.
     *
     * @return string
     */
    public function streetName()
    {
        return $this->generator->parse(static::randomElement(static::$streetNameFormats));
    }

    /**
     * Get a random street address.
     *
     * @return string
     */
    public function streetAddress()
    {
        return $this->generator->parse(static::randomElement(static::$streetAddressFormats));
    }

    /**
     * Get a random postcode.
     *
     * @return string
     */
    public static function postcode()
    {
        return static::toUpper(static::bothify(static::randomElement(static::$postcode)));
    }

    /**
     * Get a random full address.
     *
     * @return string
     */
    public function address()
    {
        return $this->generator->parse(static::randomElement(static::$addressFormats));
    }

    /**
     * Get a random country name.
     *
     * @return string
     */
    public static function country()
    {
        return static::randomElement(static::$country);
    }

    /**
     * Get a random latitude coordinate.
     *
     * @return float
     */
    public static function latitude()
    {
        return static::randomFloat(6, 0, 180) - 90;
    }

    /**
     * Get a random longitude coordinate.
     *
     * @return float
     */
    public static function longitude()
    {
        return static::randomFloat(6, 0, 360) - 180;
    }
}
