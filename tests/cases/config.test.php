<?php

defined('DS') or exit('No direct access.');

use System\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
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
        Config::$items = [];
        Config::$cache = [];
        // Config::get() keeps its own result cache. Without clearing it a value
        // written by one test (see testConfigItemsCanBeSet) stays visible to
        // every test that runs afterwards.
        Config::$gets = [];
        Config::$reload = true;
    }

    /**
     * Test for Config::get().
     *
     * @group system
     */
    public function testItemsCanBeRetrievedFromConfigFiles()
    {
        $this->assertEquals('UTF-8', Config::get('application.encoding'));
        $this->assertEquals('mysql', Config::get('database.connections.mysql.driver'));
        $this->assertEquals('dashboard', Config::get('dashboard::meta.package'));
    }

    /**
     * Test for Config::has().
     *
     * @group system
     */
    public function testHasMethodIndicatesIfConfigItemExists()
    {
        $this->assertFalse(Config::has('application.foo'));
        $this->assertTrue(Config::has('application.encoding'));
    }

    /**
     * Test for Config::set().
     *
     * @group system
     */
    public function testConfigItemsCanBeSet()
    {
        Config::set('application.encoding', 'foo');
        Config::set('dashboard::meta.package', 'bar');

        $this->assertEquals('foo', Config::get('application.encoding'));
        $this->assertEquals('bar', Config::get('dashboard::meta.package'));
    }

    /**
	 * Test for environment-specific configuration overrides.
	 *
	 * @group system
	 */
	public function testEnvironmentConfigsOverrideNormalConfigurations()
	{
		$_SERVER['RAKIT_ENV'] = 'local';
		$this->assertEquals('sqlite', Config::get('database.default'));
		unset($_SERVER['RAKIT_ENV']);
	}

    /**
     * Test for setting items after entire file is loaded.
     *
     * @group system
     */
    public function testItemsCanBeSetAfterEntireFileIsLoaded()
    {
        Config::get('session');
        Config::set('session.table', 'my_sessions');

        $session = Config::get('session');
        $this->assertEquals('my_sessions', $session['table']);

        Config::set('session.table', 'sessions');
        $this->assertEquals('sessions', Config::get('session.table'));
    }

    /**
     * Test that reads still work with the modification-time check disabled,
     * which is how the framework runs in production.
     *
     * @group system
     */
    public function testItemsCanBeRetrievedWithoutReloadChecks()
    {
        Config::$reload = false;

        $this->assertEquals('UTF-8', Config::get('application.encoding'));
        // Second read comes from the cache, bypassing the mtime check entirely.
        $this->assertEquals('UTF-8', Config::get('application.encoding'));
        $this->assertEquals('mysql', Config::get('database.connections.mysql.driver'));
        $this->assertTrue(Config::has('application.encoding'));
        $this->assertFalse(Config::has('application.foo'));
    }

    /**
     * Config::set() clears its own cache entries, so writes must remain
     * visible even when the modification-time check is disabled.
     *
     * @group system
     */
    public function testItemsCanBeSetWithoutReloadChecks()
    {
        Config::$reload = false;

        $this->assertEquals('UTF-8', Config::get('application.encoding'));

        Config::set('application.encoding', 'ISO-8859-1');
        $this->assertEquals('ISO-8859-1', Config::get('application.encoding'));

        Config::set('application.encoding', 'UTF-8');
        $this->assertEquals('UTF-8', Config::get('application.encoding'));
    }
}
