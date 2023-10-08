<?php

namespace System\Foundation\Faker\Provider\en;

defined('DS') or exit('No direct script access.');

use System\Foundation\Faker\Provider\Phone as BasePhone;

class Phone extends BasePhone
{
    protected static $tollFreeAreaCodes = [800, 844, 855, 866, 877, 888];
    protected static $formats = [
        '+1-{{areaCode}}-{{exchangeCode}}-####',
        '+1 ({{areaCode}}) {{exchangeCode}}-####',
        '+1-{{areaCode}}-{{exchangeCode}}-####',
        '+1.{{areaCode}}.{{exchangeCode}}.####',
        '+1{{areaCode}}{{exchangeCode}}####',

        '{{areaCode}}-{{exchangeCode}}-####',
        '({{areaCode}}) {{exchangeCode}}-####',
        '1-{{areaCode}}-{{exchangeCode}}-####',
        '{{areaCode}}.{{exchangeCode}}.####',

        '{{areaCode}}-{{exchangeCode}}-####',
        '({{areaCode}}) {{exchangeCode}}-####',
        '1-{{areaCode}}-{{exchangeCode}}-####',
        '{{areaCode}}.{{exchangeCode}}.####',

        '{{areaCode}}-{{exchangeCode}}-#### x###',
        '({{areaCode}}) {{exchangeCode}}-#### x###',
        '1-{{areaCode}}-{{exchangeCode}}-#### x###',
        '{{areaCode}}.{{exchangeCode}}.#### x###',

        '{{areaCode}}-{{exchangeCode}}-#### x####',
        '({{areaCode}}) {{exchangeCode}}-#### x####',
        '1-{{areaCode}}-{{exchangeCode}}-#### x####',
        '{{areaCode}}.{{exchangeCode}}.#### x####',

        '{{areaCode}}-{{exchangeCode}}-#### x#####',
        '({{areaCode}}) {{exchangeCode}}-#### x#####',
        '1-{{areaCode}}-{{exchangeCode}}-#### x#####',
        '{{areaCode}}.{{exchangeCode}}.#### x#####',
    ];

    protected static $tollFreeFormats = [
        '{{tollFreeAreaCode}}-{{exchangeCode}}-####',
        '({{tollFreeAreaCode}}) {{exchangeCode}}-####',
        '1-{{tollFreeAreaCode}}-{{exchangeCode}}-####',
        '{{tollFreeAreaCode}}.{{exchangeCode}}.####',
    ];

    public function tollFreeAreaCode()
    {
        return self::randomElement(static::$tollFreeAreaCodes);
    }

    public function tollFreePhoneNumber()
    {
        return self::numerify($this->generator->parse(self::randomElement(static::$tollFreeFormats)));
    }

    public static function areaCode()
    {
        $digit = self::randomDigit();
        return self::numberBetween(2, 9) . self::randomDigit() . self::randomDigitNotNull($digit);
    }

    public static function exchangeCode()
    {
        $digits = [self::numberBetween(2, 9), self::randomDigit()];
        $digits[] = (1 === $digits[1]) ? self::randomDigitNotNull(1) : self::randomDigit();
        return implode('', $digits);
    }
}
