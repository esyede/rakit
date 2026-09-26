<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Hook;
use System\Lang;
use System\URI;
use System\Worker\Worker;
use System\Routing\Router;

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
     * Test for Lang::line().
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
     * Test for Lang::__toString().
     *
     * @group system
     */
    public function testLineCanBeCastAsString()
    {
        $validation = require path('app') . 'language' . DS . 'id' . DS . 'validation.php';

        $this->assertEquals($validation['required'], (string) Lang::line('validation.required'));
    }

    /**
     * Test for the ':attribute' replacement in language lines.
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
     * Test for Lang::has().
     *
     * @group system
     */
    public function testHasMethodIndicatesIfLangaugeLineExists()
    {
        $this->assertTrue(Lang::has('validation'));
        $this->assertTrue(Lang::has('validation.required'));
        $this->assertFalse(Lang::has('validation.foo'));
    }

    /**
     * Test that a leading locale segment announces 'rakit.locale' with the
     * locale it set, before the request is routed.
     *
     * @group system
     */
    public function testLocaleSegmentFiresTheLocaleEvent()
    {
        $language = Config::get('application.language');
        $languages = Config::get('application.languages');
        $session = Config::get('session.driver');
        $uri = URI::$uri;
        $route = \System\Request::$route;
        $events = [];

        Hook::listen('rakit.locale', function ($locale) use (&$events) {
            $events[] = $locale;
        });

        Config::set('session.driver', '');
        Config::set('application.languages', ['id']);
        URI::$uri = 'id/rakit-locale-test';

        Router::register('GET', 'rakit-locale-test', function () {
            return 'ok';
        });

        Worker::dispatch();

        unset(Router::$routes['GET']['rakit-locale-test']);

        $this->assertEquals(['id'], $events);
        $this->assertEquals('id', Config::get('application.language'));

        Hook::clear('rakit.locale');
        Config::set('application.language', $language);
        Config::set('application.languages', $languages);
        Config::set('session.driver', $session);
        URI::$uri = $uri;
        \System\Request::$route = $route;
    }
}
