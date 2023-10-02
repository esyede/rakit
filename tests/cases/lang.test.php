<?php

defined('DS') or exit('No direct access.');

use System\Lang;

class LangTest extends \PHPUnit_Framework_TestCase
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
        // ..
    }

    /**
     * Test untuk method Lang::line().
     *
     * @group system
     */
    public function testGetMethodCanGetFromDefaultLanguage()
    {
        $validation = require path('app') . 'language' . DS . 'id' . DS . 'validation.php';

        $this->assertEquals($validation['required'], Lang::line('validation.required')->get());
        $this->assertEquals('Budi', Lang::line('validation.foo')->get(null, 'Budi'));

        $validation = require path('app') . 'language' . DS . 'en' . DS . 'validation.php';

        $this->assertEquals($validation['required'], Lang::line('validation.required')->get('en'));
    }

    /**
     * Test untuk method Lang::__toString().
     *
     * @group system
     */
    public function testLineCanBeCastAsString()
    {
        $validation = require path('app') . 'language' . DS . 'id' . DS . 'validation.php';

        $this->assertEquals($validation['required'], (string) Lang::line('validation.required'));
    }

    /**
     * Test untuk penggantian placeholder ':attribute'.
     *
     * @group system
     */
    public function testReplacementsAreMadeOnLines()
    {
        $validation = require path('app') . 'language' . DS . 'id' . DS . 'validation.php';
        $line = str_replace(':attribute', 'e-mail', $validation['required']);

        $this->assertEquals($line, Lang::line('validation.required', ['attribute' => 'e-mail'])->get());
    }

    /**
     * Test untuk method Lang::has().
     *
     * @group system
     */
    public function testHasMethodIndicatesIfLangaugeLineExists()
    {
        $this->assertTrue(Lang::has('validation'));
        $this->assertTrue(Lang::has('validation.required'));
        $this->assertFalse(Lang::has('validation.foo'));
    }
}
