<?php

namespace System\Foundation\Faker\Provider\en;

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Provider\Phone as BasePhone;

class Phone extends BasePhone
{
    protected static $tollFreeAreaCodes = [800, 844, 855, 866, 877, 888];

    protected static $formats = [
        '+1-{{areaCode}}-{{exchangeCode}}-####', '+1 ({{areaCode}}) {{exchangeCode}}-####', '+1-{{areaCode}}-{{exchangeCode}}-####',
        '+1.{{areaCode}}.{{exchangeCode}}.####', '+1{{areaCode}}{{exchangeCode}}####', '{{areaCode}}-{{exchangeCode}}-####',
        '({{areaCode}}) {{exchangeCode}}-####', '1-{{areaCode}}-{{exchangeCode}}-####', '{{areaCode}}.{{exchangeCode}}.####',
        '{{areaCode}}-{{exchangeCode}}-####', '({{areaCode}}) {{exchangeCode}}-####', '1-{{areaCode}}-{{exchangeCode}}-####',
        '{{areaCode}}.{{exchangeCode}}.####', '{{areaCode}}-{{exchangeCode}}-#### x###', '({{areaCode}}) {{exchangeCode}}-#### x###',
        '1-{{areaCode}}-{{exchangeCode}}-#### x###', '{{areaCode}}.{{exchangeCode}}.#### x###', '{{areaCode}}-{{exchangeCode}}-#### x####',
        '({{areaCode}}) {{exchangeCode}}-#### x####', '1-{{areaCode}}-{{exchangeCode}}-#### x####', '{{areaCode}}.{{exchangeCode}}.#### x####',
        '{{areaCode}}-{{exchangeCode}}-#### x#####', '({{areaCode}}) {{exchangeCode}}-#### x#####', '1-{{areaCode}}-{{exchangeCode}}-#### x#####',
        '{{areaCode}}.{{exchangeCode}}.#### x#####',
    ];

    protected static $tollFreeFormats = [
        '{{tollFreeAreaCode}}-{{exchangeCode}}-####', '({{tollFreeAreaCode}}) {{exchangeCode}}-####',
        '1-{{tollFreeAreaCode}}-{{exchangeCode}}-####', '{{tollFreeAreaCode}}.{{exchangeCode}}.####',
    ];

    /**
     * Get a random toll-free area code.
     *
     * @return int
     */
    public function tollFreeAreaCode()
    {
        return static::randomElement(static::$tollFreeAreaCodes);
    }

    /**
     * Generate a random toll-free phone number.
     *
     * @return string
     */
    public function tollFreePhoneNumber()
    {
        return static::numerify($this->generator->parse(static::randomElement(static::$tollFreeFormats)));
    }

    /**
     * Generate a random area code.
     *
     * @return string
     */
    public static function areaCode()
    {
        return static::numberBetween(2, 9).static::randomDigit().static::randomDigitNotNull(static::randomDigit());
    }

    /**
     * Generate a random exchange code.
     *
     * @return string
     */
    public static function exchangeCode()
    {
        $digits = [static::numberBetween(2, 9), static::randomDigit()];
        $digits[] = (1 === $digits[1]) ? static::randomDigitNotNull(1) : static::randomDigit();
        return implode('', $digits);
    }
}
