<?php

defined('DS') or exit('No direct access.');

use System\Maintenance;
use System\Routing\Router;

class MaintenanceTest extends \PHPUnit_Framework_TestCase
{
    use \System\Testing\Http;

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
            'foundation' => \System\Request::$foundation,
            'route' => \System\Request::$route,
            'session' => \System\Session::$instance,
        ];

        Router::register('GET', 'mt/page', function () {
            return 'page';
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Maintenance::up();
        unset(Router::$routes['GET']['mt/page']);

        \System\Cookie::flush();
        \System\Session::$instance = $this->backup['session'];
        \System\Request::$route = $this->backup['route'];
        \System\Request::$foundation = $this->backup['foundation'];
        \System\Request::reset_foundation();
    }

    /**
     * Down answers 503 with Retry-After; up brings the pages back.
     *
     * @group system
     */
    public function testDownAndUp()
    {
        $this->assertFalse(Maintenance::active());
        $this->get('mt/page')->assert_ok();

        Maintenance::down(null, 60);
        $this->assertTrue(Maintenance::active());

        $this->get('mt/page')->assert_status(503)->assert_header('Retry-After', '60');
        $this->json('GET', 'mt/page')->assert_status(503)->assert_json(['status' => 503]);

        Maintenance::up();
        $this->assertFalse(Maintenance::active());
        $this->get('mt/page')->assert_ok()->assert_see('page');
    }

    /**
     * The secret URI sets a bypass cookie, which a new secret invalidates.
     *
     * @group system
     */
    public function testSecretBypass()
    {
        Maintenance::down('let-me-in');

        $this->get('let-me-out')->assert_status(503);
        $this->get('let-me-in')->assert_redirect('/')->assert_cookie(Maintenance::COOKIE);
        $this->get('mt/page')->assert_ok();

        Maintenance::down('another-secret');
        $this->get('mt/page')->assert_status(503);

        $this->forget_client();
        $this->get('mt/page')->assert_status(503);
    }

    /**
     * Allowed client IPs go through.
     *
     * @group system
     */
    public function testAllowedIps()
    {
        Maintenance::down(null, null, ['10.0.0.9']);
        $this->get('mt/page')->assert_status(503);

        Maintenance::down(null, null, ['10.0.0.9', '127.0.0.1']);
        $this->get('mt/page')->assert_ok();
    }

    /**
     * Only a hash of the secret is stored, behind the PHP guard.
     *
     * @group system
     */
    public function testTheSecretIsNotStored()
    {
        Maintenance::down('plain-secret', 30);
        $content = file_get_contents(Maintenance::path());

        $this->assertStringStartsWith("<?php defined('DS') or exit('No direct access.');?>", $content);
        $this->assertNotContains('plain-secret', $content);
        $this->assertSame(30, Maintenance::data()['retry']);
    }
}
