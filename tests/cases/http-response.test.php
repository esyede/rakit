<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Request;
use System\Foundation\Http\Response;

class HttpResponseTest extends \PHPUnit_Framework_TestCase
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

    public function testCreate()
    {
        $response = Response::create('foo', 301, ['Foo' => 'bar']);

        $this->assertInstanceOf('\\System\\Foundation\\Http\\Response', $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('bar', $response->headers->get('foo'));
    }

    public function testToString()
    {
        $response = new Response();
        $response = explode("\r\n", $response);
        $this->assertEquals('HTTP/1.0 200 OK', $response[0]);
        $this->assertEquals('Cache-Control: no-cache', $response[1]);
    }

    public function testClone()
    {
        $response = new Response();
        $cloned = clone $response;
        $this->assertEquals($response, $cloned);
    }

    public function testSendHeaders()
    {
        $response = new Response();
        $headers = $response->sendHeaders();
        $this->assertObjectHasAttribute('headers', $headers);
        $this->assertObjectHasAttribute('content', $headers);
        $this->assertObjectHasAttribute('version', $headers);
        $this->assertObjectHasAttribute('statusCode', $headers);
        $this->assertObjectHasAttribute('statusText', $headers);
        $this->assertObjectHasAttribute('charset', $headers);
    }

    public function testSend()
    {
        $response = new Response();
        $responseSend = $response->send();
        $this->assertObjectHasAttribute('headers', $responseSend);
        $this->assertObjectHasAttribute('content', $responseSend);
        $this->assertObjectHasAttribute('version', $responseSend);
        $this->assertObjectHasAttribute('statusCode', $responseSend);
        $this->assertObjectHasAttribute('statusText', $responseSend);
        $this->assertObjectHasAttribute('charset', $responseSend);
    }

    public function testGetCharset()
    {
        $response = new Response();
        $response->setCharset('UTF-8');
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testIsCacheable()
    {
        $this->assertFalse((new Response())->isCacheable());
    }

    public function testIsCacheableWithSetTtl()
    {
        $response = new Response();
        $response->setTtl(10);
        $this->assertTrue($response->isCacheable());
    }

    public function testMustRevalidate()
    {
        $this->assertFalse((new Response())->mustRevalidate());
    }

    public function testSetNotModified()
    {
        $response = new Response();
        $modified = $response->setNotModified();
        $this->assertObjectHasAttribute('headers', $modified);
        $this->assertObjectHasAttribute('content', $modified);
        $this->assertObjectHasAttribute('version', $modified);
        $this->assertObjectHasAttribute('statusCode', $modified);
        $this->assertObjectHasAttribute('statusText', $modified);
        $this->assertObjectHasAttribute('charset', $modified);
        $this->assertEquals(304, $modified->getStatusCode());
    }

    public function testIsSuccessful()
    {
        $this->assertTrue((new Response())->isSuccessful());
    }

    public function testIsNotModified()
    {
        $this->assertFalse((new Response())->isNotModified(new Request()));
    }

    public function testIsValidateable()
    {
        $response = new Response('', 200, ['Last-Modified' => $this->createDateTimeOneHourAgo()->format(DATE_RFC2822)]);
        $this->assertTrue($response->isValidateable());
        $this->assertTrue((new Response('', 200, ['ETag' => '"12345"']))->isValidateable());
        $this->assertFalse((new Response())->isValidateable());
    }

    public function testGetDate()
    {
        $response = new Response('', 200, ['Date' => $this->createDateTimeOneHourAgo()->format(DATE_RFC2822)]);
        $this->assertEquals(0, $this->createDateTimeOneHourAgo()->diff($response->getDate())->format('%s'));
        $this->assertLessThan(1, (new Response())->getDate()->diff(new \DateTime(), true)->format('%s'));

        $response = new Response('', 200, ['Date' => $this->createDateTimeOneHourAgo()->format(DATE_RFC2822)]);
        $now = $this->createDateTimeNow();
        $response->headers->set('Date', $now->format(DATE_RFC2822));
        $this->assertEquals(0, $now->diff($response->getDate())->format('%s'), '->getDate() returns the date when the header has been modified');

        $response = new Response('', 200);
        $response->headers->remove('Date');
        $this->assertInstanceOf('\DateTime', $response->getDate());
    }

    public function testGetMaxAge()
    {
        $response = new Response();
        $response->headers->set('Cache-Control', 's-maxage=600, max-age=0');
        $this->assertEquals(600, $response->getMaxAge());

        $response = new Response();
        $response->headers->set('Cache-Control', 'max-age=600');
        $this->assertEquals(600, $response->getMaxAge());

        $response = new Response();
        $response->headers->set('Cache-Control', 'must-revalidate');
        $response->headers->set('Expires', $this->createDateTimeOneHourLater()->format(DATE_RFC2822));
        $this->assertEquals(3600, $response->getMaxAge());

        $response = new Response();
        $this->assertNull($response->getMaxAge());
    }

    public function testSetSharedMaxAge()
    {
        $response = new Response();
        $response->setSharedMaxAge(20);
        $this->assertEquals('public, s-maxage=20', $response->headers->get('Cache-Control'));
    }

    public function testIsPrivate()
    {
        $response = new Response();
        $response->headers->set('Cache-Control', 'max-age=100');
        $response->setPrivate();
        $this->assertEquals(100, $response->headers->getCacheControlDirective('max-age'));
        $this->assertTrue($response->headers->getCacheControlDirective('private'));

        $response = new Response();
        $response->headers->set('Cache-Control', 'public, max-age=100');
        $response->setPrivate();
        $this->assertEquals(100, $response->headers->getCacheControlDirective('max-age'));
        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertFalse($response->headers->hasCacheControlDirective('public'));
    }

    public function testExpire()
    {
        $response = new Response();
        $response->headers->set('Cache-Control', 'max-age=100');
        $response->expire();
        $this->assertEquals(100, $response->headers->get('Age'));

        $response = new Response();
        $response->headers->set('Cache-Control', 'max-age=100, s-maxage=500');
        $response->expire();
        $this->assertEquals(500, $response->headers->get('Age'));

        $response = new Response();
        $response->headers->set('Cache-Control', 'max-age=5, s-maxage=500');
        $response->headers->set('Age', '1000');
        $response->expire();
        $this->assertEquals(1000, $response->headers->get('Age'));

        $response = new Response();
        $response->expire();
        $this->assertFalse($response->headers->has('Age'));
    }

    public function testGetTtl()
    {
        $response = new Response();
        $this->assertNull($response->getTtl());

        $response = new Response();
        $response->headers->set('Expires', $this->createDateTimeOneHourLater()->format(DATE_RFC2822));
        $this->assertLessThan(1, 3600 - $response->getTtl());

        $response = new Response();
        $response->headers->set('Expires', $this->createDateTimeOneHourAgo()->format(DATE_RFC2822));
        $this->assertLessThan(0, $response->getTtl());

        $response = new Response();
        $response->headers->set('Cache-Control', 'max-age=60');
        $this->assertLessThan(1, 60 - $response->getTtl());
    }

    public function testSetClientTtl()
    {
        $response = new Response();
        $response->setClientTtl(10);
        $this->assertEquals($response->getMaxAge(), $response->getAge() + 10);
    }

    public function testGetSetProtocolVersion()
    {
        $response = new Response();
        $this->assertEquals('1.0', $response->getProtocolVersion());

        $response->setProtocolVersion('1.1');
        $this->assertEquals('1.1', $response->getProtocolVersion());
    }

    public function testGetVary()
    {
        $response = new Response();
        $this->assertEquals([], $response->getVary());

        $response = new Response();
        $response->headers->set('Vary', 'Accept-Language');
        $this->assertEquals(['Accept-Language'], $response->getVary());

        $response = new Response();
        $response->headers->set('Vary', 'Accept-Language User-Agent    X-Foo');
        $this->assertEquals(['Accept-Language', 'User-Agent', 'X-Foo'], $response->getVary());

        $response = new Response();
        $response->headers->set('Vary', 'Accept-Language,User-Agent,    X-Foo');
        $this->assertEquals(['Accept-Language', 'User-Agent', 'X-Foo'], $response->getVary());
    }

    public function testSetVary()
    {
        $response = new Response();
        $response->setVary('Accept-Language');
        $this->assertEquals(['Accept-Language'], $response->getVary());

        $response->setVary('Accept-Language, User-Agent');
        $this->assertEquals(['Accept-Language', 'User-Agent'], $response->getVary());

        $response->setVary('X-Foo', false);
        $this->assertEquals(['Accept-Language', 'User-Agent'], $response->getVary());
    }

    public function testContentTypeCharset()
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/css');
        $response->prepare(new Request());
        $this->assertEquals('text/css; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testPrepareDoesNothingIfContentTypeIsSet()
    {
        $response = new Response('foo');
        $response->headers->set('Content-Type', 'text/plain');
        $response->prepare(new Request());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('content-type'));
    }

    public function testPrepareDoesNothingIfRequestFormatIsNotDefined()
    {
        $response = new Response('foo');
        $response->prepare(new Request());
        $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('content-type'));
    }

    public function testPrepareSetContentType()
    {
        $response = new Response('foo');
        $request = Request::create('/');
        $request->setRequestFormat('json');
        $response->prepare($request);
        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }

    public function testPrepareRemovesContentForHeadRequests()
    {
        $response = new Response('foo');
        $request = Request::create('/', 'HEAD');
        $response->prepare($request);
        $this->assertEquals('', $response->getContent());
    }

    public function testPrepareSetsPragmaOnHttp10Only()
    {
        $request = Request::create('/', 'GET');
        $request->server->set('SERVER_PROTOCOL', 'HTTP/1.0');

        $response = new Response('foo');
        $response->prepare($request);
        $this->assertEquals('no-cache', $response->headers->get('pragma'));
        $this->assertEquals('-1', $response->headers->get('expires'));

        $request->server->set('SERVER_PROTOCOL', 'HTTP/1.1');
        $response = new Response('foo');
        $response->prepare($request);
        $this->assertFalse($response->headers->has('pragma'));
        $this->assertFalse($response->headers->has('expires'));
    }

    public function testSetCache()
    {
        $response = new Response();

        try {
            $response->setCache(['"wrong option"' => 'value']);
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Exception', $e);
            $this->assertContains('"wrong option"', $e->getMessage());
        }

        $options = ['etag' => '"whatever"'];
        $response->setCache($options);
        $this->assertEquals($response->getEtag(), '"whatever"');

        $now = new \DateTime();
        $options = ['last_modified' => $now];
        $response->setCache($options);
        $this->assertEquals($response->getLastModified()->getTimestamp(), $now->getTimestamp());

        $options = ['max_age' => 100];
        $response->setCache($options);
        $this->assertEquals($response->getMaxAge(), 100);

        $options = ['s_maxage' => 200];
        $response->setCache($options);
        $this->assertEquals($response->getMaxAge(), 200);

        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($response->headers->hasCacheControlDirective('private'));

        $response->setCache(['public' => true]);
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($response->headers->hasCacheControlDirective('private'));

        $response->setCache(['public' => false]);
        $this->assertFalse($response->headers->hasCacheControlDirective('public'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));

        $response->setCache(['private' => true]);
        $this->assertFalse($response->headers->hasCacheControlDirective('public'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));

        $response->setCache(['private' => false]);
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($response->headers->hasCacheControlDirective('private'));
    }

    public function testSendContent()
    {
        $response = new Response('test response rendering', 200);

        ob_start();
        $response->sendContent();
        $string = ob_get_clean();
        $this->assertContains('test response rendering', $string);
    }

    public function testSetPublic()
    {
        $response = new Response();
        $response->setPublic();
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($response->headers->hasCacheControlDirective('private'));
    }

    public function testSetExpires()
    {
        $response = new Response();
        $response->setExpires(null);
        $this->assertNull($response->getExpires());

        $now = new \DateTime();
        $response->setExpires($now);
        $this->assertEquals($response->getExpires()->getTimestamp(), $now->getTimestamp());
    }

    public function testSetLastModified()
    {
        $response = new Response();
        $response->setLastModified(new \DateTime());
        $this->assertNotNull($response->getLastModified());

        $response->setLastModified(null);
        $this->assertNull($response->getLastModified());
    }

    public function testIsInvalid()
    {
        $response = new Response();

        try {
            $response->setStatusCode(99);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue($response->isInvalid());
        }

        try {
            $response->setStatusCode(650);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue($response->isInvalid());
        }

        $response = new Response('', 200);
        $this->assertFalse($response->isInvalid());
    }

    /**
     * @dataProvider getStatusCodeFixtures
     */
    public function testSetStatusCode($code, $text, $expectedText)
    {
        $response = new Response();
        $response->setStatusCode($code, $text);
        $statusText = new \ReflectionProperty($response, 'statusText');
        $statusText->setAccessible(true);
        $this->assertEquals($expectedText, $statusText->getValue($response));
    }

    public function getStatusCodeFixtures()
    {
        return [
            ['200', null, 'OK'],
            ['200', false, ''],
            ['200', 'foo', 'foo'],
            ['199', null, ''],
            ['199', false, ''],
            ['199', 'foo', 'foo'],
        ];
    }

    public function testIsInformational()
    {
        $response = new Response('', 100);
        $this->assertTrue($response->isInformational());

        $response = new Response('', 200);
        $this->assertFalse($response->isInformational());
    }

    public function testIsRedirectRedirection()
    {
        $codes = [301, 302, 303, 307];

        foreach ($codes as $code) {
            $response = new Response('', $code);
            $this->assertTrue($response->isRedirection());
            $this->assertTrue($response->isRedirect());
        }

        $response = new Response('', 304);
        $this->assertTrue($response->isRedirection());
        $this->assertFalse($response->isRedirect());

        $response = new Response('', 200);
        $this->assertFalse($response->isRedirection());
        $this->assertFalse($response->isRedirect());

        $response = new Response('', 404);
        $this->assertFalse($response->isRedirection());
        $this->assertFalse($response->isRedirect());

        $response = new Response('', 301, ['Location' => '/good-uri']);
        $this->assertFalse($response->isRedirect('/bad-uri'));
        $this->assertTrue($response->isRedirect('/good-uri'));
    }

    public function testIsNotFound()
    {
        $this->assertTrue((new Response('', 404))->isNotFound());
        $this->assertFalse((new Response('', 200))->isNotFound());
    }

    public function testIsEmpty()
    {
        $codes = [201, 204, 304];

        foreach ($codes as $code) {
            $this->assertTrue((new Response('', $code))->isEmpty());
        }

        $this->assertFalse((new Response('', 200))->isEmpty());
    }

    public function testIsForbidden()
    {
        $this->assertTrue((new Response('', 403))->isForbidden());
        $this->assertFalse((new Response('', 200))->isForbidden());
    }

    public function testIsOk()
    {
        $this->assertTrue((new Response('', 200))->isOk());
        $this->assertFalse((new Response('', 404))->isOk());
    }

    public function testIsServerOrClientError()
    {
        $response = new Response('', 404);
        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isServerError());

        $response = new Response('', 500);
        $this->assertFalse($response->isClientError());
        $this->assertTrue($response->isServerError());
    }

    public function testHasVary()
    {
        $response = new Response();
        $this->assertFalse($response->hasVary());
        $response->setVary('User-Agent');
        $this->assertTrue($response->hasVary());
    }

    public function testSetEtag()
    {
        $response = new Response('', 200, ['ETag' => '"12345"']);
        $response->setEtag();
        $this->assertNull($response->headers->get('Etag'), '->setEtag() removes Etags when call with null');
    }

    /**
     * @dataProvider validContentProvider
     */
    public function testSetContent($content)
    {
        $response = new Response();
        $response->setContent($content);
        $this->assertEquals((string) $content, $response->getContent());
    }

    /**
     * @expectedException UnexpectedValueException
     * @dataProvider invalidContentProvider
     */
    public function testSetContentInvalid($content)
    {
        (new Response())->setContent($content);
    }

    public function testSettersAreChainable()
    {
        $response = new Response();
        $setters = [
            'setProtocolVersion' => '1.0',
            'setCharset' => 'UTF-8',
            'setPublic' => null,
            'setPrivate' => null,
            'setDate' => new \DateTime,
            'expire' => null,
            'setMaxAge' => 1,
            'setSharedMaxAge' => 1,
            'setTtl' => 1,
            'setClientTtl' => 1,
        ];

        foreach ($setters as $setter => $arg) {
            $this->assertEquals($response, $response->{$setter}($arg));
        }
    }

    public function validContentProvider()
    {
        return ['obj' => [new StringableObjectMock], 'string' => ['Foo'], 'int' => [2]];
    }

    public function invalidContentProvider()
    {
        return ['obj' => [new \stdClass], 'array' => [[]], 'bool' => [true, '1']];
    }

    protected function createDateTimeOneHourAgo()
    {
        return (new \DateTime())->sub(new \DateInterval('PT1H'));
    }

    protected function createDateTimeOneHourLater()
    {
        return (new \DateTime())->add(new \DateInterval('PT1H'));
    }

    protected function createDateTimeNow()
    {
        return new \DateTime();
    }
}

class StringableObjectMock
{
    public function __toString()
    {
        return 'Foo';
    }
}
