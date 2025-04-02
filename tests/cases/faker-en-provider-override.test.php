<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Factory;

class FakerEnProviderOverrideTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING_REGEX = '/.+/u';
    const TEST_EMAIL_REGEX = '/^(.+)@(.+)$/ui';

    /**
     * Setup.
     */
    public function setUp()
    {
        // ..
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testAddress($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->city);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->postcode);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->address);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->country);
    }


    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testCompany($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->company);
    }


    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testDateTime($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->century);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->timezone);
    }


    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testInternet($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->userName);
        $this->assertRegExp(static::TEST_EMAIL_REGEX, $faker->email);
        $this->assertRegExp(static::TEST_EMAIL_REGEX, $faker->safeEmail);
        $this->assertRegExp(static::TEST_EMAIL_REGEX, $faker->freeEmail);
        $this->assertRegExp(static::TEST_EMAIL_REGEX, $faker->companyEmail);
    }


    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testPerson($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->name);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->title);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->firstName);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->lastName);
    }


    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testPhoneNumber($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->phoneNumber);
    }


    /**
     * @dataProvider localeDataProvider
     * @param string $locale
     */
    public function testUserAgent($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->userAgent);
    }


    /**
     * @dataProvider localeDataProvider
     *
     * @param null   $locale
     * @param string $locale
     */
    public function testUuid($locale = null)
    {
        $faker = Factory::create($locale);
        $this->assertRegExp(static::TEST_STRING_REGEX, $faker->uuid);
    }


    /**
     * @return array
     */
    public function localeDataProvider()
    {
        $locales = $this->getAllLocales();
        $data = [];

        foreach ($locales as $locale) {
            $data[] = [$locale];
        }

        return $data;
    }


    /**
     * Returns all locales as array values
     *
     * @return array
     */
    private function getAllLocales()
    {
        static $locales = [];

        if (!empty($locales)) {
            return $locales;
        }

        $glob = glob(path('system') . 'foundation/faker/provider/*/*.php');

        foreach ($glob as $file) {
            $localisation = basename(dirname($file));
            if (isset($locales[ $localisation ])) {
                continue;
            }

            $locales[ $localisation ] = $localisation;
        }

        return $locales;
    }
}
