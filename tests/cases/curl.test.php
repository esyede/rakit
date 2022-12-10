<?php

defined('DS') or exit('No direct script access.');

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

    public function testCurlOptions()
    {
        Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
        $response = Curl::get('https://mockbin.com/request');
        $this->assertTrue(property_exists($response->body->cookies, 'foo'));
        Curl::clear_curl_options();
    }

    public function testTimeoutFail()
    {
        try {
            Curl::timeout(1);
            Curl::get('https://mockbin.com/delay/1000');
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

        $response = Curl::get('https://mockbin.com/request');
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('header1', $response->body->headers);
        $this->assertEquals('Hello', $response->body->headers->header1);
        $this->assertObjectHasAttribute('header2', $response->body->headers);
        $this->assertEquals('world', $response->body->headers->header2);

        $response = Curl::get('https://mockbin.com/request', ['header1' => 'Custom value']);
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('header1', $response->body->headers);
        $this->assertEquals('Custom value', $response->body->headers->header1);

        Curl::clear_default_headers();

        $response = Curl::get('https://mockbin.com/request');
        $this->assertEquals(200, $response->code);
        $this->assertObjectNotHasAttribute('header1', $response->body->headers);
        $this->assertObjectNotHasAttribute('header2', $response->body->headers);
    }

    public function testDefaultHeader()
    {
        Curl::default_header('Hello', 'custom');

        $response = Curl::get('https://mockbin.com/request');
        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'hello'));
        $this->assertEquals('custom', $response->body->headers->hello);

        Curl::clear_default_headers();

        $response = Curl::get('https://mockbin.com/request');

        $this->assertEquals(200, $response->code);
        $this->assertFalse(property_exists($response->body->headers, 'hello'));
    }

    public function testBasicAuthentication()
    {
        Curl::auth('user', 'password');
        $response = Curl::get('https://mockbin.com/request');
        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->body->headers->authorization);
    }

    public function testCustomHeaders()
    {
        $response = Curl::get('https://mockbin.com/request', ['user-agent' => 'dummy-agent']);
        $this->assertEquals(200, $response->code);
        $this->assertEquals('dummy-agent', $response->body->headers->{'user-agent'});
    }

    public function testGet()
    {
        $response = Curl::get('https://mockbin.com/request?name=Budi', [
            'Accept' => 'application/json',
        ], ['age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queryString->name);
        $this->assertEquals(28, $response->body->queryString->age);
    }

    public function testGetMultidimensionalArray()
    {
        $response = Curl::get('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['key' => 'value', 'items' => ['item1', 'item2']]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('value', $response->body->queryString->key);
        $this->assertEquals('item1', $response->body->queryString->items[0]);
        $this->assertEquals('item2', $response->body->queryString->items[1]);
    }

    public function testGetWithDots()
    {
        $response = Curl::get('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queryString->{'user.name'});
        $this->assertEquals(28, $response->body->queryString->age);
    }

    public function testGetWithDotsAlt()
    {
        $response = Curl::get('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi Purnomo', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->queryString->{'user.name'});
        $this->assertEquals(28, $response->body->queryString->age);
    }

    public function testGetWithEqualSign()
    {
        $response = Curl::get('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->queryString->name);
    }

    public function testGetWithEqualSignAlt()
    {
        $response = Curl::get('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello=Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello=Dewi', $response->body->queryString->name);
    }

    public function testGetWithComplexQuery()
    {
        $response = Curl::get('https://mockbin.com/request?query=[{"type":"/music/album","name":null,"artist":{"id":"/id/denny_caknan"},"limit":3}]&cursor');

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('', $response->body->queryString->cursor);
        $this->assertEquals('[{"type":"/music/album","name":null,"artist":{"id":"/id/denny_caknan"},"limit":3}]', $response->body->queryString->query);
    }

    public function testGetArray()
    {
        $response = Curl::get('https://mockbin.com/request', [], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queryString->name[0]);
        $this->assertEquals('Dewi', $response->body->queryString->name[1]);
    }

    public function testHead()
    {
        $response = Curl::head('https://mockbin.com/request?name=Budi', ['Accept' => 'application/json']);
        $this->assertEquals(200, $response->code);
    }

    public function testPost()
    {
        $response = Curl::post('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertEquals(28, $response->body->postData->params->age);
    }

    public function testPostForm()
    {
        $body = Curl::body_form(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post('https://mockbin.com/request', ['Accept' => 'application/json'], $body);

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->headers->{'content-type'});
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->postData->mimeType);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertEquals(28, $response->body->postData->params->age);
    }

    public function testPostMultipart()
    {
        $body = Curl::body_multipart(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post('https://mockbin.com/request', ['Accept' => 'application/json'], $body);

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('multipart/form-data', explode(';', $response->body->headers->{'content-type'})[0]);
        $this->assertEquals('multipart/form-data', $response->body->postData->mimeType);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertEquals(28, $response->body->postData->params->age);
    }

    public function testPostWithEqualSign()
    {
        $body = Curl::body_form(['name' => 'Budi=Hello']);
        $response = Curl::post('https://mockbin.com/request', ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->postData->params->name);
    }

    public function testPostArray()
    {
        $response = Curl::post('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->{'name[0]'});
        $this->assertEquals('Dewi', $response->body->postData->params->{'name[1]'});
    }

    public function testPostWithDots()
    {
        $response = Curl::post('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->{'user.name'});
        $this->assertEquals(28, $response->body->postData->params->age);
    }

    public function testRawPost()
    {
        $response = Curl::post('https://mockbin.com/request', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], json_encode(['author' => 'Budi Purnomo']));

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi Purnomo', json_decode($response->body->postData->text)->author);
    }

    public function testPostMultidimensionalArray()
    {
        $body = Curl::body_form(['key' => 'value', 'items' => ['item1', 'item2']]);
        $response = Curl::post('https://mockbin.com/request', ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('value', $response->body->postData->params->key);
        $this->assertEquals('item1', $response->body->postData->params->{'items[0]'});
        $this->assertEquals('item2', $response->body->postData->params->{'items[1]'});
    }

    public function testPut()
    {
        $response = Curl::put('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PUT', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertEquals(28, $response->body->postData->params->age);
    }

    public function testPatch()
    {
        $response = Curl::patch('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PATCH', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertEquals(28, $response->body->postData->params->age);
    }

    public function testDelete()
    {
        $response = Curl::delete('https://mockbin.com/request', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('DELETE', $response->body->method);
    }

    public function testUpload()
    {
        $body = Curl::body_multipart(['name' => 'Budi'], ['file' => __DIR__ . DS . 'index.html']);
        $response = Curl::post('https://mockbin.com/request', ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertTrue(strlen($response->body->postData->params->file) > 0);
    }

    public function testUploadWithoutHelper()
    {
        $response = Curl::post('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Budi',
            'file' => Curl::body_file(__DIR__ . DS . 'index.html'),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertTrue(strlen($response->body->postData->params->file) > 0);
    }

    public function testUploadIfFilePartOfData()
    {
        $response = Curl::post('https://mockbin.com/request', [
            'Accept' => 'application/json',
        ], [
            'name' => 'Budi',
            'files[owl.gif]' => Curl::body_file(__DIR__ . DS . 'index.html'),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->postData->params->name);
        $this->assertTrue(strlen($response->body->postData->params->{'files[owl.gif]'}) > 0);
    }
}
