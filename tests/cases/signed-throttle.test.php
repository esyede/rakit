<?php

defined('DS') or exit('No direct access.');

use System\Cache;
use System\Config;
use System\Request;
use System\URL;
use System\Routing\Middleware;
use System\Routing\Throttle;
use System\Foundation\Http\Request as FoundationRequest;

class SignedThrottleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Framework state replaced by the tests.
     *
     * @var array
     */
    protected $backup = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->backup = [
            'url' => Config::get('application.url'),
            'foundation' => Request::$foundation,
            'route' => Request::$route,
        ];

        Config::set('application.url', 'http://example.test');
        URL::$base = null;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('application.url', $this->backup['url']);
        URL::$base = null;
        Request::$foundation = $this->backup['foundation'];
        Request::$route = $this->backup['route'];
        Request::reset_foundation();
    }

    /**
     * Point the current request at a URL.
     *
     * @param string $url
     */
    protected function visit($url)
    {
        Request::$foundation = FoundationRequest::create($url);
        Request::reset_foundation();
    }

    /**
     * A signed URL verifies; any change to it does not.
     *
     * @group system
     */
    public function testSignedUrlsVerify()
    {
        $url = URL::signed('unsubscribe?user=7&list=news');

        $this->visit($url);
        $this->assertTrue(URL::has_valid_signature());

        $this->visit(str_replace('user=7', 'user=8', $url));
        $this->assertFalse(URL::has_valid_signature());

        $this->visit($url.'&extra=1');
        $this->assertFalse(URL::has_valid_signature());

        $this->visit(preg_replace('/&signature=[^&]+/', '', $url));
        $this->assertFalse(URL::has_valid_signature());

        $this->visit(str_replace('example.test', 'evil.test', $url));
        $this->assertFalse(URL::has_valid_signature());

        $this->visit(preg_replace('/signature=[^&]+/', 'signature[]=x', $url));
        $this->assertFalse(URL::has_valid_signature());
    }

    /**
     * A relative signature ignores the scheme and host, nothing else.
     *
     * @group system
     */
    public function testRelativeSignaturesIgnoreTheHost()
    {
        $url = URL::signed('download?file=a', null, false);

        $this->visit(str_replace('http://example.test', 'https://internal:8080', $url));
        $this->assertTrue(URL::has_valid_signature(false));
        $this->assertFalse(URL::has_valid_signature(true));

        $this->visit(str_replace('file=a', 'file=b', $url));
        $this->assertFalse(URL::has_valid_signature(false));
    }

    /**
     * An expiring signature is refused once expired, or when the expiry is altered.
     *
     * @group system
     */
    public function testTemporarySignedUrls()
    {
        $url = URL::signed('confirm', 10);
        $this->assertContains('expires=', $url);

        $this->visit($url);
        $this->assertTrue(URL::has_valid_signature());

        $this->visit(preg_replace('/expires=\d+/', 'expires='.(time() + 9999), $url));
        $this->assertFalse(URL::has_valid_signature());

        $expired = URL::signed('confirm', -1);
        $this->visit($expired);
        $this->assertFalse(URL::has_valid_signature());
    }

    /**
     * The 'signed' middleware answers 403 for an unsigned request.
     *
     * @group system
     */
    public function testSignedMiddleware()
    {
        $signed = Middleware::$middlewares['signed'];

        $this->visit(URL::signed('confirm'));
        $this->assertNull($signed());

        $this->visit('http://example.test/index.php/confirm');
        $this->assertEquals(403, $signed()->status());
    }

    /**
     * A named limiter counts its own bucket, by the key it returns.
     *
     * @group system
     */
    public function testNamedLimiters()
    {
        $by = 'user-1';

        Throttle::define('signed-throttle-test', function () use (&$by) {
            return ['max' => 2, 'minutes' => 1, 'by' => $by];
        });

        Throttle::define('signed-throttle-none', function () {
            return null;
        });

        Throttle::clear('limiter|signed-throttle-test|user-1');
        Throttle::clear('limiter|signed-throttle-test|user-2');

        $this->assertNull(Throttle::attempt('signed-throttle-test'));
        $this->assertNull(Throttle::attempt('signed-throttle-test'));

        $response = Throttle::attempt('signed-throttle-test');
        $this->assertEquals(429, $response->status());
        $this->assertEquals(2, $response->foundation()->headers->get('X-Rate-Limit-Limit'));

        // Another user has a bucket of their own.
        $by = 'user-2';
        $this->assertNull(Throttle::attempt('signed-throttle-test'));

        for ($i = 0; $i < 5; ++$i) {
            $this->assertNull(Throttle::attempt('signed-throttle-none'));
        }

        Throttle::clear('limiter|signed-throttle-test|user-1');
        Throttle::clear('limiter|signed-throttle-test|user-2');
    }

    /**
     * An undefined limiter is a configuration error.
     *
     * @group system
     */
    public function testUndefinedLimiter()
    {
        $this->setExpectedException('Exception', 'Undefined rate limiter');
        Throttle::attempt('signed-throttle-missing');
    }

    /**
     * A custom key counts on its own and is reset by clear().
     *
     * @group system
     */
    public function testCustomKeys()
    {
        Throttle::clear('login|a@b.co');

        $this->assertFalse(Throttle::exceeded(1, 1, 'login|a@b.co'));
        $this->assertTrue(Throttle::exceeded(1, 1, 'login|a@b.co'));
        $this->assertFalse(Throttle::exceeded(1, 1, 'login|c@d.co'));

        Throttle::clear('login|a@b.co');
        $this->assertFalse(Throttle::exceeded(1, 1, 'login|a@b.co'));

        Throttle::clear('login|a@b.co');
        Throttle::clear('login|c@d.co');
    }

    /**
     * The 'throttle' middleware still takes a limit and minutes.
     *
     * @group system
     */
    public function testThrottleMiddlewareKeepsTheNumericForm()
    {
        $throttle = Middleware::$middlewares['throttle'];
        Throttle::clear();

        $this->assertNull($throttle(1, 1));
        $this->assertEquals(429, $throttle(1, 1)->status());

        Throttle::clear();
    }
}
