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
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertEquals('f?', $decoded->foo);
    }

    /**
     * Test hanya izinkan UTF-8
     *
     * @group system
     */
    public function testMalformedUtf8StringsFail()
    {
        try {
            $encoded = JWT::encode(['foo' => pack('c', 128)], 'secret');
        } catch (\Throwable $e) {
            $this->assertTrue(
                'Malformed UTF-8 characters' === $e->getMessage()
                    || 'json_encode(): Invalid UTF-8 sequence in argument' === $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->assertTrue(
                'Malformed UTF-8 characters' === $e->getMessage()
                    || 'json_encode(): Invalid UTF-8 sequence in argument' === $e->getMessage()
            );
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
            $payloads = ['foo' => 'bar', 'exp' => time() - 20];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, 'secret');
        } catch (\Throwable $e) {
            $this->assertEquals('Expired token', $e->getMessage());
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
            $decoded = JWT::decode($encoded, 'secret');
        } catch (\Throwable $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
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
            $decoded = JWT::decode($encoded, 'secret');
        } catch (\Throwable $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
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
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertEquals('bar', $decoded->foo);
    }

    /**
     * Test valid token dengan leeway sukses
     *
     * @group system
     */
    public function testValidTokenWithLeeway()
    {
        JWT::$leeway = 60;

        $payloads = ['foo' => 'bar'];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertEquals('bar', $decoded->foo);

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
            JWT::$leeway = 0;
        } catch (\Throwable $e) {
            JWT::$leeway = 0;
            $this->assertEquals('Expired token', $e->getMessage());
        } catch (\Exception $e) {
            JWT::$leeway = 0;
            $this->assertEquals('Expired token', $e->getMessage());
        }
    }

    /**
     * Test valid token dengan nbf sukses
     *
     * @group system
     */
    public function testValidTokenWithNbf()
    {
        $payload = ['foo' => 'bar', 'iat' => time(), 'exp' => time() + 20, 'nbf' => time() - 20];
        $encoded = JWT::encode($payload, 'secret');
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertEquals($decoded->foo, 'bar');
    }

    /**
     * Test valid token dengan nbf & leeway sukses
     *
     * @group system
     */
    public function testValidTokenWithNbfLeeway()
    {
        JWT::$leeway = 60;

        $payload = ['foo' => 'bar', 'nbf' => time() + 20];
        $encoded = JWT::encode($payload, 'secret');
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertEquals($decoded->foo, 'bar');

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
            $decoded = JWT::decode($encoded, 'secret');
            JWT::$leeway = 0;
        } catch (\Throwable $e) {
            JWT::$leeway = 0;
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        } catch (\Exception $e) {
            JWT::$leeway = 0;
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        }
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
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertEquals('bar', $decoded->foo);

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
            $decoded = JWT::decode($encoded, 'secret');
            JWT::$leeway = 0;
        } catch (\Throwable $e) {
            JWT::$leeway = 0;
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        } catch (\Exception $e) {
            JWT::$leeway = 0;
            $this->assertTrue(0 === strpos($e->getMessage(), 'Cannot handle token prior to'));
        }
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
            $decoded = JWT::decode($encoded, 'qwerty');
        } catch (\Throwable $e) {
            $this->assertEquals('Signature verification failed', $e->getMessage());
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
            $decoded = JWT::decode($encoded, null);
        } catch (\Throwable $e) {
            $this->assertEquals('Secret cannot be empty', $e->getMessage());
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
            $decoded = JWT::decode($encoded, '');
        } catch (\Throwable $e) {
            $this->assertEquals('Secret cannot be empty', $e->getMessage());
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
            $encoded = JWT::encode(['foo' => 'bar'], 'secret', 'RS256'); // unsupported algo
        } catch (\Throwable $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Only these algorithms are supported:'));
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Only these algorithms are supported:'));
        }

        try {
            $encoded = JWT::encode(['foo' => 'bar'], 'secret', null);
            $decoded = JWT::decode($encoded, 'secret', null);
        } catch (\Throwable $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Only these algorithms are supported:'));
        } catch (\Exception $e) {
            $this->assertTrue(0 === strpos($e->getMessage(), 'Only these algorithms are supported:'));
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
            $decoded = JWT::decode('brokenheader.brokenbody', 'secret');
        } catch (\Throwable $e) {
            $this->assertEquals('Wrong number of segments', $e->getMessage());
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
            $decoded = JWT::decode($encoded, 'qwerty');
        } catch (\Throwable $e) {
            $this->assertTrue(
                'Invalid signature encoding' === $e->getMessage()
                    || 'Signature verification failed' === $e->getMessage()
            );
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
        $payloads = [];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret');

        $this->assertTrue(count(get_object_vars($decoded)) === count($payloads));
    }
}
