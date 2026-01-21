<?php

defined('DS') or exit('No direct access.');

use System\Str;
use System\Config;
use System\Cookie;
use System\Crypter;
use System\Session;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Session::$instance = null;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Session::$instance = null;
    }

    /**
     * Test untuk method Session::__callStatic().
     *
     * @group system
     */
    public function testPayloadCanBeCalledStaticly()
    {
        Session::$instance = new DummyPayload();
        $this->assertEquals('Foo', Session::test());
    }

    /**
     * Test untuk method Session::started().
     *
     * @group system
     */
    public function testStartedMethodIndicatesIfSessionIsStarted()
    {
        $this->assertFalse(Session::started());

        Session::$instance = 'foo';

        $this->assertTrue(Session::started());
    }

    /**
     * Test untuk method Payload::load() - 1.
     *
     * @group system
     */
    public function testLoadMethodCreatesNewSessionWithNullIDGiven()
    {
        $payload = $this->getPayload();
        $payload->load(null);

        $this->verifyNewSession($payload);
    }

    /**
     * Test untuk method Payload::load() - 2.
     *
     * @group system
     */
    public function testLoadMethodCreatesNewSessionWhenSessionIsExpired()
    {
        $payload = $this->getPayload();
        $session = $this->getSession();
        $session['last_activity'] = time() - 10000;

        $payload->driver->expects($this->any())
            ->method('load')
            ->will($this->returnValue($session));

        $payload->load('foo');

        $this->verifyNewSession($payload);
        $this->assertTrue($payload->session['id'] !== $session['id']);
    }

    /**
     * Pastikan bahwa session saat ini merupakan session baru.
     *
     * @param Payload $payload
     *
     * @return void
     */
    protected function verifyNewSession($payload)
    {
        $this->assertFalse($payload->exists);
        $this->assertTrue(isset($payload->session['id']));
        $this->assertEquals([], $payload->session['data'][':new:']);
        $this->assertEquals([], $payload->session['data'][':old:']);
        $this->assertTrue(isset($payload->session['data'][Session::TOKEN]));
    }

    /**
     * Test untuk method Payload::load() - 3.
     *
     * @group system
     */
    public function testLoadMethodSetsValidSession()
    {
        $payload = $this->getPayload();
        $session = $this->getSession();

        $payload->driver->expects($this->any())
            ->method('load')
            ->will($this->returnValue($session));

        $payload->load('foo');

        $this->assertEquals($session, $payload->session);
    }

    /**
     * Test untuk method Payload::load() - 4.
     *
     * @group system
     */
    public function testLoadMethodSetsCSRFTokenIfDoesntExist()
    {
        $payload = $this->getPayload();
        $session = $this->getSession();

        unset($session['data'][Session::TOKEN]);

        $payload->driver->expects($this->any())
            ->method('load')
            ->will($this->returnValue($session));

        $payload->load('foo');

        $this->assertEquals('foo', $payload->session['id']);
        $this->assertTrue(isset($payload->session['data'][Session::TOKEN]));
    }

    /**
     * Test untuk method Session::has() dan Session::get().
     *
     * @group system
     */
    public function testSessionDataCanBeRetrievedProperly()
    {
        $payload = $this->getPayload();
        $payload->session = $this->getSession();

        $this->assertTrue($payload->has('name'));
        $this->assertEquals('Budi', $payload->get('name'));

        $this->assertFalse($payload->has('foo'));
        $this->assertEquals('Default', $payload->get('foo', 'Default'));

        $this->assertTrue($payload->has('votes'));
        $this->assertEquals(10, $payload->get('votes'));

        $this->assertTrue($payload->has('city'));
        $this->assertEquals('JKT', $payload->get('city'));
    }

    /**
     * Test pengambilan data session.
     *
     * @group system
     */
    public function testDataCanBeSetProperly()
    {
        $payload = $this->getPayload();

        $payload->session = $this->getSession();

        // Test untuk method Session::put() and Session::flash().
        $payload->put('name', 'Weldon');
        $this->assertEquals('Weldon', $payload->session['data']['name']);

        $payload->flash('language', 'php');
        $this->assertEquals('php', $payload->session['data'][':new:']['language']);

        // Test untuk method Session::reflash().
        $payload->session['data'][':new:'] = ['name' => 'Budi'];
        $payload->session['data'][':old:'] = ['age' => 25];
        $payload->reflash();

        $this->assertEquals(['name' => 'Budi', 'age' => 25], $payload->session['data'][':new:']);

        // Test untuk method Session::keep().
        $payload->session['data'][':new:'] = [];
        $payload->keep(['age']);
        $this->assertEquals(25, $payload->session['data'][':new:']['age']);
    }

    /**
     * Test untuk method Payload::forget().
     *
     * @group system
     */
    public function testSessionDataCanBeForgotten()
    {
        $payload = $this->getPayload();
        $payload->session = $this->getSession();

        $this->assertTrue(isset($payload->session['data']['name']));

        $payload->forget('name');

        $this->assertFalse(isset($payload->session['data']['name']));
    }

    /**
     * Test untuk method Payload::flush().
     *
     * @group system
     */
    public function testFlushMaintainsTokenButDeletesEverythingElse()
    {
        $payload = $this->getPayload();
        $payload->session = $this->getSession();

        $this->assertTrue(isset($payload->session['data']['name']));

        $payload->flush();

        $this->assertFalse(isset($payload->session['data']['name']));
        $this->assertEquals('bar', $payload->session['data'][Session::TOKEN]);
        $this->assertEquals([], $payload->session['data'][':new:']);
        $this->assertEquals([], $payload->session['data'][':old:']);
    }

    /**
     * Test untuk method Payload::regenerate().
     *
     * @group system
     */
    public function testRegenerateMethodSetsNewIDAndTurnsOffExistenceIndicator()
    {
        $payload = $this->getPayload();

        $payload->sesion = $this->getSession();
        $payload->exists = true;

        $payload->regenerate();

        $this->assertFalse($payload->exists);
        $this->assertTrue(40 === mb_strlen($payload->session['id'], '8bit'));
    }

    /**
     * Test untuk method Payload::token().
     *
     * @group system
     */
    public function testTokenMethodReturnsCSRFToken()
    {
        $payload = $this->getPayload();
        $payload->session = $this->getSession();

        $this->assertEquals('bar', $payload->token());
    }

    /**
     * Test untuk method Payload::save() - 1.
     *
     * @group system
     */
    public function testSaveMethodCorrectlyCallsDriver()
    {
        $payload = $this->getPayload();
        $session = $this->getSession();

        $payload->session = $session;
        $payload->exists = true;

        $config = Config::get('session');
        $expect = $session;

        $expect['data'][':old:'] = $session['data'][':new:'];
        $expect['data'][':new:'] = [];

        $payload->driver->expects($this->once())
            ->method('save')
            ->with(
                $this->equalTo($expect),
                $this->equalTo($config),
                $this->equalTo(true)
            );

        $payload->save();

        $this->assertEquals($session['data'][':new:'], $payload->session['data'][':old:']);
    }



    /**
     * Test untuk method Payload::save() - 4.
     *
     * @group system
     */
    public function testSaveMethodSetsCookieWithCorrectValues()
    {
        $payload = $this->getPayload();
        $payload->session = $this->getSession();
        $payload->save();

        $this->assertTrue(isset(Cookie::$jar[Config::get('session.cookie')]));

        $cookie = Cookie::$jar[Config::get('session.cookie')];
        $this->assertEquals('foo', Crypter::decrypt($cookie['value']));

        // Tidak bisa di-test karena default cookie expiration di PHP disimpan
        // dalam bentuk timestamp sedangkan rakit menggunakan menit.
        // $this->assertEquals(Config::get('session.lifetime'), $cookie['expiration']);

        $this->assertEquals(Config::get('session.domain'), $cookie['domain']);
        $this->assertEquals(Config::get('session.path'), $cookie['path']);
        $this->assertEquals(Config::get('session.secure'), $cookie['secure']);
    }

    /**
     * Test untuk method Session::activity().
     *
     * @group system
     */
    public function testActivityMethodReturnsLastActivity()
    {
        $payload = $this->getPayload();
        $payload->session['last_activity'] = 10;

        $this->assertEquals(10, $payload->activity());
    }

    /**
     * Ambil instance payload baru.
     *
     * @return Payload
     */
    protected function getPayload()
    {
        return new \System\Session\Payload($this->getMockDriver());
    }

    /**
     * Ambil instance mock driver baru.
     *
     * @return Driver
     */
    protected function getMockDriver()
    {
        $mock = $this->getMock('\System\Session\Drivers\Driver', ['id', 'load', 'save', 'delete']);

        $mock->expects($this->any())
            ->method('id')
            ->will($this->returnValue(Str::random(40)));

        return $mock;
    }

    /**
     * Ambil data dummy session.
     *
     * @return array
     */
    protected function getSession()
    {
        $data = [
            'name' => 'Budi',
            'age' => 25,
            Session::TOKEN => 'bar',
            ':new:' => ['votes' => 10],
            ':old:' => ['city' => 'JKT'],
        ];

        return ['id' => 'foo', 'last_activity' => time(), 'data' => $data];
    }
}

class DummyPayload
{
    public function test()
    {
        return 'Foo';
    }
}
