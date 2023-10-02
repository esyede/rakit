<?php

defined('DS') or exit('No direct access.');

use System\RSA;

class RSATest extends \PHPUnit_Framework_TestCase
{
    private $plain = 'foobar';
    private $encrypted;

    /**
     * Setup.
     */
    public function setUp()
    {
        if (is_file($file = path('storage') . 'rsa-private.key')) {
            unlink($file);
        }

        if (is_file($file = path('storage') . 'rsa-public.key')) {
            unlink($file);
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (is_file($file = path('storage') . 'rsa-private.key')) {
            unlink($file);
        }

        if (is_file($file = path('storage') . 'rsa-public.key')) {
            unlink($file);
        }
    }

    /**
     * Test untuk method RSA::encrypt() dan RSA::decrypt().
     *
     * @group system
     */
    public function testEncryptDecrypt()
    {
        $encrypted = RSA::encrypt('foobar');
        $this->assertTrue(is_string($encrypted) && strlen($encrypted) > 0);
        $this->assertTrue('foobar' === RSA::decrypt($encrypted));
    }
}
