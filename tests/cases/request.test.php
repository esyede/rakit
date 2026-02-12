<?php

defined('DS') or exit('No direct access.');

use System\Request;
use System\Session;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        defined('RAKIT_START') or define('RAKIT_START', microtime(true));
        $this->restartRequest();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $_POST = [];
        $scriptname = $_SERVER['SCRIPT_NAME'];
        $_SERVER = [];
        $_SERVER['SCRIPT_NAME'] = $scriptname;
        Request::$route = null;
        Session::$instance = null;
    }

    /**
     * Helper: set value in $_SERVER.
     *
     * @param string $key
     * @param string $value
     */
    protected function setServerVar($key, $value)
    {
        $_SERVER[$key] = $value;
        $this->restartRequest();
    }

    /**
     * Helper: set value in $_POST.
     *
     * @param string $key
     * @param string $value
     */
    protected function setPostVar($key, $value)
    {
        $_POST[$key] = $value;
        $this->restartRequest();
    }

    /**
     * Helper: Re-initialize the Request object.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];
        // Ensure the SCRIPT_NAME exists
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        // Ensure the HTTP_HOST exists
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();
        // Reset cache foundation
        Request::reset_foundation();
    }

    /**
     * Test for Request::method().
     *
     * @group system
     */
    public function testMethodReturnsTheHTTPRequestMethod()
    {
        $this->setServerVar('REQUEST_METHOD', 'POST');
        $this->assertEquals('POST', Request::method());

        $this->setPostVar(Request::SPOOFER, 'PUT');
        $this->assertEquals('PUT', Request::method());
    }

    /**
     * Test for Request::server().
     *
     * @group system
     */
    public function testServerMethodReturnsFromServerArray()
    {
        $this->setServerVar('TEST', 'something');
        $this->setServerVar('USER', ['NAME' => 'budi']);

        $this->assertEquals('something', Request::server('test'));
        $this->assertEquals('budi', Request::server('user.name'));
    }

    /**
     * Test for Request::ip().
     *
     * @group system
     */
    public function testIPMethodReturnsClientIPAddress()
    {
        $this->setServerVar('REMOTE_ADDR', '192.168.1.100');
        $this->assertEquals('192.168.1.100', Request::ip());

        $this->setServerVar('REMOTE_ADDR', '10.0.0.5');
        $this->assertEquals('10.0.0.5', Request::ip());

        $this->setServerVar('REMOTE_ADDR', '172.16.0.1');
        $this->assertEquals('172.16.0.1', Request::ip());

        $scriptname = $_SERVER['SCRIPT_NAME'];
        $_SERVER = [];
        $_SERVER['SCRIPT_NAME'] = $scriptname;

        $this->restartRequest();
        $this->assertEquals('0.0.0.0', Request::ip());
    }

    /**
     * Test for Request::secure().
     *
     * @group system
     */
    public function testSecureMethodsIndicatesIfHTTPS()
    {
        $this->setServerVar('HTTPS', 'on');
        $this->assertTrue(Request::secure());

        $this->setServerVar('HTTPS', 'off');
        $this->assertFalse(Request::secure());
    }

    /**
     * Test for Request::ajax().
     *
     * @group system
     */
    public function testAjaxMethodIndicatesWhenAjax()
    {
        $this->assertFalse(Request::ajax());

        $this->setServerVar('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->assertTrue(Request::ajax());
    }

    /**
     * Test for Request::forged().
     *
     * @group system
     */
    public function testForgedMethodIndicatesIfRequestWasForged()
    {
        Session::$instance = new SessionPayloadTokenStub();

        // Set to POST to run the CSRF check
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();
        Request::reset_foundation();

        $input = [Session::TOKEN => 'Budi'];
        Request::foundation()->request->add($input);

        $this->assertFalse(Request::forged());

        // Test for forged request
        $input2 = [Session::TOKEN => 'WrongToken'];
        Request::foundation()->request->replace($input2);

        $this->assertTrue(Request::forged());
    }

    /**
     * Test for Request::route().
     *
     * @group system
     */
    public function testRouteMethodReturnsStaticRoute()
    {
        Request::$route = 'Budi';
        $this->assertEquals('Budi', Request::route());
    }

    /**
     * Test for Request::uri().
     *
     * @group system
     */
    public function testUriReturnsCurrentRequestURI()
    {
        $this->assertEquals('/', Request::uri());
    }

    /**
     * Test for Request::is_method().
     *
     * @group system
     */
    public function testIsMethodChecksRequestMethod()
    {
        $this->setServerVar('REQUEST_METHOD', 'POST');
        $this->assertTrue(Request::is_method('POST'));
        $this->assertFalse(Request::is_method('GET'));
    }

    /**
     * Test for Request::headers().
     *
     * @group system
     */
    public function testHeadersReturnsAllHTTPHeaders()
    {
        $this->setServerVar('HTTP_USER_AGENT', 'TestAgent');
        $this->assertEquals('TestAgent', Request::server('HTTP_USER_AGENT'));
    }

    /**
     * Test for Request::servers().
     *
     * @group system
     */
    public function testServersReturnsAllServerVariables()
    {
        $this->setServerVar('TEST_VAR', 'value');
        $servers = Request::servers();
        $this->assertArrayHasKey('TEST_VAR', $servers);
        $this->assertEquals('value', $servers['TEST_VAR']);
    }

    /**
     * Test for Request::spoofed().
     *
     * @group system
     */
    public function testSpoofedChecksIfMethodIsSpoofed()
    {
        $this->assertFalse(Request::spoofed());
        $this->setPostVar(Request::SPOOFER, 'PUT');
        $this->assertTrue(Request::spoofed());
    }

    /**
     * Test for Request::accept().
     *
     * @group system
     */
    public function testAcceptReturnsAcceptableContentTypes()
    {
        $this->setServerVar('HTTP_ACCEPT', 'text/html,application/json');
        $accepts = Request::accept();
        $this->assertContains('text/html', $accepts);
        $this->assertContains('application/json', $accepts);
    }

    /**
     * Test for Request::accepts().
     *
     * @group system
     */
    public function testAcceptsChecksIfContentTypeIsAccepted()
    {
        $this->setServerVar('HTTP_ACCEPT', 'text/html,application/json');
        $this->assertTrue(Request::accepts('text/html'));
        $this->assertTrue(Request::accepts('application/json'));
    }

    /**
     * Test for Request::prefers().
     *
     * @group system
     */
    public function testPrefersReturnsPreferredContentType()
    {
        $this->setServerVar('HTTP_ACCEPT', 'text/html,application/json;q=0.9');
        $this->assertEquals('text/html', Request::prefers('text/html', 'application/json'));
    }

    /**
     * Test for Request::accept_html().
     *
     * @group system
     */
    public function testAcceptHtmlChecksIfHTMLIsAccepted()
    {
        $this->setServerVar('HTTP_ACCEPT', 'text/html');
        $this->assertTrue(Request::accept_html());
    }

    /**
     * Test for Request::accept_any().
     *
     * @group system
     */
    public function testAcceptAnyChecksIfAnyContentTypeIsAccepted()
    {
        $this->setServerVar('HTTP_ACCEPT', '*/*');
        $this->assertTrue(Request::accept_any());
    }

    /**
     * Test for Request::matches_type().
     *
     * @group system
     */
    public function testMatchesTypeChecksContentTypeMatch()
    {
        $this->assertTrue(Request::matches_type('text/html', 'text/html'));
    }

    /**
     * Test for Request::is_json().
     *
     * @group system
     */
    public function testIsJsonChecksIfContentTypeIsJson()
    {
        $this->setServerVar('CONTENT_TYPE', 'application/json');
        $this->assertTrue(Request::is_json());
    }

    /**
     * Test for Request::expects_json().
     *
     * @group system
     */
    public function testExpectsJsonChecksIfJsonResponseIsExpected()
    {
        $this->setServerVar('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->assertTrue(Request::expects_json());
    }

    /**
     * Test for Request::wants_json().
     *
     * @group system
     */
    public function testWantsJsonChecksIfJsonIsWanted()
    {
        $this->setServerVar('HTTP_ACCEPT', 'application/json');
        $this->assertTrue(Request::wants_json());
    }

    /**
     * Test for Request::authorization().
     *
     * @group system
     */
    public function testAuthorizationReturnsAuthorizationHeader()
    {
        $this->setServerVar('HTTP_AUTHORIZATION', 'Bearer token123');
        $this->assertEquals('Bearer token123', Request::authorization());
    }

    /**
     * Test for Request::bearer().
     *
     * @group system
     */
    public function testBearerReturnsBearerToken()
    {
        $this->setServerVar('HTTP_AUTHORIZATION', 'Bearer token123');
        $this->assertEquals('token123', Request::bearer());
        $this->setServerVar('HTTP_AUTHORIZATION', 'Basic auth');
        $this->assertNull(Request::bearer());
    }

    /**
     * Test for Request::content().
     *
     * @group system
     */
    public function testContentReturnsRequestBody()
    {
        $this->assertNotNull(Request::content());
    }

    /**
     * Test for Request::languages().
     *
     * @group system
     */
    public function testLanguagesReturnsAcceptableLanguages()
    {
        $this->setServerVar('HTTP_ACCEPT_LANGUAGE', 'en-US,en;q=0.9');
        $languages = Request::languages();
        $this->assertNotEmpty($languages);
    }

    /**
     * Test for Request::agent().
     *
     * @group system
     */
    public function testAgentReturnsUserAgent()
    {
        $this->setServerVar('HTTP_USER_AGENT', 'TestAgent');
        $this->assertEquals('TestAgent', Request::agent());
    }

    /**
     * Test for Request::pjax().
     *
     * @group system
     */
    public function testPjaxChecksIfRequestIsPjax()
    {
        $this->setServerVar('HTTP_X_PJAX', 'true');
        $this->assertTrue(Request::pjax());
    }

    /**
     * Test for Request::prefetch().
     *
     * @group system
     */
    public function testPrefetchChecksIfRequestIsPrefetch()
    {
        $this->setServerVar('HTTP_X_MOZ', 'prefetch');
        $this->assertTrue(Request::prefetch());
    }

    /**
     * Test for Request::referrer().
     *
     * @group system
     */
    public function testReferrerReturnsHTTPReferrer()
    {
        $this->setServerVar('HTTP_REFERER', 'http://example.com');
        $this->assertEquals('http://example.com', Request::referrer());
    }

    /**
     * Test for Request::time().
     *
     * @group system
     */
    public function testTimeReturnsRequestStartTime()
    {
        $this->assertTrue(is_numeric(Request::time()));
    }

    /**
     * Test for Request::cli().
     *
     * @group system
     */
    public function testCliChecksIfRequestIsFromConsole()
    {
        $this->assertTrue(is_bool(Request::cli()));
    }

    /**
     * Test for Request::env().
     *
     * @group system
     */
    public function testEnvReturnsRequestEnvironment()
    {
        $this->setServerVar('RAKIT_ENV', 'testing');
        $this->assertEquals('testing', Request::env());
    }

    /**
     * Test for Request::set_env().
     *
     * @group system
     */
    public function testSetEnvSetsRequestEnvironment()
    {
        Request::set_env('production');
        $this->assertEquals('production', Request::env());
    }

    /**
     * Test for Request::is_env().
     *
     * @group system
     */
    public function testIsEnvChecksRequestEnvironment()
    {
        Request::set_env('development');
        $this->assertTrue(Request::is_env('development'));
        $this->assertFalse(Request::is_env('production'));
    }

    /**
     * Test for Request::detect_env().
     *
     * @group system
     */
    public function testDetectEnvDetectsEnvironmentBasedOnPatterns()
    {
        $environments = [
            'local' => ['localhost'],
            'production' => ['example.com'],
        ];

        $this->assertEquals('local', Request::detect_env($environments, 'localhost'));
        $this->assertNull(Request::detect_env($environments, 'unknown.com'));
    }

    /**
     * Test for Request::subdomain().
     *
     * @group system
     */
    public function testSubdomainReturnsRequestSubdomain()
    {
        $this->setServerVar('HTTP_HOST', 'sub.example.com');
        $this->assertEquals('sub', Request::subdomain());
    }
}

class SessionPayloadTokenStub
{
    public function token()
    {
        return 'Budi';
    }

    public function get($key, $default = null)
    {
        return ($key === Session::TOKEN) ? 'Budi' : $default;
    }
}
