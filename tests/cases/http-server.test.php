<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Server;

class HttpHServerTest extends \PHPUnit_Framework_TestCase
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

    public function testShouldExtractHeadersFromServer()
    {
        $server = [
            'SOME_SERVER_VARIABLE' => 'value',
            'SOME_SERVER_VARIABLE2' => 'value',
            'ROOT' => 'value',
            'HTTP_CONTENT_TYPE' => 'text/html',
            'HTTP_CONTENT_LENGTH' => '0',
            'HTTP_ETAG' => 'asdf',
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar',
        ];

        $bag = new Server($server);
        $this->assertEquals([
            'CONTENT_TYPE' => 'text/html',
            'CONTENT_LENGTH' => '0',
            'ETAG' => 'asdf',
            'AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar',
        ], $bag->getHeaders());
    }

    public function testHttpPasswordIsOptional()
    {
        $bag = new Server(['PHP_AUTH_USER' => 'foo']);
        $this->assertEquals([
            'AUTHORIZATION' => 'Basic ' . base64_encode('foo:'),
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => '',
        ], $bag->getHeaders());
    }

    public function testHttpBasicAuthWithPhpCgi()
    {
        $bag = new Server(['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar')]);
        $this->assertEquals([
            'AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar',
        ], $bag->getHeaders());
    }

    public function testHttpBasicAuthWithPhpCgiRedirect()
    {
        $bag = new Server(['REDIRECT_HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar')]);
        $this->assertEquals([
            'AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => 'bar',
        ], $bag->getHeaders());
    }

    public function testHttpBasicAuthWithPhpCgiEmptyPassword()
    {
        $bag = new Server(['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:')]);
        $this->assertEquals([
            'AUTHORIZATION' => 'Basic ' . base64_encode('foo:'),
            'PHP_AUTH_USER' => 'foo',
            'PHP_AUTH_PW' => '',
        ], $bag->getHeaders());
    }

    public function testOAuthBearerAuth()
    {
        $bearer = 'Bearer L-yLEOr9zhmUYRkzN1jwwxwQ-PBNiKDc8dgfB4hTfvo';
        $bag = new Server(['HTTP_AUTHORIZATION' => $bearer]);
        $this->assertEquals(['AUTHORIZATION' => $bearer], $bag->getHeaders());
    }

    public function testHttpBasicAuthWithPasswordContainingColon()
    {
        // RFC 7617 forbids ':' in the user-id but allows it in the password, so
        // only the first one separates the two.
        $header = 'Basic ' . base64_encode('bob:pa:ss');
        $bag = new Server(['HTTP_AUTHORIZATION' => $header]);
        $headers = $bag->getHeaders();

        $this->assertArrayHasKey('PHP_AUTH_USER', $headers);
        $this->assertArrayHasKey('PHP_AUTH_PW', $headers);
        $this->assertEquals('bob', $headers['PHP_AUTH_USER']);
        $this->assertEquals('pa:ss', $headers['PHP_AUTH_PW']);
    }
}
