<?php

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Config;
use System\Cookie;
use System\Session;
use System\Database;

class AuthFacileTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Cookie::$jar = [];
        Auth::$drivers = [];
        Session::$instance = null;
        Database::$connections = [];
        Config::set('database.default', 'sqlite');
        Config::set('auth.model', 'FacileAuthTestUser');
        Config::set('auth.identifier', 'email');
        Config::set('auth.driver', 'facile');
    }

    public function tearDown()
    {
        Cookie::$jar = [];
        Auth::$drivers = [];
        Session::$instance = null;
        Database::$connections = [];
        Config::set('database.default', 'sqlite');
        Config::set('auth.driver', 'magic');
        Config::set('auth.model', null);
        Config::set('auth.identifier', 'email');
    }

    // -------------------------------------------------------------------------
    // Auth\Drivers\Facile::retrieve()
    // -------------------------------------------------------------------------

    /**
     * Test for Facile::retrieve() - returns user by integer token.
     *
     * @group system
     */
    public function testFacileRetrieveReturnsUserByIntegerToken()
    {
        $driver = new \System\Auth\Drivers\Facile();
        $user = $driver->retrieve(1);

        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('budi@gmail.com', $user->email);
    }

    /**
     * Test for Facile::retrieve() - returns null for non-existent integer token.
     *
     * @group system
     */
    public function testFacileRetrieveReturnsNullForMissingId()
    {
        $driver = new \System\Auth\Drivers\Facile();
        $user = $driver->retrieve(99999);

        $this->assertNull($user);
    }

    /**
     * Test for Facile::retrieve() - returns object when token is instance of model.
     *
     * @group system
     */
    public function testFacileRetrieveReturnsObjectWhenTokenIsModelInstance()
    {
        Config::set('auth.model', 'FacileAuthTestUser');

        $driver = new \System\Auth\Drivers\Facile();
        $userObj = new FacileAuthTestUser(['id' => 5, 'email' => 'test@example.com']);

        $result = $driver->retrieve($userObj);
        $this->assertSame($userObj, $result);
    }

    /**
     * Test for Facile::retrieve() - returns null for non-integer, non-model token.
     *
     * @group system
     */
    public function testFacileRetrieveReturnsNullForNonIntegerToken()
    {
        $driver = new \System\Auth\Drivers\Facile();
        $result = $driver->retrieve('not_an_integer');
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Auth\Drivers\Facile::attempt()
    // -------------------------------------------------------------------------

    /**
     * Test for Facile::attempt() - returns false when credentials are missing.
     *
     * @group system
     */
    public function testFacileAttemptReturnsFalseWithMissingCredentials()
    {
        $driver = new \System\Auth\Drivers\Facile();
        $this->assertFalse($driver->attempt([]));
        $this->assertFalse($driver->attempt(['email' => 'budi@gmail.com']));
        $this->assertFalse($driver->attempt(['password' => 'budi123']));
    }

    /**
     * Test for Facile::attempt() - returns false with wrong password.
     *
     * @group system
     */
    public function testFacileAttemptReturnsFalseWithWrongPassword()
    {
        $driver = new \System\Auth\Drivers\Facile();
        $result = $driver->attempt(['email' => 'budi@gmail.com', 'password' => 'wrong_password']);
        $this->assertFalse($result);
    }

    /**
     * Test for Facile::attempt() - returns false with non-existent user.
     *
     * @group system
     */
    public function testFacileAttemptReturnsFalseForNonExistentUser()
    {
        $driver = new \System\Auth\Drivers\Facile();
        $result = $driver->attempt(['email' => 'nobody@example.com', 'password' => 'any']);
        $this->assertFalse($result);
    }

    /**
     * Test for Facile::attempt() - returns true with correct credentials.
     *
     * @group system
     */
    public function testFacileAttemptReturnsTrueWithCorrectCredentials()
    {
        Session::$instance = new \System\Session\Payload(
            $this->getMock('\System\Session\Drivers\Driver')
        );

        $driver = new \System\Auth\Drivers\Facile();
        $result = $driver->attempt(['email' => 'budi@gmail.com', 'password' => 'budi123']);
        $this->assertTrue($result);

        Session::$instance = null;
    }

    /**
     * Test for Facile::retrieve() - throws when auth model not configured.
     *
     * @group system
     */
    public function testFacileRetrieveThrowsWhenModelNotConfigured()
    {
        Config::set('auth.model', null);

        $driver = new \System\Auth\Drivers\Facile();
        $caught = false;
        try {
            $driver->retrieve(1);
        } catch (\Exception $e) {
            $caught = true;
            $this->assertContains('auth model', $e->getMessage());
        }
        $this->assertTrue($caught);
    }

    // -------------------------------------------------------------------------
    // Auth facade driver() with 'facile' driver
    // -------------------------------------------------------------------------

    /**
     * Test for Auth::driver('facile') - returns Facile driver instance.
     *
     * @group system
     */
    public function testAuthDriverFacileReturnsFacileInstance()
    {
        $driver = Auth::driver('facile');
        $this->assertInstanceOf('System\Auth\Drivers\Facile', $driver);
    }
}

class FacileAuthTestUser extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = false;
}
