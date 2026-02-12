<?php

defined('DS') or exit('No direct access.');

use System\View;
use System\Event;
use System\Package;
use System\Response;

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
     * Test for View::make().
     *
     * @group system
     */
    public function testMakeMethodReturnsAViewInstance()
    {
        $this->assertInstanceOf('\System\View', View::make('home.index'));
    }

    /**
     * Test for View::__construct() - 1.
     *
     * @group system
     */
    public function testViewNameIsSetByConstrutor()
    {
        $view = new View('home.index');
        $this->assertEquals('home.index', $view->view);
    }

    /**
     * Test for View::__construct() - 2.
     *
     * @group system
     */
    public function testViewIsCreatedWithCorrectPath()
    {
        $view = new View('home.index');
        $this->assertEquals(
            str_replace(DS, '/', path('app')) . 'views/home/index.php',
            str_replace(DS, '/', $view->path)
        );
    }

    /**
     * Test for View::__construct() - 3.
     *
     * @group system
     */
    public function testPackageViewIsCreatedWithCorrectPath()
    {
        $view = new View('home.index');
        $this->assertEquals(
            str_replace(DS, '/', Package::path(DEFAULT_PACKAGE)) . 'views/home/index.php',
            str_replace(DS, '/', $view->path)
        );
    }

    /**
     * Test for View::__construct() - 4.
     *
     * @group system
     */
    public function testDataIsSetOnViewByConstructor()
    {
        $view = new View('home.index', ['name' => 'Budi']);
        $this->assertEquals('Budi', $view->data['name']);
    }

    /**
     * Test for View::name().
     *
     * @group system
     */
    public function testNameMethodRegistersAViewName()
    {
        View::name('home.index', 'home');
        $this->assertEquals('home.index', View::$names['home']);
    }

    /**
     * Test for View::shared().
     *
     * @group system
     */
    public function testSharedMethodAddsDataToSharedArray()
    {
        View::share('comment', 'Budi');
        $this->assertEquals('Budi', View::$shared['comment']);
    }

    /**
     * Test for View::with().
     *
     * @group system
     */
    public function testViewDataCanBeSetUsingWithMethod()
    {
        $view = View::make('home.index')->with('comment', 'Budi');
        $this->assertEquals('Budi', $view->data['comment']);
    }

    /**
     * Test for View::__construct() - 5.
     *
     * @group system
     */
    public function testEmptyMessageContainerSetOnViewWhenNoErrorsInSession()
    {
        $view = new View('home.index');
        $this->assertInstanceOf('\System\Messages', $view->data['errors']);
    }

    /**
     * Test for View::__set().
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
     * Test for View::__get().
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
     * Test the \ArrayAccess implementation in view.
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
     * Test the \ArrayAccess implementation in view.
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
     * Test for View::nest().
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
     * Test that data is passed to view correctly.
     *
     * @group system
     */
    public function testDataIsPassedToViewCorrectly()
    {
        View::share('name', 'Budi');
        $view = View::make('tests.basic')->with('age', 25)->render();
        $this->assertEquals('Budi berumur 25<br>', trim($view));
    }

    /**
     * Test that nested views can be rendered correctly.
     *
     * @group system
     */
    public function testNestedViewsAreRendered()
    {
        $view = View::make('tests.basic')->with('age', 25)->nest('name', 'tests.nested');
        $view = trim(str_replace(["\n", "\t", "\r"], '', $view->render()));
        $this->assertEquals('Budi berumur 25<br>', $view);
    }

    /**
     * Test that nested responses can be rendered correctly.
     *
     * @group system
     */
    public function testNestedResponsesAreRendered()
    {
        $view = View::make('tests.basic')->with('age', 25)->with('name', Response::view('tests.nested'));
        $view = trim(str_replace(["\n", "\t", "\r"], '', $view->render()));
        $this->assertEquals('Budi berumur 25<br>', $view);
    }

    /**
     * Test that when a composer event is registered, it is called when the view is rendering.
     *
     * @group system
     */
    public function testComposerEventIsCalledWhenViewIsRendering()
    {
        View::composer('tests.basic', function ($view) {
            $view->data = ['name' => 'Budi', 'age' => 25];
        });

        $view = View::make('tests.basic')->render();

        $view = trim(str_replace(["\n", "\t", "\r"], '', $view));
        $this->assertEquals('Budi berumur 25<br>', $view);
    }
}
