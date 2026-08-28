<?php

defined('DS') or exit('No direct access.');

use System\Str;
use System\Auth;
use System\Hash;
use System\Hook;
use System\Cookie;
use System\Config;
use System\Session;
use System\Request;
use System\Crypter;
use System\Database;
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

        $this->clearRememberTokens();
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

        $this->clearRememberTokens();

        Config::set('database.default', 'mysql');
    }

    /**
     * Helper: wipe every "remember me" token from the users table.
     */
    protected function clearRememberTokens()
    {
        Database::table('users')->update(['remember_token' => null]);
    }

    /**
     * Helper: give a user a "remember me" token and build the matching cookie.
     *
     * @param int    $id
     * @param string $token
     *
     * @return string
     */
    protected function makeRecaller($id, $token = null)
    {
        $token = is_null($token) ? Str::random(60) : $token;

        Database::table('users')->where('id', '=', $id)->update(['remember_token' => $token]);
        $user = Database::table('users')->find($id);

        return Crypter::encrypt($id.'|'.$token.'|'.$user->password);
    }

    /**
     * Helper: mock session driver that hands out a fresh id every time.
     *
     * @return \System\Session\Drivers\Driver
     */
    protected function getMockSessionDriver()
    {
        $mock = $this->getMock('\System\Session\Drivers\Driver', ['id', 'load', 'save', 'delete']);
        $mock->expects($this->any())->method('id')->will($this->returnCallback(function () {
            return Str::random(40);
        }));

        return $mock;
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
     * Restart the request instance.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();
    }

    /**
     * Test for Auth::user() - 1.
     *
     * @group system
     */
    public function testUserMethodReturnsCurrentUser()
    {
        Auth::driver()->user = 'Budi';

        $this->assertEquals('Budi', Auth::user());
    }

    /**
     * Test for Auth::check() - 1.
     *
     * @group system
     */
    public function testCheckMethodReturnsTrueWhenUserIsSet()
    {
        $auth = new AuthUserReturnsDummy();

        $this->assertTrue($auth->check());
    }

    /**
     * Test for Auth::check() - 2.
     *
     * @group system
     */
    public function testCheckMethodReturnsFalseWhenNoUserIsSet()
    {
        $auth = new AuthUserReturnsNull();

        $this->assertFalse($auth->check());
    }

    /**
     * Test for Auth::guest() - 1.
     *
     * @group system
     */
    public function testGuestReturnsTrueWhenNoUserIsSet()
    {
        $auth = new AuthUserReturnsNull();

        $this->assertTrue($auth->guest());
    }

    /**
     * Test for Auth::guest() - 2.
     *
     * @group system
     */
    public function testGuestReturnsFalseWhenUserIsSet()
    {
        $auth = new AuthUserReturnsDummy();

        $this->assertFalse($auth->guest());
    }

    /**
     * Test for Auth::user() - 2.
     *
     * @group system
     */
    public function testUserMethodReturnsNullWhenNoUserExistsAndNoRecallerExists()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $this->assertNull(Auth::user());
    }

    /**
     * Test for Auth::user() - 3.
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
     * Test for Auth::user() - 4.
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
     * Test for Auth::recall().
     *
     * @group system
     */
    public function testUserCanBeRecalledViaCookie()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        Cookie::forever('authloginstub_remember', $this->makeRecaller(1));

        $auth = new AuthLoginStub();

        $this->assertEquals('Budi Purnomo', $auth->user()->name);
        $this->assertTrue($auth->user()->id === $_SERVER['auth.login.stub']['user']);
    }

    /**
     * Test for Auth::recall() - a cookie in the old two part format is refused.
     *
     * @group system
     */
    public function testRecallRefusesCookieWithoutToken()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        Cookie::forever('system_auth_drivers_magic_remember', Crypter::encrypt('1|'.Str::random(40)));

        $driver = new \System\Auth\Drivers\Magic();
        $this->assertNull($driver->user());
    }

    /**
     * Test for Auth::recall() - a cookie holding a stale token is refused.
     *
     * @group system
     */
    public function testRecallRefusesRevokedToken()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $cookie = $this->makeRecaller(1);
        Database::table('users')->where('id', '=', 1)->update(['remember_token' => Str::random(60)]);
        Cookie::forever('system_auth_drivers_magic_remember', $cookie);

        $driver = new \System\Auth\Drivers\Magic();
        $this->assertNull($driver->user());
    }

    /**
     * Test for Auth::recall() - a cookie is refused once the password changes.
     *
     * @group system
     */
    public function testRecallRefusesCookieAfterPasswordChange()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $before = Database::table('users')->find(1)->password;
        $cookie = $this->makeRecaller(1);

        Database::table('users')->where('id', '=', 1)->update(['password' => Hash::make('kata sandi baru')]);
        Cookie::forever('system_auth_drivers_magic_remember', $cookie);

        $driver = new \System\Auth\Drivers\Magic();
        $user = $driver->user();

        Database::table('users')->where('id', '=', 1)->update(['password' => $before]);

        $this->assertNull($user);
    }

    /**
     * Test for Auth::logout() - the stored token is replaced, so a stolen
     * cookie stops working.
     *
     * @group system
     */
    public function testLogoutCyclesTheRememberToken()
    {
        Session::$instance = new Payload($this->getMockSessionDriver());
        Session::instance()->load(null);

        Auth::login(1, true);
        $before = Database::table('users')->find(1)->remember_token;

        $this->assertNotNull($before);

        Auth::logout();

        $this->assertNotEquals($before, Database::table('users')->find(1)->remember_token);
    }

    /**
     * Test for Auth::retrieve() - a key that is not an integer still works.
     *
     * @group system
     */
    public function testRetrieveAcceptsStringKey()
    {
        Session::$instance = new Payload($this->getMockSessionDriver());
        Session::instance()->load(null);

        $pdo = Database::connection()->pdo();
        $pdo->exec('DROP TABLE IF EXISTS uuid_users');
        $pdo->exec(
            'CREATE TABLE uuid_users (id TEXT PRIMARY KEY, name TEXT,'
            .' email TEXT, password TEXT, remember_token TEXT)'
        );

        Config::set('auth.table', 'uuid_users');

        Database::table('uuid_users')->insert([
            'id' => '0197f4d2-uuid-key',
            'name' => 'Sari Melati',
            'email' => 'sari@example.com',
            'password' => Hash::make('rahasia'),
        ]);

        $driver = new \System\Auth\Drivers\Magic();

        $user = $driver->retrieve('0197f4d2-uuid-key');
        $attempt = $driver->attempt(['email' => 'sari@example.com', 'password' => 'rahasia']);

        $pdo->exec('DROP TABLE IF EXISTS uuid_users');
        Config::set('auth.table', 'users');

        $this->assertNotNull($user);
        $this->assertEquals('Sari Melati', $user->name);
        $this->assertTrue($attempt);
    }

    /**
     * Test for Auth::retrieve() - an empty token never hits the database.
     *
     * @group system
     */
    public function testRetrieveIgnoresEmptyToken()
    {
        $driver = new \System\Auth\Drivers\Magic();

        $this->assertNull($driver->retrieve(null));
        $this->assertNull($driver->retrieve(''));
        $this->assertNull($driver->retrieve([]));
    }

    /**
     * Test for Auth::attempt() - 1.
     *
     * @group system
     */
    public function testAttemptMethodReturnsFalseWhenCredentialsAreInvalid()
    {
        $this->assertFalse(Auth::attempt(['email' => 'foo', 'password' => 'foo']));
        $this->assertFalse(Auth::attempt(['email' => 'foo', 'password' => null]));
        $this->assertFalse(Auth::attempt(['email' => null, 'password' => null]));
        $this->assertFalse(Auth::attempt(['email' => 'budi', 'password' => 'password']));
        $this->assertFalse(Auth::attempt(['email' => 'budi', 'password' => 232]));
    }

    /**
     * Test for Auth::attempt() - 2.
     *
     * @group system
     */
    public function testAttemptReturnsTrueWhenCredentialsAreCorrect()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        $auth = new AuthLoginStub();

        // Correct password di database: budi = budi123, agung = agung123
        $credentials = ['email' => 'budi@gmail.com', 'password' => 'budi123'];

        $this->assertTrue($auth->attempt($credentials));
        $this->assertEquals('1', $_SERVER['auth.login.stub']['user']);
        $this->assertFalse($_SERVER['auth.login.stub']['remember']);

        $secure = new AuthLoginStub();
        $credentials['remember'] = true;

        $this->assertTrue($secure->attempt($credentials));
        $this->assertEquals('1', $_SERVER['auth.login.stub']['user']);

        $secure->logout();
        $auth->logout();
    }

    /**
     * Test for Auth::login() - 1.
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
     * Test for Auth::login() - 2.
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
     * Test for Auth::logout().
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
     * Test for Auth::login() - rotates the session id (session fixation).
     *
     * @group system
     */
    public function testLoginRegeneratesTheSessionId()
    {
        Session::$instance = new Payload($this->getMockSessionDriver());
        Session::instance()->load(null);

        $before = Session::instance()->session['id'];

        Auth::login(1);

        $this->assertNotEquals($before, Session::instance()->session['id']);
        $this->assertEquals(1, Session::get('system_auth_drivers_magic_login'));
    }

    /**
     * Test for Auth::logout() - rotates the session id as well.
     *
     * @group system
     */
    public function testLogoutRegeneratesTheSessionId()
    {
        Session::$instance = new Payload($this->getMockSessionDriver());
        Session::instance()->load(null);

        Auth::login(1);
        $before = Session::instance()->session['id'];

        Auth::logout();

        $this->assertNotEquals($before, Session::instance()->session['id']);
    }

    /**
     * Test 'rakit.auth: login' dan 'rakit.auth: logout' can be called.
     *
     * @group system
     */
    public function testAuthEventIsCalledProperly()
    {
        Session::$instance = new Payload($this->getMock('\System\Session\Drivers\Driver'));

        Hook::listen('rakit.auth: login', function () {
            $_SERVER['auth.user.login'] = 'foo';
        });

        Hook::listen('rakit.auth: logout', function () {
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

class AuthUserReturnsNull extends \System\Auth\Drivers\Driver
{
    public function user()
    {
        // ..
    }

    public function retrieve($id)
    {
        // ..
    }

    public function attempt(array $arguments = [])
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

    public function attempt(array $arguments = [])
    {
        return $this->login($arguments['email']);
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
