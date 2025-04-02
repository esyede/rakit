<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Provider\Company;
use System\Foundation\Faker\Provider\Internet;
use System\Foundation\Faker\Provider\Lorem;
use System\Foundation\Faker\Provider\Person;

class FakerEnInternetTest extends \PHPUnit_Framework_TestCase
{
    private $faker;

    /**
     * Setup.
     */
    public function setUp()
    {
        $faker = new FakerGenerator();
        $faker->addProvider(new Lorem($faker));
        $faker->addProvider(new Person($faker));
        $faker->addProvider(new Internet($faker));
        $faker->addProvider(new Company($faker));
        $this->faker = $faker;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function localeDataProvider()
    {
        $providerPath = path('system') . 'foundation' . DS . 'faker' . DS . 'provider';
        $localePaths = array_filter(glob($providerPath . DS . '*', GLOB_ONLYDIR));

        foreach ($localePaths as $path) {
            $parts = explode(DS, $path);
            $locales[] = array($parts[count($parts) - 1]);
        }

        return $locales;
    }

    /**
     * @dataProvider localeDataProvider
     */
    public function testEmailIsValid($locale)
    {
        $this->loadLocalProviders($locale);
        // Ref: https://stackoverflow.com/questions/12026842/how-to-validate-an-email-address-in-php
        $pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
        $this->assertRegExp($pattern, $this->faker->email());
    }

    /**
     * @dataProvider localeDataProvider
     */
    public function testUsernameIsValid($locale)
    {
        $this->loadLocalProviders($locale);
        $this->assertRegExp('/^[A-Za-z0-9._]+$/', $this->faker->username());
    }

    public function loadLocalProviders($locale)
    {
        $providerPath = path('system') . 'foundation' . DS . 'faker' . DS . 'provider';

        if (is_file($providerPath . DS . $locale . DS . 'internet.php')) {
            $internet = "\\System\\Foundation\\Faker\\Provider\\$locale\\Internet";
            $this->faker->addProvider(new $internet($this->faker));
        }

        if (is_file($providerPath . DS . $locale . DS . 'person.php')) {
            $person = "\\System\\Foundation\\Faker\\Provider\\$locale\\Person";
            $this->faker->addProvider(new $person($this->faker));
        }

        if (is_file($providerPath . DS . $locale . DS . 'company.php')) {
            $company = "\\System\\Foundation\\Faker\\Provider\\$locale\\Company";
            $this->faker->addProvider(new $company($this->faker));
        }
    }

    public function testPasswordIsValid()
    {
        $this->assertRegexp('/^.{6}$/', $this->faker->password(6, 6));
    }

    public function testSlugIsValid()
    {
        $this->assertSame(preg_match('/^[a-z0-9-]+$/', $this->faker->slug()), 1);
    }

    public function testUrlIsValid()
    {
        $this->assertNotFalse(filter_var($this->faker->url(), FILTER_VALIDATE_URL));
    }

    public function testLocalIpv4()
    {
        $this->assertNotFalse(filter_var(Internet::localIpv4(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
    }

    public function testIpv4()
    {
        $this->assertNotFalse(filter_var($this->faker->ipv4(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
    }

    public function testIpv6()
    {
        $this->assertNotFalse(filter_var($this->faker->ipv6(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
    }

    public function testMacAddress()
    {
        $this->assertRegExp('/^([0-9A-F]{2}[:]){5}([0-9A-F]{2})$/i', Internet::macAddress());
    }
}
