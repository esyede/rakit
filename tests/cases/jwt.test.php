<?php

defined('DS') or exit('No direct script access.');

class JWTTest extends \PHPUnit_Framework_TestCase
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

    /**
     * Test hanya izinkan karakter URL-friendly
     *
     * @group system
     */
    public function testUrlSafeCharacters()
    {
        $encoded = JWT::encode(['foo' => 'f?'], 'secret');
        $this->assertEquals('f?', JWT::decode($encoded, 'secret')->foo);
    }

    /**
     * Test hanya izinkan UTF-8
     *
     * @group system
     */
    public function testMalformedUtf8StringsFail()
    {
        try {
            JWT::encode(['foo' => pack('c', 128)], 'secret');
        } catch (\Exception $e) {
            $this->assertEquals('Malformed UTF-8 characters', $e->getMessage());
        }
    }

    /**
     * Test expired token gagal
     *
     * @group system
     */
    public function testExpiredToken()
    {
        try {
            $payloads = ['foo' => 'bar', 'exp' => time() - 1000];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, 'secret');
        } catch (\Exception $e) {
            $this->assertEquals('Expired token', $e->getMessage());
        }
    }

    /**
     * Test invalid token dengan nbf gagal
     *
     * @group system
     */
    public function testBeforeValidTokenWithNbf()
    {
        try {
            $payloads = ['foo' => 'bar', 'nbf' => time() + 20];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, 'secret');
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        }
    }

    /**
     * Test invalid token dengan iat gagal
     *
     * @group system
     */
    public function testBeforeValidTokenWithIat()
    {
        try {
            $payloads = ['foo' => 'bar', 'iat' => time() + 20];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, 'secret');
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        }
    }

    /**
     * Test valid token sukses
     *
     * @group system
     */
    public function testValidToken()
    {
        $payloads = ['foo' => 'bar', 'exp' => time() + JWT::$leeway + 20];
        $encoded = JWT::encode($payloads, 'secret');
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);

        JWT::$leeway = 0;
    }

    /**
     * Test valid token dengan leeway sukses
     *
     * @group system
     */
    public function testValidTokenWithLeeway()
    {
        JWT::$leeway = 60;

        $payloads = ['foo' => 'bar', 'exp' => time() - 20];
        $encoded = JWT::encode($payloads, 'secret');
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);

        JWT::$leeway = 0;
    }

    /**
     * Test invalid token dengan leeway gagal
     *
     * @group system
     */
    public function testExpiredTokenWithLeeway()
    {
        JWT::$leeway = 60;

        try {
            $payloads = ['foo' => 'bar', 'exp' => time() - 70];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, 'secret');
        } catch (\Exception $e) {
            $this->assertEquals('Expired token', $e->getMessage());
        }

        JWT::$leeway = 0;
    }

    /**
     * Test valid token dengan nbf sukses
     *
     * @group system
     */
    public function testValidTokenWithNbf()
    {
        $payloads = ['foo' => 'bar', 'iat' => time(), 'exp' => time() + 20, 'nbf' => time() - 20];
        $encoded = JWT::encode($payloads, 'secret');
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);
    }

    /**
     * Test valid token dengan nbf & leeway sukses
     *
     * @group system
     */
    public function testValidTokenWithNbfLeeway()
    {
        JWT::$leeway = 60;

        $payloads = ['foo' => 'bar', 'nbf' => time() + 20];
        $encoded = JWT::encode($payloads, 'secret');
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);

        JWT::$leeway = 0;
    }

    /**
     * Test invalid token dengan nbf & leeway gagal
     *
     * @group system
     */
    public function testInvalidTokenWithNbfLeeway()
    {
        JWT::$leeway = 60;

        try {
            $payloads = ['foo' => 'bar', 'nbf' => time() + 65];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, 'secret');
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        }

        JWT::$leeway = 0;
    }

    /**
     * Test valid token dengan nbf & iat sukses
     *
     * @group system
     */
    public function testValidTokenWithIatLeeway()
    {
        JWT::$leeway = 60;

        $payloads = ['foo' => 'bar', 'iat' => time() + 20];
        $encoded = JWT::encode($payloads, 'secret');
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);

        JWT::$leeway = 0;
    }

    /**
     * Test invalid token dengan iat & leeway gagal
     *
     * @group system
     */
    public function testInvalidTokenWithIatLeeway()
    {
        JWT::$leeway = 60;

        try {
            $payloads = ['foo' => 'bar', 'iat' => time() + 65];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, 'secret');
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        }

        JWT::$leeway = 0;
    }

    /**
     * Test invalid token gagal
     *
     * @group system
     */
    public function testInvalidToken()
    {
        try {
            $payloads = ['foo' => 'bar', 'exp' => time() + 20];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, 'qwerty');
        } catch (\Exception $e) {
            $this->assertEquals('Signature verification failed', $e->getMessage());
        }
    }

    /**
     * Test invalid key/secret gagal
     *
     * @group system
     */
    public function testNullKeyFails()
    {
        try {
            $payloads = ['foo' => 'bar', 'exp' => time() + JWT::$leeway + 20];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, null);
        } catch (\Exception $e) {
            $this->assertEquals('Secret cannot be empty', $e->getMessage());
        }
    }

    /**
     * Test key/secret kosong gagal
     *
     * @group system
     */
    public function testEmptyKeyFails()
    {
        try {
            $payloads = ['foo' => 'bar', 'exp' => time() + JWT::$leeway + 20];
            $encoded = JWT::encode($payloads, 'secret');
            JWT::decode($encoded, '');
        } catch (\Exception $e) {
            $this->assertEquals('Secret cannot be empty', $e->getMessage());
        }
    }

    /**
     * Test invalid algorithm gagal
     *
     * @group system
     */
    public function testInvalidAlgorithm()
    {
        try {
            $encoded = JWT::encode(['foo' => 'bar'], 'secret', null);
            JWT::decode($encoded, 'secret', null);
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Only these algorithm are supported:'));
        }
    }

    /**
     * Test header tambahan sukses
     *
     * @group system
     */
    public function testAdditionalHeaders()
    {
        $encoded = JWT::encode(['foo' => 'bar'], 'secret', 'HS256', ['cty' => 'test-eit;v=1']);
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);
    }

    /**
     * Test invalid segment count gagal
     *
     * @group system
     */
    public function testInvalidSegmentCount()
    {
        try {
            JWT::decode('brokenheader.brokenbody', 'secret');
        } catch (\Exception $e) {
            $this->assertEquals('Wrong number of segments', $e->getMessage());
        }
    }

    /**
     * Test invalid signature encoding gagal
     *
     * @group system
     */
    public function testInvalidSignatureEncoding()
    {
        try {
            $encoded = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.'
                .'eyJpZCI6MSwibmFtZSI6ImZvbyJ9.'
                .'Q4Kee9E8o0Xfo4ADXvYA8t7dN_X_bU9K5w6tXuiSjlUxx';
            JWT::decode($encoded, 'qwerty');
        } catch (\Exception $e) {
            $this->assertTrue(
                'Invalid signature encoding' === $e->getMessage()
                    || 'Signature verification failed' === $e->getMessage()
            );
        }
    }

    /**
     * Test payload array kososng sukses
     *
     * @group system
     */
    public function testDecodesEmptyArrayAsObject()
    {
        $encoded = JWT::encode([], 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertTrue(
            $decoded instanceof \stdClass
            && isset($decoded->exp)
            && isset($decoded->jti)
            && isset($decoded->iat)
            && count(get_object_vars($decoded)) === 3
        );
    }
}
