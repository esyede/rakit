<?php

defined('DS') or exit('No direct script access.');

class AssetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::$items = [];
        Config::$cache = [];
        Asset::$containers = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    /**
     * Test untuk method Asset::container() - 1.
     *
     * @group system
     */
    public function testContainersCanBeCreated()
    {
        $container = Asset::container('foo');

        $this->assertTrue($container === Asset::container('foo'));
        $this->assertInstanceOf('\System\Assetor', $container);
    }

    /**
     * Test untuk method Asset::container() - 2.
     *
     * @group system
     */
    public function testDefaultContainerCreatedByDefault()
    {
        $this->assertEquals('default', Asset::container()->name);
    }

    /**
     * Test untuk method Asset::__callStatic().
     *
     * @group system
     */
    public function testContainerMethodsCanBeDynamicallyCalled()
    {
        Asset::style('common', 'common.css');

        $this->assertEquals('common.css', Asset::container()->assets['style']['common']['source']);
    }

    /**
     * Test untuk method Assetor::__construct().
     *
     * @group system
     */
    public function testNameIsSetOnAssetContainerConstruction()
    {
        $container = $this->getContainer();

        $this->assertEquals('foo', $container->name);
    }

    /**
     * Test untuk method Assetor::add() - 1.
     *
     * @group system
     */
    public function testAddMethodProperlySniffsAssetType()
    {
        $container = $this->getContainer();

        $container->add('jquery', 'jquery.js');
        $container->add('common', 'common.css');

        $this->assertEquals('assets/jquery.js', $container->assets['script']['jquery']['source']);
        $this->assertEquals('assets/common.css', $container->assets['style']['common']['source']);
    }

    /**
     * Test untuk method Assetor::style().
     *
     * @group system
     */
    public function testStyleMethodProperlyRegistersAnAsset()
    {
        $container = $this->getContainer();

        $container->style('common', 'common.css');

        $this->assertEquals('common.css', $container->assets['style']['common']['source']);
    }

    /**
     * Test untuk method Assetor::style().
     *
     * @group system
     */
    public function testStyleMethodProperlySetsMediaAttributeIfNotSet()
    {
        $container = $this->getContainer();

        $container->style('common', 'common.css');

        $this->assertEquals('all', $container->assets['style']['common']['attributes']['media']);
    }

    /**
     * Test untuk method Assetor::style().
     *
     * @group system
     */
    public function testStyleMethodProperlyIgnoresMediaAttributeIfSet()
    {
        $container = $this->getContainer();

        $container->style('common', 'common.css', [], ['media' => 'print']);

        $this->assertEquals('print', $container->assets['style']['common']['attributes']['media']);
    }

    /**
     * Test untuk method Assetor::script().
     *
     * @group system
     */
    public function testScriptMethodProperlyRegistersAnAsset()
    {
        $container = $this->getContainer();

        $container->script('jquery', 'jquery.js');

        $this->assertEquals('jquery.js', $container->assets['script']['jquery']['source']);
    }

    /**
     * Test untuk method Assetor::add() - 2.
     *
     * @group system
     */
    public function testAddMethodProperlySetsDependencies()
    {
        $container = $this->getContainer();

        $container->add('common', 'common.css', 'jquery');
        $container->add('jquery', 'jquery.js', ['jquery-ui']);

        $this->assertEquals(['jquery'], $container->assets['style']['common']['dependencies']);
        $this->assertEquals(['jquery-ui'], $container->assets['script']['jquery']['dependencies']);
    }

    /**
     * Test untuk method Assetor::add() - 3.
     *
     * @group system
     */
    public function testAddMethodProperlySetsAttributes()
    {
        $container = $this->getContainer();

        $container->add('common', 'common.css', [], ['media' => 'print']);
        $container->add('jquery', 'jquery.js', [], ['defer']);

        $this->assertEquals(['media' => 'print'], $container->assets['style']['common']['attributes']);
        $this->assertEquals(['defer'], $container->assets['script']['jquery']['attributes']);
    }

    /**
     * Test untuk method Assetor::package().
     *
     * @group system
     */
    public function testBundleMethodCorrectlySetsTheAssetBundle()
    {
        $container = $this->getContainer();

        $container->package('facile');

        $this->assertEquals('facile', $container->package);
    }

    /**
     * Test untuk method Assetor::path().
     *
     * @group system
     */
    public function testPathMethodReturnsCorrectPathForABundleAsset()
    {
        $container = $this->getContainer();
        $container->package('facile');

        $this->assertEquals('/packages/facile/foo.jpg', $container->path('foo.jpg'));
    }

    /**
     * Test untuk method Assetor::path().
     *
     * @group system
     */
    public function testPathMethodReturnsCorrectPathForAnApplicationAsset()
    {
        $this->assertEquals('/foo.jpg', $this->getContainer()->path('foo.jpg'));
    }

    /**
     * Test untuk method Assetor::scripts().
     *
     * @group system
     */
    public function testScriptsCanBeRetrieved()
    {
        $container = $this->getContainer();

        $container->script('dojo', 'dojo.js', ['jquery-ui']);
        $container->script('jquery', 'jquery.js', ['jquery-ui', 'dojo']);
        $container->script('jquery-ui', 'jquery-ui.js');

        $scripts = $container->scripts();

        $this->assertTrue(strpos($scripts, 'jquery.js') > 0);
        $this->assertTrue(strpos($scripts, 'jquery.js') > strpos($scripts, 'jquery-ui.js'));
        $this->assertTrue(strpos($scripts, 'dojo.js') > strpos($scripts, 'jquery-ui.js'));
    }

    /**
     * Test untuk method Assetor::styles().
     *
     * @group system
     */
    public function testStylesCanBeRetrieved()
    {
        $container = $this->getContainer();

        $container->style('dojo', 'dojo.css', ['jquery-ui'], ['media' => 'print']);
        $container->style('jquery', 'jquery.css', ['jquery-ui', 'dojo']);
        $container->style('jquery-ui', 'jquery-ui.css');

        $styles = $container->styles();

        $this->assertTrue(strpos($styles, 'jquery.css') > 0);
        $this->assertTrue(strpos($styles, 'media="print"') > 0);
        $this->assertTrue(strpos($styles, 'jquery.css') > strpos($styles, 'jquery-ui.css'));
        $this->assertTrue(strpos($styles, 'dojo.css') > strpos($styles, 'jquery-ui.css'));
    }

    /**
     * Helper: buat instance Assetor baru.
     *
     * @param string $name
     *
     * @return Assetor
     */
    private function getContainer($name = 'foo')
    {
        return new \System\Assetor($name);
    }
}
