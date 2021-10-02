<?php

defined('DS') or exit('No direct script access.');

use System\Foundation\Http\Request as FoundationRequest;

use System\Str;
use System\Auth;
use System\Cookie;
use System\Session;
use System\Crypter;
use System\Session\Payload;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        $_SERVER['auth.login.stub'] = null;
        $_SERVER['auth.user.login'] = null;
        $_SERVER['auth.user.logout'] = null;

        Cookie::$jar = [];
        Config::$items = [];

        Auth::driver()->user = null;

        Session::$instance = null;

        Config::set('database.default', 'sqlite');
        Config::set('application.key', 'mySecretKeyIsSoDarnLongSoPeopleCantRememberIt');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $_SERVER['auth.login.stub'] = null;
        $_SERVER['auth.user.login'] = null;
        $_SERVER['auth.user.logout'] = null;

        Cookie::$jar = [];
        Config::$items = [];

        Auth::driver()->user = null;

        Session::$instance = null;

        Config::set('database.default', 'mysql');
        Config::set('application.key', '');
    }

    /**
     * Helper: set value di $_SERVER.
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
     * Inisialisasi ulang global request.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        Request::$foundation = FoundationRequest::createFromGlobals();
    }

    /**
     * Test untuk method Auth::user() - 1.
     *
     * @group system
     */
    public function testUserMethodReturnsCurrentUser()
    {
        Auth::driver()->user = 'Budi';

        $this->assertEquals('Budi', Auth::user());
    }

    /**
     * Test untuk method Auth::check() - 1.
     *
     * @group system
     */
    public function testCheckMethodReturnsTrueWhenUserIsSet()
    {
        $auth = new AuthUserReturnsDummy();

        $this->assertTrue($auth->check());
    }

    /**
     * Test untuk method Auth::check() - 2.
     *
     * @group system
     */
    public function testCheckMethodReturnsFalseWhenNoUserIsSet()
    {
        $auth = new AuthUserReturnsNull();

        $this->assertFalse($auth->check());
    }

    /**
     * Test untuk method Auth::guest() - 1.
     *
     * @group system
     */
    public function testGuestReturnsTrueWhenNoUserIsSet()
    {
        $auth = new AuthUserReturnsNull();

        $this->assertTrue($auth->guest());
    }

    /**
     * Test untuk method Auth::guest() - 2.
     *
     * @group system
     */
    public function testGuestReturnsFalseWhenUserIsSet()
    {
        $auth = new AuthUserReturnsDummy();

        $this->assertFalse($auth->guest());
    }

    /**
     * Test untuk method Auth::user() - 2.
     *
     * @group system
     */
    public function testUserMethodReturnsNullWhenNoUserExistsAndNoRecallerExists()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $this->assertNull(Auth::user());
    }

    /**
     * Test untuk method Auth::user() - 3.
     *
     * @group system
     */
    public function testUserReturnsUserByID()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        Auth::login(1);
        $this->assertEquals('Budi Purnomo', Auth::user()->name);

        Auth::logout();
    }

    /**
     * Test untuk method Auth::user() - 4.
     *
     * @group system
     */
    public function testNullReturnedWhenUserIDNotValidInteger()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        Auth::login('asdfghjkl');
        $this->assertNull(Auth::user());
    }

    /**
     * Test untuk method Auth::recall().
     *
     * @group system
     */
    public function testUserCanBeRecalledViaCookie()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $cookie = Crypter::encrypt('1|'.Str::random(40));
        Cookie::forever('authloginstub_remember', $cookie);

        $auth = new AuthLoginStub();

        $this->assertEquals('Budi Purnomo', $auth->user()->name);
        $this->assertTrue($auth->user()->id === $_SERVER['auth.login.stub']['user']);
    }

    /**
     * Test untuk method Auth::attempt() - 1.
     *
     * @group system
     */
    public function testAttemptMethodReturnsFalseWhenCredentialsAreInvalid()
    {
        $this->assertFalse(Auth::attempt(['username' => 'foo', 'password' => 'foo']));
        $this->assertFalse(Auth::attempt(['username' => 'foo', 'password' => null]));
        // $this->assertFalse(Auth::attempt(['username' => null, 'password' => null])); // error InvalidArgumentException dari perubahan where() yang baru
        $this->assertFalse(Auth::attempt(['username' => 'budi', 'password' => 'password']));
        $this->assertFalse(Auth::attempt(['username' => 'budi', 'password' => 232]));
    }

    /**
     * Test untuk method Auth::attempt() - 2.
     *
     * @group system
     */
    public function testAttemptReturnsTrueWhenCredentialsAreCorrect()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $auth = new AuthLoginStub();

        // Correct password di database: budi = budi123, agung = agung123
        $credentials = ['username' => 'budi', 'password' => 'budi123'];

        $this->assertTrue($auth->attempt($credentials));
        $this->assertEquals('1', $_SERVER['auth.login.stub']['user']);
        $this->assertFalse($_SERVER['auth.login.stub']['remember']);

        $secure = new AuthLoginStub();
        $credentials['remember'] = true;

        $this->assertTrue($secure->attempt($credentials));
        $this->assertEquals('1', $_SERVER['auth.login.stub']['user']);
        $this->assertTrue($_SERVER['auth.login.stub']['remember']);

        $secure->logout();
        $auth->logout();
    }

    /**
     * Test untuk method Auth::login() - 1.
     *
     * @group system
     */
    public function testLoginMethodStoresUserKeyInSession()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $user = new \stdClass();
        $user->id = 10;

        Auth::login($user);

        $user = Session::instance()->session['data']['system_auth_drivers_magic_login'];
        $this->assertEquals(10, $user->id);

        Auth::logout();

        Auth::login(5);
        $user = Session::instance()->session['data']['system_auth_drivers_magic_login'];
        $this->assertEquals(5, $user);
        Auth::logout(5);
    }

    /**
     * Test untuk method Auth::login() - 2.
     *
     * @group system
     */
    public function testLoginStoresRememberCookieWhenNeeded()
    {
        $mock = $this->getMock('\System\Session\Drivers\Driver');
        Session::$instance = new Payload($mock);

        $this->setServerVar('HTTPS', 'on');

        // Set variabel session supaya dipakai oleh remember cookie.
        Config::set('session.path', 'foo');
        Config::set('session.domain', 'bar');
        Config::set('session.secure', true);

        Auth::login(1, true);

        $this->assertTrue(isset(Cookie::$jar['system_auth_drivers_magic_remember']));

        $cookie = Cookie::get('system_auth_drivers_magic_remember');
        $cookie = explode('|', Crypter::decrypt($cookie));

        $this->assertEquals('1', $cookie[0]);
        $this->assertEquals('foo', Cookie::$jar['system_auth_drivers_magic_remember']['path']);
        $this->assertEquals('bar', Cookie::$jar['system_auth_drivers_magic_remember']['domain']);
        $this->assertTrue(Cookie::$jar['system_auth_drivers_magic_remember']['secure']);

        Auth::logout();

        $this->setServerVar('HTTPS', 'off');
    }

    /**
     * Test untuk method Auth::logout().
     *
     * @group system
     */
    public function testLogoutMethodLogsOutUser()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));
        Session::instance()->session['data']['system_auth_drivers_magic_login'] = 1;

        Auth::logout();

        $this->assertNull(Auth::user());
        $this->assertFalse(isset(Session::instance()->session['data']['system_auth_drivers_magic_login']));
        $this->assertTrue(Cookie::$jar['system_auth_drivers_magic_remember']['expiration'] < time());
    }

    /**
     * Test event 'rakit.auth: login' dan 'rakit.auth: logout' bisa terpanggil dengan benar.
     *
     * @group system
     */
    public function testAuthEventIsCalledProperly()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        Event::listen('rakit.auth: login', function () {
            $_SERVER['auth.user.login'] = 'foo';
        });

        Event::listen('rakit.auth: logout', function () {
            $_SERVER['auth.user.logout'] = 'foo';
        });

        $this->assertNull($_SERVER['auth.user.login']);
        $this->assertNull($_SERVER['auth.user.logout']);

        Auth::login(1, true);
        $this->assertEquals('foo', $_SERVER['auth.user.login']);

        Auth::logout();

        $this->assertEquals('foo', $_SERVER['auth.user.logout']);
    }
}

class AuthUserReturnsNull extends \Authenticator
{
    public function user()
    {
        // ..
    }

    public function retrieve($id)
    {
        // ..
    }

    public function attempt($arguments = [])
    {
        // ..
    }
}

class AuthUserReturnsDummy extends \System\Auth\Drivers\Driver
{
    public function user()
    {
        return 'Budi';
    }

    public function retrieve($id)
    {
        // ..
    }

    public function attempt($arguments = [])
    {
        return $this->login($arguments['username']);
    }
}

class AuthLoginStub extends \System\Auth\Drivers\Magic
{
    public function login($user, $remember = false)
    {
        $remember = is_null($remember) ? false : $remember;

        $_SERVER['auth.login.stub'] = compact('user', 'remember');

        return parent::login($user, $remember);
    }

    public function retrieve($id)
    {
        $user = parent::retrieve($id);

        $_SERVER['auth.login.stub'] = ['user' => $user->id, 'remember' => false];

        return $user;
    }
}
