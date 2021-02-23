<?php

defined('DS') or exit('No direct script access.');

class ViewTest extends \PHPUnit_Framework_TestCase
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
        View::$shared = [];
        unset(Event::$events['composing: test.basic']);
    }

    /**
     * Test untuk method View::make().
     *
     * @group system
     */
    public function testMakeMethodReturnsAViewInstance()
    {
        $this->assertInstanceOf('\System\View', View::make('home.index'));
    }

    /**
     * Test untuk method View::__construct() - 1.
     *
     * @group system
     */
    public function testViewNameIsSetByConstrutor()
    {
        $view = new View('home.index');

        $this->assertEquals('home.index', $view->view);
    }

    /**
     * Test untuk method View::__construct() - 2.
     *
     * @group system
     */
    public function testViewIsCreatedWithCorrectPath()
    {
        $view = new View('home.index');

        $this->assertEquals(
            str_replace(DS, '/', path('app')).'views/home/index.php',
            str_replace(DS, '/', $view->path)
        );
    }

    /**
     * Test untuk method View::__construct() - 3.
     *
     * @group system
     */
    public function testPackageViewIsCreatedWithCorrectPath()
    {
        $view = new View('home.index');

        $this->assertEquals(
            str_replace(DS, '/', Package::path(DEFAULT_PACKAGE)).'views/home/index.php',
            str_replace(DS, '/', $view->path)
        );
    }

    /**
     * Test untuk method View::__construct() - 4.
     *
     * @group system
     */
    public function testDataIsSetOnViewByConstructor()
    {
        $view = new View('home.index', ['name' => 'Budi']);

        $this->assertEquals('Budi', $view->data['name']);
    }

    /**
     * Test untuk method View::name().
     *
     * @group system
     */
    public function testNameMethodRegistersAViewName()
    {
        View::name('home.index', 'home');

        $this->assertEquals('home.index', View::$names['home']);
    }

    /**
     * Test untuk method View::shared().
     *
     * @group system
     */
    public function testSharedMethodAddsDataToSharedArray()
    {
        View::share('comment', 'Budi');

        $this->assertEquals('Budi', View::$shared['comment']);
    }

    /**
     * Test untuk method View::with().
     *
     * @group system
     */
    public function testViewDataCanBeSetUsingWithMethod()
    {
        $view = View::make('home.index')->with('comment', 'Budi');

        $this->assertEquals('Budi', $view->data['comment']);
    }

    /**
     * Test untuk method View::__construct() - 5.
     *
     * @group system
     */
    public function testEmptyMessageContainerSetOnViewWhenNoErrorsInSession()
    {
        $view = new View('home.index');

        $this->assertInstanceOf('\System\Messages', $view->data['errors']);
    }

    /**
     * Test untuk method View::__set().
     *
     * @group system
     */
    public function testDataCanBeSetOnViewsThroughMagicMethods()
    {
        $view = new View('home.index');

        $view->comment = 'Budi';

        $this->assertEquals('Budi', $view->data['comment']);
    }

    /**
     * Test untuk method View::__get().
     *
     * @group system
     */
    public function testDataCanBeRetrievedFromViewsThroughMagicMethods()
    {
        $view = new View('home.index');

        $view->comment = 'Budi';

        $this->assertEquals('Budi', $view->comment);
    }

    /**
     * Test implementasi \ArrayAccess di view.
     *
     * @group system
     */
    public function testDataCanBeSetOnTheViewThroughArrayAccess()
    {
        $view = new View('home.index');

        $view['comment'] = 'Budi';

        $this->assertEquals('Budi', $view->data['comment']);
    }

    /**
     * Test implementasi \ArrayAccess di view.
     *
     * @group system
     */
    public function testDataCanBeRetrievedThroughArrayAccess()
    {
        $view = new View('home.index');

        $view['comment'] = 'Budi';

        $this->assertEquals('Budi', $view['comment']);
    }

    /**
     * Test untuk method View::nest().
     *
     * @group system
     */
    public function testNestMethodSetsViewInstanceInData()
    {
        $view = View::make('home.index')->nest('partial', 'tests.basic');

        $this->assertEquals('tests.basic', $view->data['partial']->view);
        $this->assertInstanceOf('\System\View', $view->data['partial']);
    }

    /**
     * Test bahwa data yang dioper ke view bisa ditangkap dengan benar.
     *
     * @group system
     */
    public function testDataIsPassedToViewCorrectly()
    {
        View::share('name', 'Budi');

        $view = View::make('tests.basic')->with('age', 25)->render();

        $this->assertEquals('Budi berumur 25', $view);
    }

    /**
     * Test bahwa nested view bisa dirender dengan benar.
     *
     * @group system
     */
    public function testNestedViewsAreRendered()
    {
        $view = View::make('tests.basic')
            ->with('age', 25)
            ->nest('name', 'tests.nested');

        $this->assertEquals('Budi berumur 25', str_replace(["\n", "\t", "\r"], '', $view->render()));
    }

    /**
     * Test bahwa nested response bisa dirender dengan benar.
     *
     * @group system
     */
    public function testNestedResponsesAreRendered()
    {
        $view = View::make('tests.basic')
            ->with('age', 25)
            ->with('name', Response::view('tests.nested'));

        $this->assertEquals('Budi berumur 25', str_replace(["\n", "\t", "\r"], '', $view->render()));
    }

    /**
     * Test bahwa saat view dipanggil, event 'composing' juga ikut terpanggil.
     *
     * @group system
     */
    public function testComposerEventIsCalledWhenViewIsRendering()
    {
        View::composer('tests.basic', function ($view) {
            $view->data = ['name' => 'Budi', 'age' => 25];
        });

        $view = View::make('tests.basic')->render();

        $this->assertEquals('Budi berumur 25', str_replace(["\n", "\t", "\r"], '', $view));
    }
}
