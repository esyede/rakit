<?php

defined('DS') or exit('No direct script access.');

use System\Input;
use System\Session;
use System\Request;

class InputTest extends \PHPUnit_Framework_TestCase
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
        // TODO: bersihkan data request di http foundation.
        Session::$instance = null;
    }

    /**
     * Test untuk method Input::all().
     *
     * @group system
     */
    public function testAllMethodReturnsInputAndFiles()
    {
        Request::foundation()->request->add(['name' => 'Budi']);

        $_FILES = ['age' => 25];

        $this->assertEquals(Input::all(), ['name' => 'Budi', 'age' => 25]);
    }

    /**
     * Test untuk method Input::has().
     *
     * @group system
     */
    public function testHasMethodIndicatesTheExistenceOfInput()
    {
        $this->assertFalse(Input::has('foo'));

        Request::foundation()->request->add(['name' => 'Budi']);

        $this->assertTrue(Input::has('name'));
    }

    /**
     * Test untuk method Input::get().
     *
     * @group system
     */
    public function testGetMethodReturnsInputValue()
    {
        Request::foundation()->request->add(['name' => 'Budi']);

        $this->assertEquals('Budi', Input::get('name'));
        $this->assertEquals('Default', Input::get('foo', 'Default'));
    }

    /**
     * Test untuk method Input::only().
     *
     * @group system
     */
    public function testOnlyMethodReturnsSubsetOfInput()
    {
        Request::foundation()->request->add(['name' => 'Budi', 'age' => 25]);

        $this->assertEquals(['name' => 'Budi'], Input::only(['name']));
    }

    /**
     * Test untuk method Input::except().
     *
     * @group system
     */
    public function testExceptMethodReturnsSubsetOfInput()
    {
        Request::foundation()->request->add(['name' => 'Budi', 'age' => 25]);

        $this->assertEquals(['age' => 25], Input::except(['name']));
    }

    /**
     * Test untuk method Input::old().
     *
     * @group system
     */
    public function testOldInputCanBeRetrievedFromSession()
    {
        $this->instantiateSession();

        Session::instance()->session['data'][Input::OLD] = ['name' => 'Budi'];

        $this->assertNull(Input::old('foo'));
        $this->assertTrue(Input::had('name'));
        $this->assertFalse(Input::had('foo'));
        $this->assertEquals('Budi', Input::old('name'));
    }

    /**
     * Test untuk method Input::file().
     *
     * @group system
     */
    public function testFileMethodReturnsFromFileArray()
    {
        $_FILES['foo'] = ['name' => 'Budi', 'size' => 100];

        $this->assertEquals('Budi', Input::file('foo.name'));
        $this->assertEquals(['name' => 'Budi', 'size' => 100], Input::file('foo'));
    }

    /**
     * Test untuk method Input::flash().
     *
     * @group system
     */
    public function testFlashMethodFlashesInputToSession()
    {
        $this->instantiateSession();

        $input = ['name' => 'Budi', 'age' => 25];
        Request::foundation()->request->add($input);

        Input::flash();
        $this->assertEquals($input, Session::instance()->session['data'][':new:'][Input::OLD]);

        Input::flash('only', ['name']);
        $this->assertEquals(['name' => 'Budi'], Session::instance()->session['data'][':new:'][Input::OLD]);

        Input::flash('except', ['name']);
        $this->assertEquals(['age' => 25], Session::instance()->session['data'][':new:'][Input::OLD]);
    }

    /**
     * Test untuk method Input::flush().
     *
     * @group system
     */
    public function testFlushMethodClearsFlashedInput()
    {
        $this->instantiateSession();

        $input = ['name' => 'Budi', 'age' => 30];
        Request::foundation()->request->add($input);

        Input::flash();
        $this->assertEquals($input, Session::instance()->session['data'][':new:'][Input::OLD]);

        Input::flush();

        $this->assertEquals([], Session::instance()->session['data'][':new:'][Input::OLD]);
    }

    /**
     * Instansiasi payload session.
     */
    protected function instantiateSession()
    {
        $driver = $this->getMock('\System\Session\Drivers\Driver');
        Session::$instance = new \System\Session\Payload($driver);
    }
}
