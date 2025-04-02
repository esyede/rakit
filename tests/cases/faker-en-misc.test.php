<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Provider\Miscellaneous;

class FakerEnMiscTest extends \PHPUnit_Framework_TestCase
{
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

    public function testBoolean()
    {
        $this->assertContains(Miscellaneous::boolean(), array(true, false));
    }

    public function testMd5()
    {
        $this->assertRegExp('/^[a-z0-9]{32}$/', Miscellaneous::md5());
    }

    public function testSha1()
    {
        $this->assertRegExp('/^[a-z0-9]{40}$/', Miscellaneous::sha1());
    }

    public function testSha256()
    {
        $this->assertRegExp('/^[a-z0-9]{64}$/', Miscellaneous::sha256());
    }

    public function testLocale()
    {
        $this->assertRegExp('/^[a-z]{2,3}_[A-Z]{2}$/', Miscellaneous::locale());
    }

    public function testCountryCode()
    {
        $this->assertRegExp('/^[A-Z]{2}$/', Miscellaneous::countryCode());
    }

    public function testCountryISOAlpha3()
    {
        $this->assertRegExp('/^[A-Z]{3}$/', Miscellaneous::countryISOAlpha3());
    }

    public function testLanguage()
    {
        $this->assertRegExp('/^[a-z]{2}$/', Miscellaneous::languageCode());
    }

    public function testCurrencyCode()
    {
        $this->assertRegExp('/^[A-Z]{3}$/', Miscellaneous::currencyCode());
    }
}
