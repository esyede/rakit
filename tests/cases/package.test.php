<?php

defined('DS') or exit('No direct access.');

use System\Event;
use System\Config;
use System\Package;

class PackageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Package::$booted = [];
        Package::$elements = [];
        unset(Package::$packages['foo']);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Package::$booted = [];
        Package::$elements = [];
        unset(Package::$packages['foo']);
    }

    /**
     * Test for Package::register().
     *
     * @group system
     */
    public function testRegisterMethodCorrectlyRegistersPackage()
    {
        Package::register('foo-baz', ['handles' => 'foo-baz']);

        $this->assertEquals('foo-baz', Package::$packages['foo-baz']['handles']);
        $this->assertFalse(Package::$packages['foo-baz']['autoboot']);

        Package::register('foo-bar', []);

        $this->assertFalse(Package::$packages['foo-baz']['autoboot']);
        $this->assertNull(Package::$packages['foo-bar']['handles']);

        unset(Package::$packages['foo-baz'], Package::$packages['foo-bar']);
    }

    /**
     * Test for Package::boot().
     *
     * @group system
     */
    public function testBootMethodBootsPackage()
    {
        $_SERVER['package.dummy.boot'] = 0;
        $_SERVER['package.dummy.routes'] = 0;
        $_SERVER['booted.dummy'] = false;

        Event::listen('rakit.booted: dummy', function () {
            $_SERVER['booted.dummy'] = true;
            // Indicates that the dummy package has been booted: it's routes.php file is in get_included_files()
            if (in_array(path('package') . 'dummy' . DS . 'routes.php', get_included_files())) {
                $_SERVER['package.dummy.routes']++;
            }
        });

        Package::register('dummy');

        Package::boot('dummy');

        $this->assertTrue($_SERVER['booted.dummy']);
        $this->assertEquals(1, $_SERVER['package.dummy.boot']);
        $this->assertEquals(1, $_SERVER['package.dummy.routes']);

        Package::boot('dummy');

        $this->assertEquals(1, $_SERVER['package.dummy.boot']);
        $this->assertEquals(1, $_SERVER['package.dummy.routes']);
    }

    /**
     * Test for Package::handles().
     *
     * @group system
     */
    public function testHandlesMethodReturnsPackageThatHandlesURI()
    {
        Package::register('foo', ['handles' => 'foo-bar']);

        $this->assertEquals('foo', Package::handles('foo-bar/admin'));

        unset(Package::$packages['foo']);
    }

    /**
     * Test for Package::exist().
     *
     * @group system
     */
    public function testExistMethodIndicatesIfPackageExist()
    {
        $this->assertTrue(Package::exists('dashboard'));
        $this->assertFalse(Package::exists('foo'));
    }

    /**
     * Test for Package::booted().
     *
     * @group system
     */
    public function testBootedMethodIndicatesIfPackageIsBooted()
    {
        Package::register('dummy');
        Package::boot('dummy');

        $this->assertTrue(Package::booted('dummy'));
    }

    /**
     * Test for Package::prefix().
     *
     * @group system
     */
    public function testPrefixMethodReturnsCorrectPrefix()
    {
        $this->assertEquals('dummy::', Package::prefix('dummy'));
        $this->assertEquals('', Package::prefix(DEFAULT_PACKAGE));
    }

    /**
     * Test for Package::class_prefix().
     *
     * @group system
     */
    public function testClassPrefixMethodReturnsProperClassPrefixForPackage()
    {
        $this->assertEquals('Dummy_', Package::class_prefix('dummy'));
        $this->assertEquals('', Package::class_prefix(DEFAULT_PACKAGE));
    }

    /**
     * Test for Package::path().
     *
     * @group system
     */
    public function testPathMethodReturnsCorrectPath()
    {
        $this->assertEquals(path('app'), Package::path(null));
        $this->assertEquals(path('app'), Package::path(DEFAULT_PACKAGE));
        $this->assertEquals(path('package') . 'dashboard' . DS, Package::path('dashboard'));
    }

    /**
     * Test for Package::asset().
     *
     * @group system
     */
    public function testAssetPathReturnsPathToPackagesAssets()
    {
        $this->assertEquals('/packages/dashboard/', Package::assets('dashboard'));
        $this->assertEquals('/', Package::assets(DEFAULT_PACKAGE));

        Config::set('application.url', '');
    }

    /**
     * Test for Package::name().
     *
     * @group system
     */
    public function testPackageNameCanBeRetrievedFromIdentifier()
    {
        $this->assertEquals(DEFAULT_PACKAGE, Package::name('something'));
        $this->assertEquals(DEFAULT_PACKAGE, Package::name('something.else'));
        $this->assertEquals('package', Package::name('package::something.else'));
    }

    /**
     * Test for Package::element().
     *
     * @group system
     */
    public function testElementCanBeRetrievedFromIdentifier()
    {
        $this->assertEquals('something', Package::element('something'));
        $this->assertEquals('something.else', Package::element('something.else'));
        $this->assertEquals('something.else', Package::element('package::something.else'));
    }

    /**
     * Test for Package::identifier().
     *
     * @group system
     */
    public function testIdentifierCanBeConstructed()
    {
        $this->assertEquals('something.else', Package::identifier(DEFAULT_PACKAGE, 'something.else'));
        $this->assertEquals('dashboard::something', Package::identifier('dashboard', 'something'));
        $this->assertEquals('dashboard::something.else', Package::identifier('dashboard', 'something.else'));
    }

    /**
     * Test for Package::resolve().
     *
     * @group system
     */
    public function testPackageNamesCanBeResolved()
    {
        $this->assertEquals(DEFAULT_PACKAGE, Package::resolve('foo'));
        $this->assertEquals('dashboard', Package::resolve('dashboard'));
    }

    /**
     * Test for Package::parse().
     *
     * @group system
     */
    public function testParseMethodReturnsElementAndIdentifier()
    {
        $this->assertEquals(['application', 'something'], Package::parse('something'));
        $this->assertEquals(['application', 'something.else'], Package::parse('something.else'));
        $this->assertEquals(['dashboard', 'something'], Package::parse('dashboard::something'));
        $this->assertEquals(['dashboard', 'something.else'], Package::parse('dashboard::something.else'));
    }

    /**
     * Test for Package::get().
     *
     * @group system
     */
    public function testOptionMethodReturnsPackageOption()
    {
        $this->assertFalse(Package::option('dashboard', 'autoboot'));
        $this->assertEquals('dashboard', Package::option('dashboard', 'location'));
    }

    /**
     * Test for Package::all().
     *
     * @group system
     */
    public function testAllMethodReturnsPackageArray()
    {
        Package::register('foo');
        $this->assertEquals(Package::$packages, Package::all());

        unset(Package::$packages['foo']);
    }

    /**
     * Test for Package::names().
     *
     * @group system
     */
    public function testNamesMethodReturnsPackageNames()
    {
        Package::register('foo');
        $this->assertEquals(['dashboard', 'dummy', 'foo'], Package::names());

        unset(Package::$packages['foo']);
    }
}
