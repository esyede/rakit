<?php

namespace System\Foundation\Faker\Provider\id;

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Provider\Company as BaseCompany;

class Company extends BaseCompany
{
    protected static $formats = [
        '{{companyPrefix}} {{lastName}}',
        '{{companyPrefix}} {{lastName}} {{lastName}}',
        '{{companyPrefix}} {{lastName}} {{companySuffix}}',
        '{{companyPrefix}} {{lastName}} {{lastName}} {{companySuffix}}',
    ];

    protected static $companyPrefix = ['PT', 'CV', 'UD', 'PD', 'Perum'];

    protected static $companySuffix = ['(Persero) Tbk', 'Tbk'];

    /**
     * Get a random Indonesian company prefix.
     *
     * @return string
     */
    public static function companyPrefix()
    {
        return static::randomElement(static::$companyPrefix);
    }

    /**
     * Get a random Indonesian company suffix.
     *
     * @return string
     */
    public static function companySuffix()
    {
        return static::randomElement(static::$companySuffix);
    }

    /**
     * Generate a random catch phrase.
     *
     * @return string
     */
    public function catchPhrase()
    {
        // ..
    }

    /**
     * Generate a random BS phrase.
     *
     * @return string
     */
    public function bs()
    {
        // ..
    }
}
