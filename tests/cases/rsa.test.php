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
        // Reset static details for isolation
        $reflection = new \ReflectionClass('System\RSA');
        $details = $reflection->getProperty('details');
        /** @disregard */
        $details->setAccessible(true);
        $details->setValue([
            'public_key' => null,
            'private_key' => null,
            'config' => null,
            'options' => [],
        ]);

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
     * Test for RSA::encrypt() and RSA::decrypt().
     *
     * @group system
     */
    public function testEncryptDecrypt()
    {
        $encrypted = RSA::encrypt('foobar');
        $this->assertTrue(is_string($encrypted) && strlen($encrypted) > 0);
        $this->assertTrue('foobar' === RSA::decrypt($encrypted));
    }

    /**
     * Test encrypt/decrypt with large data.
     *
     * @group system
     */
    public function testEncryptDecryptLargeData()
    {
        $large_data = str_repeat('A', 1000);
        $encrypted = RSA::encrypt($large_data);
        $this->assertTrue(is_string($encrypted) && strlen($encrypted) > 0);
        $this->assertEquals($large_data, RSA::decrypt($encrypted));
    }

    /**
     * Test encrypt/decrypt with OAEP padding.
     *
     * @group system
     */
    public function testEncryptDecryptWithOAEP()
    {
        $data = 'test data';
        $encrypted = RSA::encrypt($data, OPENSSL_PKCS1_OAEP_PADDING);
        $this->assertTrue(is_string($encrypted) && strlen($encrypted) > 0);
        $this->assertEquals($data, RSA::decrypt($encrypted, OPENSSL_PKCS1_OAEP_PADDING));
    }

    /**
     * Test load_keys and export.
     *
     * @group system
     */
    public function testLoadKeysAndExport()
    {
        // Generate keys first
        RSA::encrypt('dummy');

        $private = RSA::export_private();
        $public = RSA::export_public();

        $this->assertTrue(is_string($private) && strlen($private) > 0);
        $this->assertTrue(is_string($public) && strlen($public) > 0);

        // Reset and load
        $reflection = new \ReflectionClass('System\RSA');
        $details = $reflection->getProperty('details');
        /** @disregard */
        $details->setAccessible(true);
        $details->setValue([
            'public_key' => null,
            'private_key' => null,
            'config' => null,
            'options' => [],
        ]);

        RSA::load_keys($private, $public);

        $data = 'test load';
        $encrypted = RSA::encrypt($data);
        $this->assertEquals($data, RSA::decrypt($encrypted));
    }

    /**
     * Test load_keys without public key.
     *
     * @group system
     */
    public function testLoadKeysWithoutPublic()
    {
        // Generate keys first
        RSA::encrypt('dummy');
        $private = RSA::export_private();

        // Reset
        $reflection = new \ReflectionClass('System\RSA');
        $details = $reflection->getProperty('details');
        /** @disregard */
        $details->setAccessible(true);
        $details->setValue([
            'public_key' => null,
            'private_key' => null,
            'config' => null,
            'options' => [],
        ]);

        RSA::load_keys($private);

        $data = 'test load without public';
        $encrypted = RSA::encrypt($data);
        $this->assertEquals($data, RSA::decrypt($encrypted));
    }

    /**
     * Test invalid private key in load_keys.
     *
     * @group system
     */
    public function testLoadKeysInvalidPrivate()
    {
        try {
            RSA::load_keys('invalid key');
        } catch (\Throwable $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Invalid private key'));
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Invalid private key'));
        }
    }

    /**
     * Test details method.
     *
     * @group system
     */
    public function testDetails()
    {
        $details = RSA::details();
        $this->assertTrue(is_array($details));
        $this->assertArrayHasKey('public_key', $details);
        $this->assertArrayHasKey('private_key', $details);
    }
}
