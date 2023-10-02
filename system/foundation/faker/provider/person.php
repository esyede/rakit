<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Person extends Base
{
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';

    protected static $titleFormat = ['{{titleMale}}', '{{titleFemale}}'];
    protected static $firstNameFormat = ['{{firstNameMale}}', '{{firstNameFemale}}'];
    protected static $maleNameFormats = ['{{firstNameMale}} {{lastName}}'];
    protected static $femaleNameFormats = ['{{firstNameFemale}} {{lastName}}'];
    protected static $firstNameMale = ['John'];
    protected static $firstNameFemale = ['Jane'];
    protected static $lastName = ['Doe'];
    protected static $titleMale = ['Mr.', 'Dr.', 'Prof.'];
    protected static $titleFemale = ['Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.'];

    public function name($gender = null)
    {
        if ($gender === static::GENDER_MALE) {
            $format = static::randomElement(static::$maleNameFormats);
        } elseif ($gender === static::GENDER_FEMALE) {
            $format = static::randomElement(static::$femaleNameFormats);
        } else {
            $format = static::randomElement(array_merge(static::$maleNameFormats, static::$femaleNameFormats));
        }

        return $this->generator->parse($format);
    }

    public function firstName($gender = null)
    {
        if ($gender === static::GENDER_MALE) {
            return static::firstNameMale();
        } elseif ($gender === static::GENDER_FEMALE) {
            return static::firstNameFemale();
        }

        return $this->generator->parse(static::randomElement(static::$firstNameFormat));
    }

    public static function firstNameMale()
    {
        return static::randomElement(static::$firstNameMale);
    }

    public static function firstNameFemale()
    {
        return static::randomElement(static::$firstNameFemale);
    }

    public function lastName()
    {
        return static::randomElement(static::$lastName);
    }

    public function title($gender = null)
    {
        if ($gender === static::GENDER_MALE) {
            return static::titleMale();
        } elseif ($gender === static::GENDER_FEMALE) {
            return static::titleFemale();
        }

        return $this->generator->parse(static::randomElement(static::$titleFormat));
    }

    public static function titleMale()
    {
        return static::randomElement(static::$titleMale);
    }

    public static function titleFemale()
    {
        return static::randomElement(static::$titleFemale);
    }
}
