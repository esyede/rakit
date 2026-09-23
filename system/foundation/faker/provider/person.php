<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Person extends Base
{
    /** @var string */
    const GENDER_MALE = 'male';

    /** @var string */
    const GENDER_FEMALE = 'female';

    /**
     * The list of title formats.
     *
     * @var array
     */
    protected static $titleFormat = ['{{titleMale}}', '{{titleFemale}}'];

    /**
     * The list of first name formats.
     *
     * @var array
     */
    protected static $firstNameFormat = ['{{firstNameMale}}', '{{firstNameFemale}}'];

    /**
     * The list of male name formats.
     *
     * @var array
     */
    protected static $maleNameFormats = ['{{firstNameMale}} {{lastName}}'];

    /**
     * The list of female name formats.
     *
     * @var array
     */
    protected static $femaleNameFormats = ['{{firstNameFemale}} {{lastName}}'];

    /**
     * The list of first name male.
     *
     * @var array
     */
    protected static $firstNameMale = ['John'];

    /**
     * The list of first name female.
     *
     * @var array
     */
    protected static $firstNameFemale = ['Jane'];

    /**
     * The list of last name.
     *
     * @var array
     */
    protected static $lastName = ['Doe'];

    /**
     * The list of title male.
     *
     * @var array
     */
    protected static $titleMale = ['Mr.', 'Dr.', 'Prof.'];

    /**
     * The list of title female.
     *
     * @var array
     */
    protected static $titleFemale = ['Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.'];

    /**
     * Generate a random person name.
     *
     * @param string|null $gender
     *
     * @return string
     */
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

    /**
     * Generate a random first name.
     *
     * @param string|null $gender
     *
     * @return string
     */
    public function firstName($gender = null)
    {
        if ($gender === static::GENDER_MALE) {
            return static::firstNameMale();
        } elseif ($gender === static::GENDER_FEMALE) {
            return static::firstNameFemale();
        }

        return $this->generator->parse(static::randomElement(static::$firstNameFormat));
    }

    /**
     * Get a random male first name.
     *
     * @return string
     */
    public static function firstNameMale()
    {
        return static::randomElement(static::$firstNameMale);
    }

    /**
     * Get a random female first name.
     *
     * @return string
     */
    public static function firstNameFemale()
    {
        return static::randomElement(static::$firstNameFemale);
    }

    /**
     * Get a random last name.
     *
     * @return string
     */
    public function lastName()
    {
        return static::randomElement(static::$lastName);
    }

    /**
     * Generate a random title.
     *
     * @param string|null $gender
     *
     * @return string
     */
    public function title($gender = null)
    {
        if ($gender === static::GENDER_MALE) {
            return static::titleMale();
        } elseif ($gender === static::GENDER_FEMALE) {
            return static::titleFemale();
        }

        return $this->generator->parse(static::randomElement(static::$titleFormat));
    }

    /**
     * Get a random male title.
     *
     * @return string
     */
    public static function titleMale()
    {
        return static::randomElement(static::$titleMale);
    }

    /**
     * Get a random female title.
     *
     * @return string
     */
    public static function titleFemale()
    {
        return static::randomElement(static::$titleFemale);
    }
}
