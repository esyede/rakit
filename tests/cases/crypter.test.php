rakit\tests\cases\crypter.test.php
<?php

defined('DS') or exit('No direct access.');

use System\Crypter;

class CrypterTest extends \PHPUnit_Framework_TestCase
{
    private $key = 'test_key_for_crypter';

    /**
     * Setup.
     */
    public function setUp()
    {
        if (!defined('RAKIT_KEY')) {
            define('RAKIT_KEY', $this->key);
        }
    }

    /**
     * Test encrypt/decrypt.
     *
     * @group system
     */
    public function testEncryptDecrypt()
    {
        $data = 'hello world';
        $encrypted = Crypter::encrypt($data);
        $this->assertTrue(is_string($encrypted) && strlen($encrypted) > 0);
        $this->assertEquals($data, Crypter::decrypt($encrypted));
    }

    /**
     * Test encrypt/decrypt empty string.
     *
     * @group system
     */
    public function testEncryptDecryptEmpty()
    {
        $data = '';
        $encrypted = Crypter::encrypt($data);
        $this->assertEquals($data, Crypter::decrypt($encrypted));
    }

    /**
     * Test equals method.
     *
     * @group system
     */
    public function testEquals()
    {
        $str1 = 'test';
        $str2 = 'test';
        $str3 = 'different';
        $this->assertTrue(Crypter::equals($str1, $str2));
        $this->assertFalse(Crypter::equals($str1, $str3));
        $this->assertFalse(Crypter::equals($str1, ''));
        $this->assertFalse(Crypter::equals('', $str1));
        $this->assertFalse(Crypter::equals($str1, null));
    }

    /**
     * Test invalid decrypt.
     *
     * @group system
     */
    public function testInvalidDecrypt()
    {
        try {
            Crypter::decrypt('invalid');
        } catch (\Throwable $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'The payload is invalid') || 0 === strpos($e->getMessage(), 'Could not decrypt'));
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'The payload is invalid') || 0 === strpos($e->getMessage(), 'Could not decrypt'));
        }
    }

    /**
     * Test tampered data.
     *
     * @group system
     */
    public function testTamperedData()
    {
        $data = 'secret';
        $encrypted = Crypter::encrypt($data);
        $tampered = substr($encrypted, 0, -1) . 'x'; // Tamper last char

        try {
            Crypter::decrypt($tampered);
        } catch (\Throwable $e) {
            $this->assertTrue($e->getMessage() === 'The payload is invalid.' || $e->getMessage() === 'The MAC is invalid.');
        } catch (\Exception $e) {
            $this->assertTrue($e->getMessage() === 'The payload is invalid.' || $e->getMessage() === 'The MAC is invalid.');
        }
    }
}
