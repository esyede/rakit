<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Calculator\Iban;
use System\Foundation\Faker\Calculator\Tcno;

class FakerCalculatorsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // ..
    }

    public function tearDown()
    {
        // ..
    }

    // -------------------------------------------------------------------------
    // Iban::checksum()
    // -------------------------------------------------------------------------

    /**
     * Test for Iban::checksum() - returns two-digit checksum.
     *
     * @group system
     */
    public function testIbanChecksumReturnsTwoDigits()
    {
        // GB IBAN prefix example: GB??NWBK60161331926819
        $result = Iban::checksum('GB' . '00' . 'NWBK60161331926819');
        $this->assertRegExp('/^\d{2}$/', $result);
    }

    /**
     * Test for Iban::checksum() - known IBAN GB29 checksum is 29.
     *
     * @group system
     */
    public function testIbanChecksumKnownValue()
    {
        $result = Iban::checksum('GB29NWBK60161331926819');
        $this->assertEquals('29', $result);
    }

    /**
     * Test for Iban::alphaToNumber() - converts letter to number.
     *
     * @group system
     */
    public function testIbanAlphaToNumberConvertsLetterToNumber()
    {
        $this->assertEquals(10, Iban::alphaToNumber('A'));
        $this->assertEquals(11, Iban::alphaToNumber('B'));
        $this->assertEquals(35, Iban::alphaToNumber('Z'));
    }

    /**
     * Test for Iban::mod97() - returns modulo 97 of large number.
     *
     * @group system
     */
    public function testIbanMod97ReturnsCorrectRemainder()
    {
        $this->assertEquals(1, Iban::mod97('98'));
        $this->assertEquals(0, Iban::mod97('97'));
        $this->assertEquals(1, Iban::mod97('1'));
    }

    /**
     * Test for Iban::isValid() - returns true for valid IBAN.
     *
     * @group system
     */
    public function testIbanIsValidReturnsTrueForValidIban()
    {
        $this->assertTrue(Iban::isValid('GB29NWBK60161331926819'));
    }

    /**
     * Test for Iban::isValid() - returns false for invalid IBAN.
     *
     * @group system
     */
    public function testIbanIsValidReturnsFalseForInvalidIban()
    {
        $this->assertFalse(Iban::isValid('GB00NWBK60161331926819'));
    }

    // -------------------------------------------------------------------------
    // Tcno::checksum()
    // -------------------------------------------------------------------------

    /**
     * Test for Tcno::checksum() - returns two-digit checksum.
     *
     * @group system
     */
    public function testTcnoChecksumReturnsTwoDigits()
    {
        $result = Tcno::checksum('123456789');
        $this->assertRegExp('/^\d{2}$/', $result);
    }

    /**
     * Test for Tcno::checksum() - known value.
     *
     * @group system
     */
    public function testTcnoChecksumKnownValue()
    {
        // 10004658756 is a known valid TCNO: prefix=100046587, checksum=56
        $checksum = Tcno::checksum('100046587');
        $this->assertEquals('56', $checksum);
    }

    /**
     * Test for Tcno::checksum() - throws for wrong length.
     *
     * @group system
     */
    public function testTcnoChecksumThrowsForWrongLength()
    {
        $caught = false;
        try {
            Tcno::checksum('12345');
        } catch (\Exception $e) {
            $caught = true;
            $this->assertContains('9 digits', $e->getMessage());
        }
        $this->assertTrue($caught);
    }

    /**
     * Test for Tcno::isValid() - returns true for valid TCNO.
     *
     * @group system
     */
    public function testTcnoIsValidReturnsTrueForValidTcno()
    {
        // prefix=100046587 → checksum=56 → valid TCNO is 10004658756
        $this->assertTrue(Tcno::isValid('10004658756'));
    }

    /**
     * Test for Tcno::isValid() - returns false for invalid TCNO.
     *
     * @group system
     */
    public function testTcnoIsValidReturnsFalseForInvalidTcno()
    {
        $this->assertFalse(Tcno::isValid('10004658700'));
    }
}
