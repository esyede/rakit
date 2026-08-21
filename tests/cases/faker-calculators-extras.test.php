<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Valid;
use System\Foundation\Faker\Factory;
use System\Foundation\Faker\Calculator\Ean;
use System\Foundation\Faker\Calculator\Inn;

class FakerCalculatorsExtrasTest extends \PHPUnit_Framework_TestCase
{
    // -------------------------------------------------------------------------
    // Ean
    // -------------------------------------------------------------------------

    /**
     * Test for Ean::checksum() with real barcodes.
     *
     * @group system
     */
    public function testEanChecksum()
    {
        // EAN-13
        $this->assertEquals(1, Ean::checksum('400638133393'));
        $this->assertEquals(4, Ean::checksum('978020137962'));

        // EAN-8
        $this->assertEquals(7, Ean::checksum('7351353'));
        $this->assertEquals(4, Ean::checksum('9638507'));
    }

    /**
     * Test for Ean::isValid() with valid barcodes.
     *
     * @group system
     */
    public function testEanIsValid()
    {
        $this->assertTrue(Ean::isValid('4006381333931'));
        $this->assertTrue(Ean::isValid('9780201379624'));
        $this->assertTrue(Ean::isValid('73513537'));
        $this->assertTrue(Ean::isValid('96385074'));
    }

    /**
     * Test for Ean::isValid() with invalid barcodes.
     *
     * @group system
     */
    public function testEanIsInvalid()
    {
        // wrong check digit
        $this->assertFalse(Ean::isValid('4006381333930'));
        $this->assertFalse(Ean::isValid('96385075'));

        // wrong length
        $this->assertFalse(Ean::isValid('400638'));
        $this->assertFalse(Ean::isValid('40063813339311'));

        // not a number at all
        $this->assertFalse(Ean::isValid('abcdefgh'));
        $this->assertFalse(Ean::isValid(''));
    }

    /**
     * The generated barcodes must validate against the calculator.
     *
     * The weight sequence used while generating an EAN-8 used to be the one for
     * EAN-13, so every generated EAN-8 carried a wrong check digit.
     *
     * @group system
     */
    public function testGeneratedBarcodesAreValid()
    {
        $faker = Factory::create('en');

        for ($i = 0; $i < 25; $i++) {
            $ean13 = $faker->ean13;
            $ean8 = $faker->ean8;
            $isbn13 = $faker->isbn13;

            $this->assertEquals(13, strlen($ean13));
            $this->assertEquals(8, strlen($ean8));

            $this->assertTrue(Ean::isValid($ean13), 'ean13: ' . $ean13);
            $this->assertTrue(Ean::isValid($ean8), 'ean8: ' . $ean8);
            $this->assertTrue(Ean::isValid($isbn13), 'isbn13: ' . $isbn13);
        }
    }

    /**
     * ISBN-10 carries its own checksum, which may end with an 'X'.
     *
     * @group system
     */
    public function testGeneratedIsbn10()
    {
        $faker = Factory::create('en');

        for ($i = 0; $i < 25; $i++) {
            $isbn = $faker->isbn10;

            $this->assertEquals(10, strlen($isbn));
            $this->assertRegExp('/^[0-9]{9}[0-9X]$/', $isbn);

            $digits = str_split(substr($isbn, 0, 9));
            $sum = 0;

            foreach ($digits as $position => $digit) {
                $sum += (10 - $position) * $digit;
            }

            $check = (11 - $sum % 11) % 11;
            $check = ($check < 10) ? (string) $check : 'X';

            $this->assertEquals($check, substr($isbn, -1), $isbn);
        }
    }

    // -------------------------------------------------------------------------
    // Inn
    // -------------------------------------------------------------------------

    /**
     * Test for Inn::checksum().
     *
     * @group system
     */
    public function testInnChecksum()
    {
        $this->assertEquals('9', Inn::checksum('50010073225'));
        $this->assertEquals('3', Inn::checksum('783000229'));
    }

    /**
     * Test for Inn::isValid().
     *
     * @group system
     */
    public function testInnIsValid()
    {
        $this->assertTrue(Inn::isValid('7830002293'));
        $this->assertFalse(Inn::isValid('7830002294'));
    }

    // -------------------------------------------------------------------------
    // Valid
    // -------------------------------------------------------------------------

    /**
     * Only values accepted by the validator come out.
     *
     * @group system
     */
    public function testValidOnlyReturnsAcceptedValues()
    {
        $faker = Factory::create('en');

        $even = new Valid($faker, function ($number) {
            return 0 === $number % 2;
        });

        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(0, $even->numberBetween(1, 100) % 2);
        }
    }

    /**
     * Property access goes through the same path.
     *
     * @group system
     */
    public function testValidWorksThroughPropertyAccess()
    {
        $faker = Factory::create('en');

        $short = new Valid($faker, function ($value) {
            return is_string($value) && strlen($value) < 40;
        });

        $this->assertLessThan(40, strlen($short->name));
    }

    /**
     * Without a validator every value passes.
     *
     * @group system
     */
    public function testValidWithoutValidator()
    {
        $faker = Factory::create('en');
        $any = new Valid($faker);

        $this->assertInternalType('integer', $any->numberBetween(1, 10));
    }

    /**
     * A validator that never accepts anything must give up.
     *
     * @group system
     *
     * @expectedException OverflowException
     */
    public function testValidGivesUpAfterMaxRetries()
    {
        $faker = Factory::create('en');

        $impossible = new Valid($faker, function () {
            return false;
        }, 5);

        $impossible->numberBetween(1, 10);
    }

    /**
     * A non callable validator is refused.
     *
     * @group system
     *
     * @expectedException InvalidArgumentException
     */
    public function testValidRejectsNonCallableValidator()
    {
        new Valid(Factory::create('en'), 'not a callable at all');
    }
}
