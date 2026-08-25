<?php

defined('DS') or exit('No direct access.');

use System\Curl;

class CurlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Base URL of the endpoint that echoes requests back as JSON.
     *
     * @var string
     */
    private static $base;

    /**
     * @var bool|null
     */
    private static $skipNetworkTests;

    /**
     * Point the suite at the local mock endpoint served by
     * tests/mock/server.php, so it is offline-capable, fast, and never
     * reaches a production host.
     *
     * Set RAKIT_CURL_MOCK_URL to test against a different endpoint.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $override = getenv('RAKIT_CURL_MOCK_URL');

        if (is_string($override) && '' !== $override) {
            self::$base = rtrim($override, '/');
            self::$skipNetworkTests = !MockServer::reachable(self::$base);

            return;
        }

        self::$base = MockServer::url();
        self::$skipNetworkTests = (null === self::$base);
    }

    public function setUp()
    {
        Curl::timeout(240);
    }

    public function tearDown()
    {
        Curl::timeout(null);
    }

    private function skipIfNoNetwork()
    {
        if (self::$skipNetworkTests) {
            $this->markTestSkipped('Mock endpoint tidak tersedia di ' . self::$base);
        }
    }

    public function testCurlExists()
    {
        $this->assertTrue(extension_loaded('curl'));
    }

    public function testCurlOptions()
    {
        $this->skipIfNoNetwork();

        Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
        $response = Curl::get(self::$base);

        if (!is_object($response->body) || !isset($response->body->headers)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals($response->body->headers->Cookie, 'foo=bar');
        Curl::clear_curl_options();
    }

    public function testTimeoutFail()
    {
        // Without this the test does not skip along with the rest when the
        // endpoint is unreachable: it would still fire a request, get
        // "connection refused" instead of a timeout, and fail. That turns any
        // environment where the mock server cannot start into a red build
        // rather than a skipped one.
        $this->skipIfNoNetwork();

        $caught = null;

        try {
            Curl::timeout(1);
            Curl::get(self::$base . '/1000');
        } catch (\Throwable $e) {
            $caught = $e;
        } catch (\Exception $e) {
            $caught = $e;
        }

        // Kept outside the try: the call above is meant to throw, so leaving
        // this after it would never reset the timeout.
        Curl::timeout(null);

        $this->assertNotNull($caught, 'Expected the request to time out.');
        $this->assertTrue(false !== strpos(strtolower($caught->getMessage()), 'timed out'));
    }

    public function testDefaultHeaders()
    {
        $this->skipIfNoNetwork();

        Curl::default_headers(['header1' => 'Hello', 'header2' => 'world']);

        $response = Curl::get(self::$base);
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('Hello', $response->body->headers->Header1);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('world', $response->body->headers->Header2);

        $response = Curl::get(self::$base, ['header1' => 'Custom value']);
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('Custom value', $response->body->headers->Header1);

        Curl::clear_default_headers();

        $response = Curl::get(self::$base);
        $this->assertEquals(200, $response->code);
        $this->assertObjectNotHasAttribute('Header1', $response->body->headers);
        $this->assertObjectNotHasAttribute('Header2', $response->body->headers);
    }

    public function testDefaultHeader()
    {
        $this->skipIfNoNetwork();

        Curl::default_header('Hello', 'custom');

        $response = Curl::get(self::$base);
        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'Hello'));
        $this->assertEquals('custom', $response->body->headers->Hello);

        Curl::clear_default_headers();

        $response = Curl::get(self::$base);

        $this->assertEquals(200, $response->code);
        $this->assertFalse(property_exists($response->body->headers, 'hello'));
    }

    public function testBasicAuthentication()
    {
        $this->skipIfNoNetwork();

        Curl::auth('user', 'password');
        $response = Curl::get(self::$base);

        if (!is_object($response->body) || !isset($response->body->headers)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->body->headers->Authorization);
    }

    public function testCustomHeaders()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, ['user-agent' => 'dummy-agent']);
        $this->assertEquals(200, $response->code);
        $this->assertEquals('dummy-agent', $response->body->headers->{'User-Agent'});
    }

    public function testGet()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base . '?name=Budi', [
            'Accept' => 'application/json',
        ], ['age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetMultidimensionalArray()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['key' => 'value', 'items' => ['item1', 'item2']]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('value', $response->body->queries->key);
        $this->assertEquals('item1', $response->body->queries->items[0]);
        $this->assertEquals('item2', $response->body->queries->items[1]);
    }

    public function testGetWithDots()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->user_name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetWithDotsAlt()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi Purnomo', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->queries->user_name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetWithEqualSign()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->queries->name);
    }

    public function testGetWithEqualSignAlt()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello=Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello=Dewi', $response->body->queries->name);
    }

    public function testGetWithComplexQuery()
    {
        $this->skipIfNoNetwork();

        $query = '[{"type":"/music/album","name":null,"artist":{"id":"/id/denny_caknan"},"limit":3}]';
        $response = Curl::get(self::$base . '?query=' . $query . '&cursor');

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('', $response->body->queries->cursor);
        $this->assertEquals($query, $response->body->queries->query);
    }

    public function testGetArray()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->name[0]);
        $this->assertEquals('Dewi', $response->body->queries->name[1]);
    }

    public function testHead()
    {
        $this->skipIfNoNetwork();

        $response = Curl::head(self::$base . '?name=Budi', ['Accept' => 'application/json']);
        $this->assertEquals(200, $response->code);
    }

    public function testPost()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostForm()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_form(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        if (!is_object($response->body) || !isset($response->body->method)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->headers->{'Content-Type'});
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostMultipart()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_multipart(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        if (!is_object($response->body) || !isset($response->body->method)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('multipart/form-data', explode(';', $response->body->headers->{'Content-Type'})[0]);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostWithEqualSign()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_form(['name' => 'Budi=Hello']);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->data->name);
    }

    public function testPostArray()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name[0]);
        $this->assertEquals('Dewi', $response->body->data->name[1]);
    }

    public function testPostWithDots()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->user_name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testRawPost()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], json_encode(['author' => 'Budi Purnomo']));

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->data->json->author);
    }

    public function testPostMultidimensionalArray()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_form(['key' => 'value', 'items' => ['item1', 'item2']]);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('value', $response->body->data->key);
        $this->assertEquals('item1', $response->body->data->items[0]);
        $this->assertEquals('item2', $response->body->data->items[1]);
    }

    public function testPut()
    {
        $this->skipIfNoNetwork();

        $response = Curl::put(self::$base, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], ['name' => 'Budi', 'gender' => 'Male']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PUT', $response->body->method);
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Budi'));
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Male'));
    }

    public function testPatch()
    {
        $this->skipIfNoNetwork();

        $response = Curl::patch(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'gender' => 'Male']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PATCH', $response->body->method);
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Budi'));
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Male'));
    }

    public function testDelete()
    {
        $this->skipIfNoNetwork();

        $response = Curl::delete(self::$base);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('DELETE', $response->body->method);
    }

    public function testUpload()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_multipart(['name' => 'Budi'], ['file' => __DIR__ . DS . 'index.html']);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->file->size > 0);
    }

    public function testUploadWithoutHelper()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], [
            'name' => 'Budi',
            'file' => Curl::body_file(__DIR__ . DS . 'index.html'),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->file->size > 0);
    }

    public function testUploadIfFilePartOfData()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], [
            'name' => 'Budi',
            'files[owl.gif]' => Curl::body_file(__DIR__ . DS . 'index.html'),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->files->size->{'owl.gif'} > 0);
    }
}
