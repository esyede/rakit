<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Calculator\Luhn;
use System\Foundation\Faker\Provider\Base as BaseProvider;
use System\Foundation\Faker\Provider\Person as PersonProvider;
use System\Foundation\Faker\Provider\Dates as DatesProvider;
use System\Foundation\Faker\Provider\Payment as PaymentProvider;

class FakerEnPaymentTest extends \PHPUnit_Framework_TestCase
{
    private $faker;

    /**
     * Setup.
     */
    public function setUp()
    {
        $faker = new FakerGenerator();
        $faker->addProvider(new BaseProvider($faker));
        $faker->addProvider(new DatesProvider($faker));
        $faker->addProvider(new PersonProvider($faker));
        $faker->addProvider(new PaymentProvider($faker));
        $this->faker = $faker;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function testCreditCardTypeReturnsValidVendorName()
    {
        $this->assertTrue(in_array($this->faker->creditCardType, ['Visa', 'MasterCard', 'American Express', 'Discover Card']));
    }

    public function creditCardNumberProvider()
    {
        return [['Discover Card', '/^6011\d{12}$/'], ['Visa', '/^4\d{12,15}$/'], ['MasterCard', '/^5[1-5]\d{14}$/']];
    }

    /**
     * @dataProvider creditCardNumberProvider
     */
    public function testCreditCardNumberReturnsValidCreditCardNumber($type, $regexp)
    {
        $cardNumber = $this->faker->creditCardNumber($type);
        $this->assertRegExp($regexp, $cardNumber);
        $this->assertTrue(Luhn::isValid($cardNumber));
    }

    public function testCreditCardNumberCanFormatOutput()
    {
        $this->assertRegExp('/^6011-\d{4}-\d{4}-\d{4}$/', $this->faker->creditCardNumber('Discover Card', true));
    }

    public function testCreditCardExpirationDateReturnsValidDateByDefault()
    {
        $expirationDate = $this->faker->creditCardExpirationDate;
        $this->assertTrue(intval($expirationDate->format('U')) > strtotime('now'));
        $this->assertTrue(intval($expirationDate->format('U')) < strtotime('+36 months'));
    }

    public function testRandomCard()
    {
        $cardDetails = $this->faker->creditCardDetails;
        $this->assertEquals(count($cardDetails), 4);
        $this->assertEquals(array('type', 'number', 'name', 'expirationDate'), array_keys($cardDetails));
    }
}
