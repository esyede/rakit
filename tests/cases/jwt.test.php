<?php

defined('DS') or exit('No direct access.');

use System\JWT;

class JWTTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeDecode()
    {
        $encoded = JWT::encode(['foo' => 'f?'], 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertEquals('f?', $decoded->foo);
    }

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

    public function testValidToken()
    {
        $payloads = ['foo' => 'bar', 'exp' => time() + JWT::$leeway + 20];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertEquals('bar', $decoded->foo);
    }

    public function testValidTokenWithLeeway()
    {
        JWT::$leeway = 60;
        $payloads = ['foo' => 'bar'];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertEquals('bar', $decoded->foo);
        JWT::$leeway = 0;
    }

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

    public function testValidTokenWithNbf()
    {
        $payload = ['foo' => 'bar', 'iat' => time(), 'exp' => time() + 20, 'nbf' => time() - 20];
        $encoded = JWT::encode($payload, 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertEquals($decoded->foo, 'bar');
    }

    public function testValidTokenWithNbfLeeway()
    {
        JWT::$leeway = 60;
        $payload = ['foo' => 'bar', 'nbf' => time() + 20];
        $encoded = JWT::encode($payload, 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertEquals($decoded->foo, 'bar');
        JWT::$leeway = 0;
    }

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

    public function testValidTokenWithIatLeeway()
    {
        JWT::$leeway = 60;
        $payloads = ['foo' => 'bar', 'iat' => time() + 20];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertEquals('bar', $decoded->foo);
        JWT::$leeway = 0;
    }

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

    public function testNullKeyFails()
    {
        try {
            $payloads = ['foo' => 'bar', 'exp' => time() + JWT::$leeway + 20];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, null);
        } catch (\Throwable $e) {
            $this->assertEquals('Key cannot be empty or non-string value', $e->getMessage());
        } catch (\Exception $e) {
            $this->assertEquals('Key cannot be empty or non-string value', $e->getMessage());
        }
    }

    public function testEmptyKeyFails()
    {
        try {
            $payloads = ['foo' => 'bar', 'exp' => time() + JWT::$leeway + 20];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, '');
        } catch (\Throwable $e) {
            $this->assertEquals('Key cannot be empty or non-string value', $e->getMessage());
        } catch (\Exception $e) {
            $this->assertEquals('Key cannot be empty or non-string value', $e->getMessage());
        }
    }

    public function testInvalidAlgorithm()
    {
        try {
            $encoded = JWT::encode(['foo' => 'bar'], 'secret', [], 'UNSUPPORTED');
        } catch (\Throwable $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'Only these algorithms are supported:'));
        } catch (\Exception $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'Only these algorithms are supported:'));
        }
    }

    public function testAdditionalHeaders()
    {
        $encoded = JWT::encode(['foo' => 'bar'], 'secret', ['cty' => 'test-eit;v=1']);
        $this->assertEquals('bar', JWT::decode($encoded, 'secret')->foo);
    }

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

    public function testInvalidSignatureEncoding()
    {
        try {
            $encoded = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwibmFtZSI6ImZvbyJ9.Q4Kee9E8o0Xfo4ADXvYA8t7dN_X_bU9K5w6tXuiSjlUxx';
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

    public function testDecodesEmptyArrayAsObject()
    {
        $payloads = [];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret');
        $this->assertTrue(count(get_object_vars($decoded)) === count($payloads));
    }

    public function testValidTokenWithAudIss()
    {
        $payloads = ['foo' => 'bar', 'aud' => 'expected_aud', 'iss' => 'expected_iss'];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret', ['aud' => 'expected_aud', 'iss' => 'expected_iss']);
        $this->assertEquals('bar', $decoded->foo);
    }

    public function testInvalidAud()
    {
        try {
            $payloads = ['foo' => 'bar', 'aud' => 'wrong_aud'];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, 'secret', ['aud' => 'expected_aud']);
        } catch (\Throwable $e) {
            $this->assertEquals('Invalid audience', $e->getMessage());
        } catch (\Exception $e) {
            $this->assertEquals('Invalid audience', $e->getMessage());
        }
    }

    public function testInvalidIss()
    {
        try {
            $payloads = ['foo' => 'bar', 'iss' => 'wrong_iss'];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, 'secret', ['iss' => 'expected_iss']);
        } catch (\Throwable $e) {
            $this->assertEquals('Invalid issuer', $e->getMessage());
        } catch (\Exception $e) {
            $this->assertEquals('Invalid issuer', $e->getMessage());
        }
    }

    public function testCustomValidatorSuccess()
    {
        $payloads = ['foo' => 'bar', 'sub' => 'user123'];
        $encoded = JWT::encode($payloads, 'secret');
        $decoded = JWT::decode($encoded, 'secret', [
            'validator' => function($payloads, $headers) {
                if (!isset($payloads->sub)) {
                    throw new \Exception('Missing subject');
                }
            }
        ]);
        $this->assertEquals('bar', $decoded->foo);
    }

    public function testCustomValidatorFail()
    {
        try {
            $payloads = ['foo' => 'bar'];
            $encoded = JWT::encode($payloads, 'secret');
            $decoded = JWT::decode($encoded, 'secret', [
                'validator' => function($payloads, $headers) {
                    if (!isset($payloads->sub)) {
                        throw new \Exception('Missing subject');
                    }
                }
            ]);
        } catch (\Throwable $e) {
            $this->assertEquals('Missing subject', $e->getMessage());
        } catch (\Exception $e) {
            $this->assertEquals('Missing subject', $e->getMessage());
        }
    }

    public function testRefreshToken()
    {
        $payloads = ['foo' => 'bar', 'exp' => time() + 100];
        $encoded = JWT::encode($payloads, 'secret');
        $new_exp = time() + 200;
        $refreshed = JWT::refresh($encoded, 'secret', $new_exp);
        $decoded = JWT::decode($refreshed, 'secret');
        $this->assertEquals('bar', $decoded->foo);
        $this->assertEquals($new_exp, $decoded->exp);
    }
}

