<?php

defined('DS') or exit('No direct access.');

use System\Curl;

class CurlTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Curl::timeout(240);
    }

    public function tearDown()
    {
        Curl::timeout(null);
    }

    public function testCurlExists()
    {
        $this->assertTrue(extension_loaded('curl'));
    }

    public function testCurlOptions()
    {
        Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
        $response = Curl::get('https://rakit.esyede.my.id/mock');
        $this->assertEquals($response->body->headers->Cookie, 'foo=bar');
        Curl::clear_curl_options();
    }

    public function testTimeoutFail()
    {
        try {
            Curl::timeout(1);
            Curl::get('https://rakit.esyede.my.id/mock/1000');
            Curl::timeout(null);
        } catch (\Throwable $e) {
            $this->assertTrue(false !== strpos(strtolower($e->getMessage()), 'timed out'));
        } catch (\Exception $e) {
            $this->assertTrue(false !== strpos(strtolower($e->getMessage()), 'timed out'));
        }
    }

    public function testDefaultHeaders()
    {
        Curl::default_headers(['header1' => 'Hello', 'header2' => 'world']);

        $response = Curl::get('https://rakit.esyede.my.id/mock');
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('Hello', $response->body->headers->Header1);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('world', $response->body->headers->Header2);

        $response = Curl::get('https://rakit.esyede.my.id/mock', ['header1' => 'Custom value']);
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('Custom value', $response->body->headers->Header1);

        Curl::clear_default_headers();

        $response = Curl::get('https://rakit.esyede.my.id/mock');
        $this->assertEquals(200, $response->code);
        $this->assertObjectNotHasAttribute('Header1', $response->body->headers);
        $this->assertObjectNotHasAttribute('Header2', $response->body->headers);
    }

    public function testDefaultHeader()
    {
        Curl::default_header('Hello', 'custom');

        $response = Curl::get('https://rakit.esyede.my.id/mock');
        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'Hello'));
        $this->assertEquals('custom', $response->body->headers->Hello);

        Curl::clear_default_headers();

        $response = Curl::get('https://rakit.esyede.my.id/mock');

        $this->assertEquals(200, $response->code);
        $this->assertFalse(property_exists($response->body->headers, 'hello'));
    }

    public function testBasicAuthentication()
    {
        Curl::auth('user', 'password');
        $response = Curl::get('https://rakit.esyede.my.id/mock');
        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->body->headers->Authorization);
    }

    public function testCustomHeaders()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock', ['user-agent' => 'dummy-agent']);
        $this->assertEquals(200, $response->code);
        $this->assertEquals('dummy-agent', $response->body->headers->{'User-Agent'});
    }

    public function testGet()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock?name=Budi', [
            'Accept' => 'application/json',
        ], ['age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetMultidimensionalArray()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock', [
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
        $response = Curl::get('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->user_name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetWithDotsAlt()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi Purnomo', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->queries->user_name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetWithEqualSign()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->queries->name);
    }

    public function testGetWithEqualSignAlt()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello=Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello=Dewi', $response->body->queries->name);
    }

    public function testGetWithComplexQuery()
    {
        $query = '[{"type":"/music/album","name":null,"artist":{"id":"/id/denny_caknan"},"limit":3}]';
        $response = Curl::get('https://rakit.esyede.my.id/mock?query=' . $query . '&cursor');

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('', $response->body->queries->cursor);
        $this->assertEquals($query, $response->body->queries->query);
    }

    public function testGetArray()
    {
        $response = Curl::get('https://rakit.esyede.my.id/mock', [], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->name[0]);
        $this->assertEquals('Dewi', $response->body->queries->name[1]);
    }

    public function testHead()
    {
        $response = Curl::head('https://rakit.esyede.my.id/mock?name=Budi', ['Accept' => 'application/json']);
        $this->assertEquals(200, $response->code);
    }

    public function testPost()
    {
        $response = Curl::post('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostForm()
    {
        $body = Curl::body_form(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post('https://rakit.esyede.my.id/mock', ['Accept' => 'application/json'], $body);

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->headers->{'Content-Type'});
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostMultipart()
    {
        $body = Curl::body_multipart(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post('https://rakit.esyede.my.id/mock', ['Accept' => 'application/json'], $body);

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('multipart/form-data', explode(';', $response->body->headers->{'Content-Type'})[0]);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostWithEqualSign()
    {
        $body = Curl::body_form(['name' => 'Budi=Hello']);
        $response = Curl::post('https://rakit.esyede.my.id/mock', ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->data->name);
    }

    public function testPostArray()
    {
        $response = Curl::post('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name[0]);
        $this->assertEquals('Dewi', $response->body->data->name[1]);
    }

    public function testPostWithDots()
    {
        $response = Curl::post('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->user_name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testRawPost()
    {
        $response = Curl::post('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], json_encode(['author' => 'Budi Purnomo']));

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->data->json->author);
    }

    public function testPostMultidimensionalArray()
    {
        $body = Curl::body_form(['key' => 'value', 'items' => ['item1', 'item2']]);
        $response = Curl::post('https://rakit.esyede.my.id/mock', ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('value', $response->body->data->key);
        $this->assertEquals('item1', $response->body->data->items[0]);
        $this->assertEquals('item2', $response->body->data->items[1]);
    }

    public function testPut()
    {
        $response = Curl::put('https://rakit.esyede.my.id/mock', [
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
        $response = Curl::patch('https://rakit.esyede.my.id/mock', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'gender' => 'Male']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PATCH', $response->body->method);
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Budi'));
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Male'));
    }

    public function testDelete()
    {
        $response = Curl::delete('https://rakit.esyede.my.id/mock');

        $this->assertEquals(200, $response->code);
        $this->assertEquals('DELETE', $response->body->method);
    }

    public function testUpload()
    {
        $body = Curl::body_multipart(['name' => 'Budi'], ['file' => __DIR__ . DS . 'index.html']);
        $response = Curl::post('https://rakit.esyede.my.id/mock', ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->file->size > 0);
    }

    public function testUploadWithoutHelper()
    {
        $response = Curl::post('https://rakit.esyede.my.id/mock', [
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
        $response = Curl::post('https://rakit.esyede.my.id/mock', [
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
