<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Request;

class HttpRequestTest extends \PHPUnit_Framework_TestCase
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

    public function testConstructor()
    {
        $this->testInitialize();
    }

    public function testInitialize()
    {
        $request = new Request();
        $request->initialize(['foo' => 'bar']);

        $this->assertEquals('bar', $request->query->get('foo'), '->initialize() takes an array of query parameters as its first argument');
        $request->initialize([], ['foo' => 'bar']);
        $this->assertEquals('bar', $request->request->get('foo'), '->initialize() takes an array of request parameters as its second argument');

        $request->initialize([], [], ['foo' => 'bar']);
        $this->assertEquals('bar', $request->attributes->get('foo'), '->initialize() takes an array of attributes as its third argument');

        $request->initialize([], [], [], [], [], ['HTTP_FOO' => 'bar']);
        $this->assertEquals('bar', $request->headers->get('FOO'), '->initialize() takes an array of HTTP headers as its fourth argument');
    }

    public function testCreate()
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $this->assertEquals('http://test.com/foo?bar=baz', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('bar=baz', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test.com/foo', 'GET', ['bar' => 'baz']);
        $this->assertEquals('http://test.com/foo?bar=baz', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('bar=baz', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test.com/foo?bar=foo', 'GET', ['bar' => 'baz']);
        $this->assertEquals('http://test.com/foo?bar=baz', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('bar=baz', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('https://test.com/foo?bar=baz');
        $this->assertEquals('https://test.com/foo?bar=baz', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('bar=baz', $request->getQueryString());
        $this->assertEquals(443, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertTrue($request->isSecure());

        $request = Request::create('test.com:90/foo');
        $this->assertEquals('http://test.com:90/foo', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('test.com', $request->getHost());
        $this->assertEquals('test.com:90', $request->getHttpHost());
        $this->assertEquals(90, $request->getPort());
        $this->assertFalse($request->isSecure());

        $request = Request::create('https://test.com:90/foo');
        $this->assertEquals('https://test.com:90/foo', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('test.com', $request->getHost());
        $this->assertEquals('test.com:90', $request->getHttpHost());
        $this->assertEquals(90, $request->getPort());
        $this->assertTrue($request->isSecure());

        $request = Request::create('https://127.0.0.1:90/foo');
        $this->assertEquals('https://127.0.0.1:90/foo', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('127.0.0.1', $request->getHost());
        $this->assertEquals('127.0.0.1:90', $request->getHttpHost());
        $this->assertEquals(90, $request->getPort());
        $this->assertTrue($request->isSecure());

        $request = Request::create('https://[::1]:90/foo');
        $this->assertEquals('https://[::1]:90/foo', $request->getUri());
        $this->assertEquals('/foo', $request->getPathInfo());
        $this->assertEquals('[::1]', $request->getHost());
        $this->assertEquals('[::1]:90', $request->getHttpHost());
        $this->assertEquals(90, $request->getPort());
        $this->assertTrue($request->isSecure());

        $json = '{"jsonrpc":"2.0","method":"echo","id":7,"params":["Hello World"]}';
        $request = Request::create('http://example.com/jsonrpc', 'POST', [], [], [], [], $json);
        $this->assertEquals($json, $request->getContent());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test.com');
        $this->assertEquals('http://test.com/', $request->getUri());
        $this->assertEquals('/', $request->getPathInfo());
        $this->assertEquals('', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test.com?test=1');
        $this->assertEquals('http://test.com/?test=1', $request->getUri());
        $this->assertEquals('/', $request->getPathInfo());
        $this->assertEquals('test=1', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test.com:90/?test=1');
        $this->assertEquals('http://test.com:90/?test=1', $request->getUri());
        $this->assertEquals('/', $request->getPathInfo());
        $this->assertEquals('test=1', $request->getQueryString());
        $this->assertEquals(90, $request->getPort());
        $this->assertEquals('test.com:90', $request->getHttpHost());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://test:test@test.com');
        $this->assertEquals('http://test.com/', $request->getUri());
        $this->assertEquals('/', $request->getPathInfo());
        $this->assertEquals('', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertEquals('test', $request->getUser());
        $this->assertEquals('test', $request->getPassword());
        $this->assertFalse($request->isSecure());

        $request = Request::create('http://testnopass@test.com');
        $this->assertEquals('http://test.com/', $request->getUri());
        $this->assertEquals('/', $request->getPathInfo());
        $this->assertEquals('', $request->getQueryString());
        $this->assertEquals(80, $request->getPort());
        $this->assertEquals('test.com', $request->getHttpHost());
        $this->assertEquals('testnopass', $request->getUser());
        $this->assertNull($request->getPassword());
        $this->assertFalse($request->isSecure());
    }

    public function testDuplicate()
    {
        $request = new Request(['foo' => 'bar'], ['foo' => 'bar'], ['foo' => 'bar'], [], [], ['HTTP_FOO' => 'bar']);
        $dup = $request->duplicate();

        $this->assertEquals($request->query->all(), $dup->query->all(), '->duplicate() duplicates a request an copy the current query parameters');
        $this->assertEquals($request->request->all(), $dup->request->all(), '->duplicate() duplicates a request an copy the current request parameters');
        $this->assertEquals($request->attributes->all(), $dup->attributes->all(), '->duplicate() duplicates a request an copy the current attributes');
        $this->assertEquals($request->headers->all(), $dup->headers->all(), '->duplicate() duplicates a request an copy the current HTTP headers');

        $dup = $request->duplicate(['foo' => 'foobar'], ['foo' => 'foobar'], ['foo' => 'foobar'], [], [], ['HTTP_FOO' => 'foobar']);

        $this->assertEquals(['foo' => 'foobar'], $dup->query->all(), '->duplicate() overrides the query parameters if provided');
        $this->assertEquals(['foo' => 'foobar'], $dup->request->all(), '->duplicate() overrides the request parameters if provided');
        $this->assertEquals(['foo' => 'foobar'], $dup->attributes->all(), '->duplicate() overrides the attributes if provided');
        $this->assertEquals(['Foo' => ['foobar']], $dup->headers->all(), '->duplicate() overrides the HTTP header if provided');
    }

    /**
     * @dataProvider getFormatToMimeTypeMapProvider
     */
    public function testGetFormatFromMimeType($format, $mimeTypes)
    {
        $request = new Request();
        foreach ($mimeTypes as $mime) {
            $this->assertEquals($format, $request->getFormat($mime));
        }
        $request->setFormat($format, $mimeTypes);
        foreach ($mimeTypes as $mime) {
            $this->assertEquals($format, $request->getFormat($mime));
        }
    }

    public function testGetFormatFromMimeTypeWithParameters()
    {
        $request = new Request();
        $this->assertEquals('json', $request->getFormat('application/json; charset=utf-8'));
    }

    /**
     * @dataProvider getFormatToMimeTypeMapProvider
     */
    public function testGetMimeTypeFromFormat($format, $mimeTypes)
    {
        if (null !== $format) {
            $request = new Request();
            $this->assertEquals($mimeTypes[0], $request->getMimeType($format));
        }
    }

    public function getFormatToMimeTypeMapProvider()
    {
        return [
            [null, [null, 'unexistent-mime-type']],
            ['txt', ['text/plain']],
            ['js', ['application/javascript', 'application/x-javascript', 'text/javascript']],
            ['css', ['text/css']],
            ['json', ['application/json', 'application/x-json']],
            ['xml', ['text/xml', 'application/xml', 'application/x-xml']],
            ['rdf', ['application/rdf+xml']],
            ['atom',['application/atom+xml']],
        ];
    }

    public function testGetUri()
    {
        $server = [];
        // Standard Request on non default PORT
        // http://hostname:8080/index.php/path/info?query=string
        $server['HTTP_HOST'] = 'hostname:8080';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '8080';

        $server['QUERY_STRING'] = 'query=string';
        $server['REQUEST_URI'] = '/index.php/path/info?query=string';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PATH_INFO'] = '/path/info';
        $server['PATH_TRANSLATED'] = 'redirect:/index.php/path/info';
        $server['PHP_SELF'] = '/index_dev.php/path/info';
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';

        $request = new Request();
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://hostname:8080/index.php/path/info?query=string', $request->getUri(), '->getUri() with non default port');

        // Use std port number
        $server['HTTP_HOST'] = 'hostname';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://hostname/index.php/path/info?query=string', $request->getUri(), '->getUri() with default port');

        // Without HOST HEADER
        unset($server['HTTP_HOST']);
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://servername/index.php/path/info?query=string', $request->getUri(), '->getUri() with default port without HOST_HEADER');

        // Request with URL REWRITING (hide index.php)
        //   RewriteCond %{REQUEST_FILENAME} !-f
        //   RewriteRule ^(.*)$ index.php [QSA,L]
        // http://hostname:8080/path/info?query=string
        $server = [];
        $server['HTTP_HOST'] = 'hostname:8080';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '8080';

        $server['REDIRECT_QUERY_STRING'] = 'query=string';
        $server['REDIRECT_URL'] = '/path/info';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['QUERY_STRING'] = 'query=string';
        $server['REQUEST_URI'] = '/path/info?toto=test&1=1';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PHP_SELF'] = '/index.php';
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';

        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://hostname:8080/path/info?query=string', $request->getUri(), '->getUri() with rewrite');

        // Use std port number
        //  http://hostname/path/info?query=string
        $server['HTTP_HOST'] = 'hostname';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://hostname/path/info?query=string', $request->getUri(), '->getUri() with rewrite and default port');

        // Without HOST HEADER
        unset($server['HTTP_HOST']);
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://servername/path/info?query=string', $request->getUri(), '->getUri() with rewrite, default port without HOST_HEADER');

        // With encoded characters

        $server = [
            'HTTP_HOST' => 'hostname:8080',
            'SERVER_NAME' => 'servername',
            'SERVER_PORT' => '8080',
            'QUERY_STRING' => 'query=string',
            'REQUEST_URI' => '/ba%20se/index_dev.php/foo%20bar/in+fo?query=string',
            'SCRIPT_NAME' => '/ba se/index_dev.php',
            'PATH_TRANSLATED' => 'redirect:/index.php/foo bar/in+fo',
            'PHP_SELF' => '/ba se/index_dev.php/path/info',
            'SCRIPT_FILENAME' => '/some/where/ba se/index_dev.php',
        ];

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals(
            'http://hostname:8080/ba%20se/index_dev.php/foo%20bar/in+fo?query=string',
            $request->getUri()
        );

        // with user info

        $server['PHP_AUTH_USER'] = 'budi';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://hostname:8080/ba%20se/index_dev.php/foo%20bar/in+fo?query=string', $request->getUri());

        $server['PHP_AUTH_PW'] = 'symfony';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://hostname:8080/ba%20se/index_dev.php/foo%20bar/in+fo?query=string', $request->getUri());
    }

    public function testGetUriForPath()
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $this->assertEquals('http://test.com/some/path', $request->getUriForPath('/some/path'));

        $request = Request::create('http://test.com:90/foo?bar=baz');
        $this->assertEquals('http://test.com:90/some/path', $request->getUriForPath('/some/path'));

        $request = Request::create('https://test.com/foo?bar=baz');
        $this->assertEquals('https://test.com/some/path', $request->getUriForPath('/some/path'));

        $request = Request::create('https://test.com:90/foo?bar=baz');
        $this->assertEquals('https://test.com:90/some/path', $request->getUriForPath('/some/path'));

        $server = [];

        // Standard Request on non default PORT
        // http://hostname:8080/index.php/path/info?query=string

        $server['HTTP_HOST'] = 'hostname:8080';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '8080';

        $server['QUERY_STRING'] = 'query=string';
        $server['REQUEST_URI'] = '/index.php/path/info?query=string';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PATH_INFO'] = '/path/info';
        $server['PATH_TRANSLATED'] = 'redirect:/index.php/path/info';
        $server['PHP_SELF'] = '/index_dev.php/path/info';
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';

        $request = new Request();

        $request->initialize([], [], [], [], [],$server);

        $this->assertEquals('http://hostname:8080/index.php/some/path', $request->getUriForPath('/some/path'), '->getUriForPath() with non default port');

        // Use std port number
        $server['HTTP_HOST'] = 'hostname';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://hostname/index.php/some/path', $request->getUriForPath('/some/path'), '->getUriForPath() with default port');

        // Without HOST HEADER
        unset($server['HTTP_HOST']);
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://servername/index.php/some/path', $request->getUriForPath('/some/path'), '->getUriForPath() with default port without HOST_HEADER');

        // Request with URL REWRITING (hide index.php)
        //   RewriteCond %{REQUEST_FILENAME} !-f
        //   RewriteRule ^(.*)$ index.php [QSA,L]
        // http://hostname:8080/path/info?query=string
        $server = [];
        $server['HTTP_HOST'] = 'hostname:8080';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '8080';

        $server['REDIRECT_QUERY_STRING'] = 'query=string';
        $server['REDIRECT_URL'] = '/path/info';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['QUERY_STRING'] = 'query=string';
        $server['REQUEST_URI'] = '/path/info?toto=test&1=1';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PHP_SELF'] = '/index.php';
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';

        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://hostname:8080/some/path', $request->getUriForPath('/some/path'), '->getUri() with rewrite');

        // Use std port number
        //  http://hostname/path/info?query=string
        $server['HTTP_HOST'] = 'hostname';
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://hostname/some/path', $request->getUriForPath('/some/path'), '->getUriForPath() with rewrite and default port');

        // Without HOST HEADER
        unset($server['HTTP_HOST']);
        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '80';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('http://servername/some/path', $request->getUriForPath('/some/path'), '->getUriForPath() with rewrite, default port without HOST_HEADER');
        $this->assertEquals('servername', $request->getHttpHost());

        // with user info

        $server['PHP_AUTH_USER'] = 'budi';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://servername/some/path', $request->getUriForPath('/some/path'));

        $server['PHP_AUTH_PW'] = 'symfony';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://servername/some/path', $request->getUriForPath('/some/path'));
    }

    public function testGetUserInfo()
    {
        $request = new Request();

        $server['PHP_AUTH_USER'] = 'budi';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('budi:', $request->getUserInfo());

        $server['PHP_AUTH_USER'] = '0';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('0:', $request->getUserInfo());

        $server['PHP_AUTH_PW'] = '0';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('0:0', $request->getUserInfo());
    }

    public function testGetSchemeAndHttpHost()
    {
        $request = new Request();

        $server['SERVER_NAME'] = 'servername';
        $server['SERVER_PORT'] = '90';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://servername:90', $request->getSchemeAndHttpHost());

        $server['PHP_AUTH_USER'] = 'budi';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://servername:90', $request->getSchemeAndHttpHost());

        $server['PHP_AUTH_USER'] = '0';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://servername:90', $request->getSchemeAndHttpHost());

        $server['PHP_AUTH_PW'] = '0';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('http://servername:90', $request->getSchemeAndHttpHost());
    }

    /**
     * @dataProvider getQueryStringNormalizationData
     */
    public function testGetQueryString($query, $expectedQuery, $msg)
    {
        $request = new Request();

        $request->server->set('QUERY_STRING', $query);
        $this->assertSame($expectedQuery, $request->getQueryString(), $msg);
    }

    public function getQueryStringNormalizationData()
    {
        return [
            ['foo', 'foo', 'works with valueless parameters'],
            ['foo=', 'foo=', 'includes a dangling equal sign'],
            ['bar=&foo=bar', 'bar=&foo=bar', '->works with empty parameters'],
            ['foo=bar&bar=', 'bar=&foo=bar', 'sorts keys alphabetically'],

            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str.
            ['him=John%20Doe&her=Jane+Doe', 'her=Jane%20Doe&him=John%20Doe', 'normalizes spaces in both encodings "%20" and "+"'],

            ['foo[]=1&foo[]=2', 'foo%5B%5D=1&foo%5B%5D=2', 'allows array notation'],
            ['foo=1&foo=2', 'foo=1&foo=2', 'allows repeated parameters'],
            ['pa%3Dram=foo%26bar%3Dbaz&test=test', 'pa%3Dram=foo%26bar%3Dbaz&test=test', 'works with encoded delimiters'],
            ['0', '0', 'allows "0"'],
            ['Jane Doe&John%20Doe', 'Jane%20Doe&John%20Doe', 'normalizes encoding in keys'],
            ['her=Jane Doe&him=John%20Doe', 'her=Jane%20Doe&him=John%20Doe', 'normalizes encoding in values'],
            ['foo=bar&&&test&&', 'foo=bar&test', 'removes unneeded delimiters'],
            ['formula=e=m*c^2', 'formula=e%3Dm%2Ac%5E2', 'correctly treats only the first "=" as delimiter and the next as value'],

            // Ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
            // PHP also does not include them when building _GET.
            ['foo=bar&=a=b&=x=y', 'foo=bar', 'removes params with empty key'],
        ];
    }

    public function testGetQueryStringReturnsNull()
    {
        $request = new Request();

        $this->assertNull($request->getQueryString(), '->getQueryString() returns null for non-existent query string');

        $request->server->set('QUERY_STRING', '');
        $this->assertNull($request->getQueryString(), '->getQueryString() returns null for empty query string');
    }

    public function testGetHost()
    {
        $request = new Request();

        $request->initialize(['foo' => 'bar']);
        $this->assertEquals('/', $request->getHost(), '->getHost() return empty string if not initialized');

        $request->initialize([], [], [], [], [], ['HTTP_HOST' => 'www.exemple.com']);
        $this->assertEquals('www.exemple.com', $request->getHost(), '->getHost() from Host Header');

        // Host header with port number
        $request->initialize([], [], [], [], [], ['HTTP_HOST' => 'www.exemple.com:8080']);
        $this->assertEquals('www.exemple.com', $request->getHost(), '->getHost() from Host Header with port number');

        // Server values
        $request->initialize([], [], [], [], [], ['SERVER_NAME' => 'www.exemple.com']);
        $this->assertEquals('www.exemple.com', $request->getHost(), '->getHost() from server name');

        $request->initialize([], [], [], [], [], ['SERVER_NAME' => 'www.exemple.com', 'HTTP_HOST' => 'www.host.com']);
        $this->assertEquals('www.host.com', $request->getHost(), '->getHost() value from Host header has priority over SERVER_NAME ');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetHostWithFakeHttpHostValue()
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], ['HTTP_HOST' => 'www.host.com?query=string']);
        $request->getHost();
    }

    public function testGetSetMethod()
    {
        $request = new Request();

        $this->assertEquals('GET', $request->getMethod(), '->getMethod() returns GET if no method is defined');

        $request->setMethod('get');
        $this->assertEquals('GET', $request->getMethod(), '->getMethod() returns an uppercased string');

        $request->setMethod('PURGE');
        $this->assertEquals('PURGE', $request->getMethod(), '->getMethod() returns the method even if it is not a standard one');

        $request->setMethod('POST');
        $this->assertEquals('POST', $request->getMethod(), '->getMethod() returns the method POST if no _method is defined');

        $request->setMethod('POST');
        $request->request->set('_method', 'purge');
        $this->assertEquals('PURGE', $request->getMethod(), '->getMethod() returns the method from _method if defined and POST');

        $request->setMethod('POST');
        $request->request->remove('_method');
        $request->query->set('_method', 'purge');
        $this->assertEquals('PURGE', $request->getMethod(), '->getMethod() returns the method from _method if defined and POST');

        $request->setMethod('POST');
        $request->headers->set('X-HTTP-METHOD-OVERRIDE', 'delete');
        $this->assertEquals('DELETE', $request->getMethod(), '->getMethod() returns the method from X-HTTP-Method-Override even though _method is set if defined and POST');

        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('X-HTTP-METHOD-OVERRIDE', 'delete');
        $this->assertEquals('DELETE', $request->getMethod(), '->getMethod() returns the method from X-HTTP-Method-Override if defined and POST');
    }

    public function testGetContentWorksTwiceInDefaultMode()
    {
        $req = new Request;
        $this->assertEquals('', $req->getContent());
        $this->assertEquals('', $req->getContent());
    }

    public function testGetContentReturnsResource()
    {
        $req = new Request;
        $retval = $req->getContent(true);
        $this->assertInternalType('resource', $retval);
        $this->assertEquals("", fread($retval, 1));
        $this->assertTrue(feof($retval));
    }

    /**
     * @expectedException \LogicException
     * @dataProvider getContentCantBeCalledTwiceWithResourcesProvider
     */
    public function testGetContentCantBeCalledTwiceWithResources($first, $second)
    {
        $req = new Request;
        $req->getContent($first);
        $req->getContent($second);
    }

    public function getContentCantBeCalledTwiceWithResourcesProvider()
    {
        return [
            'Resource then fetch' => [true, false],
            'Resource then resource' => [true, true],
            'Fetch then resource' => [false, true],
        ];
    }

    public function provideOverloadedMethods()
    {
        return [['PUT'], ['DELETE'], ['PATCH']];
    }

    /**
     * @dataProvider provideOverloadedMethods
     */
    public function testCreateFromGlobals($method)
    {
        $_GET['foo1']    = 'bar1';
        $_POST['foo2']   = 'bar2';
        $_COOKIE['foo3'] = 'bar3';
        $_FILES['foo4']  = ['bar4'];
        $_SERVER['foo5'] = 'bar5';

        $request = Request::createFromGlobals();
        $this->assertEquals('bar1', $request->query->get('foo1'), '::fromGlobals() uses values from $_GET');
        $this->assertEquals('bar2', $request->request->get('foo2'), '::fromGlobals() uses values from $_POST');
        $this->assertEquals('bar3', $request->cookies->get('foo3'), '::fromGlobals() uses values from $_COOKIE');
        $this->assertEquals(['bar4'], $request->files->get('foo4'), '::fromGlobals() uses values from $_FILES');
        $this->assertEquals('bar5', $request->server->get('foo5'), '::fromGlobals() uses values from $_SERVER');

        unset($_GET['foo1'], $_POST['foo2'], $_COOKIE['foo3'], $_FILES['foo4'], $_SERVER['foo5']);

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $request = RequestContentProxy::createFromGlobals();
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals('mycontent', $request->request->get('content'));

        unset($_SERVER['REQUEST_METHOD'], $_SERVER['CONTENT_TYPE']);

        $_POST['_method'] = $method;
        $_POST['foo6'] = 'bar6';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Request::createFromGlobals();
        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals('bar6', $request->request->get('foo6'));

        unset($_POST['_method'], $_POST['foo6'], $_SERVER['REQUEST_METHOD']);
    }

    public function testGetScriptName()
    {
        $request = new Request();
        $this->assertEquals('', $request->getScriptName());

        $server = [];
        $server['SCRIPT_NAME'] = '/index.php';

        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/index.php', $request->getScriptName());

        $server = [];
        $server['ORIG_SCRIPT_NAME'] = '/frontend.php';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/frontend.php', $request->getScriptName());

        $server = [];
        $server['SCRIPT_NAME'] = '/index.php';
        $server['ORIG_SCRIPT_NAME'] = '/frontend.php';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/index.php', $request->getScriptName());
    }

    public function testGetBasePath()
    {
        $request = new Request();
        $this->assertEquals('/', $request->getBasePath());

        $server = [];
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';
        $request->initialize([], [], [], [], [], $server);
        $this->assertEquals('/', $request->getBasePath());

        $server = [];
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';
        $server['SCRIPT_NAME'] = '/index.php';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/', $request->getBasePath());

        $server = [];
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';
        $server['PHP_SELF'] = '/index.php';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/', $request->getBasePath());

        $server = [];
        $server['SCRIPT_FILENAME'] = '/some/where/index.php';
        $server['ORIG_SCRIPT_NAME'] = '/index.php';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/', $request->getBasePath());
    }

    public function testGetPathInfo()
    {
        $request = new Request();
        $this->assertEquals('/', $request->getPathInfo());

        $server = [];
        $server['REQUEST_URI'] = '/path/info';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/path/info', $request->getPathInfo());

        $server = [];
        $server['REQUEST_URI'] = '/path%20test/info';
        $request->initialize([], [], [], [], [], $server);

        $this->assertEquals('/path%20test/info', $request->getPathInfo());
    }

    public function testGetPreferredLanguage()
    {
        $request = new Request();
        $this->assertNull($request->getPreferredLanguage());
        $this->assertNull($request->getPreferredLanguage([]));
        $this->assertEquals('fr', $request->getPreferredLanguage(['fr']));
        $this->assertEquals('fr', $request->getPreferredLanguage(['fr', 'en']));
        $this->assertEquals('en', $request->getPreferredLanguage(['en', 'fr']));
        $this->assertEquals('fr-ch', $request->getPreferredLanguage(['fr-ch', 'fr-fr']));

        $request = new Request();
        $request->headers->set('Accept-language', 'zh, en-us; q=0.8, en; q=0.6');
        $this->assertEquals('en', $request->getPreferredLanguage(['en', 'en-us']));

        $request = new Request();
        $request->headers->set('Accept-language', 'zh, en-us; q=0.8, en; q=0.6');
        $this->assertEquals('en', $request->getPreferredLanguage(['fr', 'en']));
    }

    public function testIsXmlHttpRequest()
    {
        $request = new Request();
        $this->assertFalse($request->isXmlHttpRequest());

        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->isXmlHttpRequest());

        $request->headers->remove('X-Requested-With');
        $this->assertFalse($request->isXmlHttpRequest());
    }

    public function testIntlLocale()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('The intl extension is needed to run this test.');
        }

        $request = new Request();

        $request->setDefaultLocale('fr');
        $this->assertEquals('fr', $request->getLocale());
        $this->assertEquals('fr', \Locale::getDefault());

        $request->setLocale('en');
        $this->assertEquals('en', $request->getLocale());
        $this->assertEquals('en', \Locale::getDefault());

        $request->setDefaultLocale('de');
        $this->assertEquals('en', $request->getLocale());
        $this->assertEquals('en', \Locale::getDefault());
    }

    public function testGetCharsets()
    {
        $request = new Request();
        $this->assertEquals([], $request->getCharsets());
        $request->headers->set('Accept-Charset', 'ISO-8859-1, US-ASCII, UTF-8; q=0.8, ISO-10646-UCS-2; q=0.6');
        $this->assertEquals([], $request->getCharsets()); // testing caching

        $request = new Request();
        $request->headers->set('Accept-Charset', 'ISO-8859-1, US-ASCII, UTF-8; q=0.8, ISO-10646-UCS-2; q=0.6');
        $this->assertEquals(['ISO-8859-1', 'US-ASCII', 'UTF-8', 'ISO-10646-UCS-2'], $request->getCharsets());

        $request = new Request();
        $request->headers->set('Accept-Charset', 'ISO-8859-1,utf-8;q=0.7,*;q=0.7');
        $this->assertEquals(['ISO-8859-1', 'utf-8', '*'], $request->getCharsets());
    }

    public function testGetAcceptableContentTypes()
    {
        $request = new Request();
        $this->assertEquals([], $request->getAcceptableContentTypes());
        $request->headers->set('Accept', 'application/vnd.wap.wmlscriptc, text/vnd.wap.wml, application/vnd.wap.xhtml+xml, application/xhtml+xml, text/html, multipart/mixed, */*');
        $this->assertEquals([], $request->getAcceptableContentTypes()); // testing caching

        $request = new Request();
        $request->headers->set('Accept', 'application/vnd.wap.wmlscriptc, text/vnd.wap.wml, application/vnd.wap.xhtml+xml, application/xhtml+xml, text/html, multipart/mixed, */*');
        $this->assertEquals(
            ['application/vnd.wap.wmlscriptc', 'text/vnd.wap.wml', 'application/vnd.wap.xhtml+xml', 'application/xhtml+xml', 'text/html', 'multipart/mixed', '*/*'],
            $request->getAcceptableContentTypes()
        );
    }

    public function testGetLanguages()
    {
        $request = new Request();
        $this->assertEquals([], $request->getLanguages());

        $request = new Request();
        $request->headers->set('Accept-language', 'zh, en-us; q=0.8, en; q=0.6');
        $this->assertEquals(['zh', 'en_US', 'en'], $request->getLanguages());
        $this->assertEquals(['zh', 'en_US', 'en'], $request->getLanguages());

        $request = new Request();
        $request->headers->set('Accept-language', 'zh, en-us; q=0.6, en; q=0.8');
        $this->assertEquals(['zh', 'en', 'en_US'], $request->getLanguages()); // Test out of order qvalues

        $request = new Request();
        $request->headers->set('Accept-language', 'zh, en, en-us');
        $this->assertEquals(['zh', 'en', 'en_US'], $request->getLanguages()); // Test equal weighting without qvalues

        $request = new Request();
        $request->headers->set('Accept-language', 'zh; q=0.6, en, en-us; q=0.6');
        $this->assertEquals(['en', 'zh', 'en_US'], $request->getLanguages()); // Test equal weighting with qvalues

        $request = new Request();
        $request->headers->set('Accept-language', 'zh, i-cherokee; q=0.6');
        $this->assertEquals(['zh', 'cherokee'], $request->getLanguages());
    }

    public function testGetRequestFormat()
    {
        $request = new Request();
        $this->assertEquals('html', $request->getRequestFormat());

        $request = new Request();
        $this->assertNull($request->getRequestFormat(null));

        $request = new Request();
        $this->assertNull($request->setRequestFormat('foo'));
        $this->assertEquals('foo', $request->getRequestFormat(null));
    }

    public function testToString()
    {
        $request = new Request();
        $request->headers->set('Accept-language', 'zh, en-us; q=0.8, en; q=0.6');
        $this->assertContains('Accept-Language: zh, en-us; q=0.8, en; q=0.6', $request->__toString());
    }

    /**
     * @dataProvider splitHttpAcceptHeaderData
     */
    public function testSplitHttpAcceptHeader($acceptHeader, $expected)
    {
        $request = new Request();

        $this->assertEquals($expected, $request->splitHttpAcceptHeader($acceptHeader));
    }

    public function splitHttpAcceptHeaderData()
    {
        return [
            [null, []],
            ['text/html;q=0.8', ['text/html' => 0.8]],
            ['text/html;foo=bar;q=0.8 ', ['text/html;foo=bar' => 0.8]],
            ['text/html;charset=utf-8; q=0.8', ['text/html;charset=utf-8' => 0.8]],
            ['text/html,application/xml;q=0.9,*/*;charset=utf-8; q=0.8', ['text/html' => 1, 'application/xml' => 0.9, '*/*;charset=utf-8' => 0.8]],
            ['text/html,application/xhtml+xml;q=0.9,*/*;q=0.8; foo=bar', ['text/html' => 1, 'application/xhtml+xml' => 0.9, '*/*' => 0.8]],
            ['text/html,application/xhtml+xml;charset=utf-8;q=0.9; foo=bar,*/*', ['text/html' => 1, '*/*' => 1, 'application/xhtml+xml;charset=utf-8' => 0.9]],
            ['text/html,application/xhtml+xml', ['application/xhtml+xml' => 1, 'text/html' => 1]],
        ];
    }

    public function testIsMethod()
    {
        $request = new Request();
        $request->setMethod('POST');
        $this->assertTrue($request->isMethod('POST'));
        $this->assertTrue($request->isMethod('post'));
        $this->assertFalse($request->isMethod('GET'));
        $this->assertFalse($request->isMethod('get'));

        $request->setMethod('GET');
        $this->assertTrue($request->isMethod('GET'));
        $this->assertTrue($request->isMethod('get'));
        $this->assertFalse($request->isMethod('POST'));
        $this->assertFalse($request->isMethod('post'));
    }

    /**
     * @dataProvider getBaseUrlData
     */
    public function testGetBaseUrl($uri, $server, $expectedBaseUrl, $expectedPathInfo)
    {
        $request = Request::create($uri, 'GET', [], [], [], $server);

        $this->assertSame($expectedBaseUrl, $request->getBaseUrl(), 'baseUrl');
        $this->assertSame($expectedPathInfo, $request->getPathInfo(), 'pathInfo');
    }

    public function getBaseUrlData()
    {
        return [
            [
                '/foo%20bar',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '',
            ],
            [
                '/foo%20bar/home',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar',
                '/home',
            ],
            [
                '/foo%20bar/app.php/home',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar/app.php',
                '/home',
            ],
            [
                '/foo%20bar/app.php/home%3Dbaz',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo bar/app.php',
                    'SCRIPT_NAME' => '/foo bar/app.php',
                    'PHP_SELF' => '/foo bar/app.php',
                ],
                '/foo%20bar/app.php',
                '/home%3Dbaz',
            ],
            [
                '/foo/bar+baz',
                [
                    'SCRIPT_FILENAME' => '/home/John Doe/public_html/foo/app.php',
                    'SCRIPT_NAME' => '/foo/app.php',
                    'PHP_SELF' => '/foo/app.php',
                ],
                '/foo',
                '/bar+baz',
            ],
        ];
    }

    /**
     * @dataProvider urlencodedStringPrefixData
     */
    public function testUrlencodedStringPrefix($string, $prefix, $expect)
    {
        $request = new Request;

        $me = new \ReflectionMethod($request, 'getUrlencodedPrefix');
        $me->setAccessible(true);

        $this->assertSame($expect, $me->invoke($request, $string, $prefix));
    }

    public function urlencodedStringPrefixData()
    {
        return [
            ['foo', 'foo', 'foo'],
            ['fo%6f', 'foo', 'fo%6f'],
            ['foo/bar', 'foo', 'foo'],
            ['fo%6f/bar', 'foo', 'fo%6f'],
            ['f%6f%6f/bar', 'foo', 'f%6f%6f'],
            ['%66%6F%6F/bar', 'foo', '%66%6F%6F'],
            ['fo+o/bar', 'fo+o', 'fo+o'],
            ['fo%2Bo/bar', 'fo+o', 'fo%2Bo'],
        ];
    }
}

class RequestContentProxy extends Request
{
    public function getContent($asResource = false)
    {
        return http_build_query(['_method' => 'PUT', 'content' => 'mycontent']);
    }
}
