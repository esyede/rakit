<?php

defined('DS') or exit('No direct access.');

class PaginatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        // Mock dependencies if needed
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        \System\Paginator::$view = 'paginator';
    }

    /**
     * Test creating a paginator instance.
     *
     * @group system
     */
    public function testMakePaginator()
    {
        $results = ['item1', 'item2'];
        $total = 10;
        $perpage = 2;
        $paginator = \System\Paginator::make($results, $total, $perpage);

        $this->assertInstanceOf('\System\Paginator', $paginator);
        $this->assertEquals($results, $paginator->results);
        $this->assertEquals(1, $paginator->page); // Assuming page 1
        $this->assertEquals(5, $paginator->last); // ceil(10/2)
        $this->assertEquals($total, $paginator->total);
        $this->assertEquals($perpage, $paginator->perpage);
    }

    /**
     * Test getting current page.
     *
     * @group system
     */
    public function testPageMethod()
    {
        // Mock Input::get to return 2
        // Since we can't easily mock, assume default or use reflection if needed
        // For simplicity, test with default
        $page = \System\Paginator::page(10, 2);
        $this->assertEquals(1, $page); // Default page 1
    }

    /**
     * Test valid page method using reflection.
     *
     * @group system
     */
    public function testValidPage()
    {
        $reflection = new \ReflectionClass('\System\Paginator');
        $method = $reflection->getMethod('valid');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, 1));
        $this->assertTrue($method->invoke(null, 5));
        $this->assertFalse($method->invoke(null, 0));
        $this->assertFalse($method->invoke(null, -1));
        $this->assertFalse($method->invoke(null, 'abc'));
    }

    /**
     * Test links method.
     *
     * @group system
     */
    public function testLinks()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $links = $paginator->links();
        $this->assertContains('<nav class="pagination-nav">', $links);
        $this->assertContains('<ul class="pagination">', $links);
    }

    /**
     * Test that the slider is rendered when there are many pages.
     *
     * @group system
     */
    public function testLinksRendersASliderForManyPages()
    {
        $paginator = \System\Paginator::make(['item'], 100, 2);
        $links = $paginator->links();

        $this->assertContains('page-dots', $links);
        $this->assertContains('page-link', $links);
    }

    /**
     * Test that nothing is rendered when there is only one page.
     *
     * @group system
     */
    public function testLinksIsEmptyWhenThereIsOnlyOnePage()
    {
        $paginator = \System\Paginator::make(['item'], 1, 10);
        $this->assertEquals('', trim($paginator->links()));
    }

    /**
     * Test that the previous element is disabled on the first page.
     *
     * @group system
     */
    public function testPreviousElementIsDisabledOnTheFirstPage()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $elements = $paginator->link_elements();

        $this->assertEquals('previous', $elements[0]['type']);
        $this->assertTrue($elements[0]['disabled']);
        $this->assertNull($elements[0]['url']);
        $this->assertContains('previous_page page-item disabled', $paginator->links());
    }

    /**
     * Test that the next element points to the following page.
     *
     * @group system
     */
    public function testNextElementPointsToTheFollowingPage()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $paginator->speaks('en');
        $elements = $paginator->link_elements();
        $last = $elements[count($elements) - 1];

        $this->assertEquals('next', $last['type']);
        $this->assertFalse($last['disabled']);
        $this->assertEquals(2, $last['page']);
        $this->assertContains('Next', $paginator->links());
    }

    /**
     * Test that the next element is disabled on the last page.
     *
     * @group system
     */
    public function testNextElementIsDisabledOnTheLastPage()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2, 'page', 5);
        $elements = $paginator->link_elements();
        $last = $elements[count($elements) - 1];

        $this->assertTrue($last['disabled']);
        $this->assertNull($last['url']);
        $this->assertContains('next_page page-item disabled', $paginator->links());
    }

    /**
     * Test that the pagination view stub exists.
     *
     * @group system
     */
    public function testStubPathPointsToTheViewStub()
    {
        $stub = \System\Paginator::stub_path();

        $this->assertTrue(is_file($stub));
        $this->assertContains('pagination-nav', file_get_contents($stub));
    }

    /**
     * Test that the view is published from its stub the first time it is needed.
     *
     * @group system
     */
    public function testLinksPublishesTheViewStubWhenTheViewIsMissing()
    {
        $target = \System\Paginator::published_path();
        $backup = file_get_contents($target);

        unlink($target);
        clearstatcache();

        $links = \System\Paginator::make(['item'], 30, 10)->links();
        $published = is_file($target) ? file_get_contents($target) : null;

        file_put_contents($target, $backup, LOCK_EX);
        clearstatcache();

        $this->assertNotNull($published);
        $this->assertEquals(file_get_contents(\System\Paginator::stub_path()), $published);
        $this->assertContains('<nav class="pagination-nav">', $links);
    }

    /**
     * Test that an already published view is never overwritten.
     *
     * @group system
     */
    public function testPublishViewNeverOverwritesAnExistingView()
    {
        $target = \System\Paginator::published_path();
        $backup = file_get_contents($target);

        file_put_contents($target, 'CUSTOMIZED', LOCK_EX);
        clearstatcache();

        $published = \System\Paginator::publish_view();
        $content = file_get_contents($target);

        file_put_contents($target, $backup, LOCK_EX);
        clearstatcache();

        $this->assertTrue($published);
        $this->assertEquals('CUSTOMIZED', $content);
    }

    /**
     * Test that a missing custom view is never silently published.
     *
     * @group system
     */
    public function testLinksThrowsWhenTheConfiguredViewIsMissing()
    {
        $this->setExpectedException('\Exception', 'View does not exist: paginator_that_does_not_exist');
        \System\Paginator::$view = 'paginator_that_does_not_exist';
        \System\Paginator::make(['item'], 30, 10)->links();
    }

    /**
     * Test that an explicitly given view is never silently replaced.
     *
     * @group system
     */
    public function testLinksThrowsWhenAnExplicitlyGivenViewIsMissing()
    {
        $this->setExpectedException('\Exception', 'View does not exist: paginator_that_does_not_exist');
        \System\Paginator::make(['item'], 30, 10)->links(3, 'paginator_that_does_not_exist');
    }

    /**
     * Test that links() can be rendered using a custom view.
     *
     * @group system
     */
    public function testLinksCanUseACustomView()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $this->assertEquals('Budi', trim($paginator->links(3, 'tests.nested')));
    }

    /**
     * Test appends method.
     *
     * @group system
     */
    public function testAppends()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $paginator->appends(['key' => 'value']);

        $reflection = new \ReflectionClass($paginator);
        $property = $reflection->getProperty('appends');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $property->setAccessible(true);
        $this->assertEquals(['key' => 'value'], $property->getValue($paginator));
    }

    /**
     * Test speaks method.
     *
     * @group system
     */
    public function testSpeaks()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $paginator->speaks('en');

        $reflection = new \ReflectionClass($paginator);
        $property = $reflection->getProperty('language');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $property->setAccessible(true);
        $this->assertEquals('en', $property->getValue($paginator));
    }

    /**
     * Test that the last page is at least 1 even when there is no result at all.
     *
     * @group system
     */
    public function testLastPageIsAtLeastOneWhenThereIsNoResult()
    {
        $paginator = \System\Paginator::make([], 0, 15);

        $this->assertEquals(1, $paginator->last);
        $this->assertEquals(1, $paginator->page);
        $this->assertEquals(0, $paginator->total);
        $this->assertTrue($paginator->is_empty());
        $this->assertFalse($paginator->is_not_empty());
        $this->assertFalse($paginator->has_pages());
        $this->assertNull($paginator->first_item());
        $this->assertNull($paginator->last_item());
    }

    /**
     * Test the accessor methods.
     *
     * @group system
     */
    public function testAccessorMethods()
    {
        $paginator = \System\Paginator::make(['a', 'b', 'c'], 10, 3);

        $this->assertEquals(['a', 'b', 'c'], $paginator->items());
        $this->assertEquals(1, $paginator->current_page());
        $this->assertEquals(4, $paginator->last_page());
        $this->assertEquals(10, $paginator->total());
        $this->assertEquals(3, $paginator->per_page());
        $this->assertEquals(1, $paginator->first_item());
        $this->assertEquals(3, $paginator->last_item());
        $this->assertEquals('page', $paginator->page_name());
        $this->assertTrue($paginator->has_pages());
        $this->assertTrue($paginator->has_more_pages());
        $this->assertTrue($paginator->on_first_page());
        $this->assertFalse($paginator->on_last_page());
    }

    /**
     * Test that the paginator is countable, iterable and array accessible.
     *
     * @group system
     */
    public function testPaginatorIsCountableIterableAndArrayAccessible()
    {
        $paginator = \System\Paginator::make(['a', 'b'], 10, 2);
        $collected = [];

        foreach ($paginator as $item) {
            $collected[] = $item;
        }

        $this->assertEquals(2, count($paginator));
        $this->assertEquals(['a', 'b'], $collected);
        $this->assertEquals('a', $paginator[0]);
        $this->assertTrue(isset($paginator[1]));
        $this->assertFalse(isset($paginator[9]));
        $this->assertNull($paginator[9]);
    }

    /**
     * Test to_array() method.
     *
     * @group system
     */
    public function testToArrayUsesTheSameKeysAsLaravel()
    {
        $paginator = \System\Paginator::make([['id' => 1]], 10, 3);
        $array = $paginator->to_array();

        $expected = [
            'current_page', 'data', 'first_page_url', 'from', 'last_page', 'last_page_url',
            'links', 'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', 'total',
        ];

        $this->assertEquals($expected, array_keys($array));
        $this->assertEquals(1, $array['current_page']);
        $this->assertEquals(4, $array['last_page']);
        $this->assertEquals(10, $array['total']);
        $this->assertEquals(3, $array['per_page']);
        $this->assertEquals(1, $array['from']);
        $this->assertEquals(1, $array['to']);
        $this->assertEquals([['id' => 1]], $array['data']);
        $this->assertNull($array['prev_page_url']);
        $this->assertContains('page=2', $array['next_page_url']);
    }

    /**
     * Test to_json() and jsonSerialize() methods.
     *
     * @group system
     */
    public function testJsonSerializationUsesToArray()
    {
        $paginator = \System\Paginator::make([['id' => 1]], 10, 3);

        $this->assertInstanceOf('\JsonSerializable', $paginator);
        $this->assertEquals($paginator->to_array(), $paginator->jsonSerialize());
        $this->assertEquals(json_encode($paginator->to_array()), $paginator->to_json());
        $this->assertEquals(json_encode($paginator->to_array()), json_encode($paginator));
    }

    /**
     * Test link_elements() method.
     *
     * @group system
     */
    public function testLinkElements()
    {
        $paginator = \System\Paginator::make(['a'], 10, 5);
        $elements = $paginator->link_elements();

        // 1 previous + 2 pages + 1 next
        $this->assertEquals(4, count($elements));
        $this->assertNull($elements[0]['url']);
        $this->assertEquals('1', $elements[1]['label']);
        $this->assertTrue($elements[1]['active']);
        $this->assertEquals('2', $elements[2]['label']);
        $this->assertFalse($elements[2]['active']);
        $this->assertContains('page=2', $elements[3]['url']);
    }

    /**
     * Test url() method with appends and fragment.
     *
     * @group system
     */
    public function testUrlContainsAppendsAndFragment()
    {
        $paginator = \System\Paginator::make(['a'], 10, 2);
        $url = $paginator->appends(['sort' => 'asc'])->fragment('list')->url(3);

        $this->assertContains('sort=asc', $url);
        $this->assertContains('page=3', $url);
        $this->assertContains('#list', $url);
        $this->assertEquals('list', $paginator->fragment());
    }

    /**
     * Test that appends() merges values and never overrides the page number.
     *
     * @group system
     */
    public function testAppendsMergesValuesAndIgnoresPageName()
    {
        $paginator = \System\Paginator::make(['a'], 10, 2);
        $paginator->appends(['sort' => 'asc', 'page' => 9]);
        $paginator->appends('filter', 'new');

        $reflection = new \ReflectionClass($paginator);
        $property = $reflection->getProperty('appends');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $property->setAccessible(true);

        $this->assertEquals(['sort' => 'asc', 'filter' => 'new'], $property->getValue($paginator));
        $this->assertContains('page=2', $paginator->url(2));
    }

    /**
     * Test the page url methods.
     *
     * @group system
     */
    public function testPageUrlMethods()
    {
        $paginator = \System\Paginator::make(['a'], 100, 10, 'page', 5);

        $this->assertEquals(5, $paginator->page);
        $this->assertContains('page=1', $paginator->first_page_url());
        $this->assertContains('page=10', $paginator->last_page_url());
        $this->assertContains('page=6', $paginator->next_page_url());
        $this->assertContains('page=4', $paginator->previous_page_url());

        $first = \System\Paginator::make(['a'], 100, 10, 'page', 1);
        $last = \System\Paginator::make(['a'], 100, 10, 'page', 10);

        $this->assertNull($first->previous_page_url());
        $this->assertNull($last->next_page_url());
    }

    /**
     * Test that the page number may be read from a custom query string parameter.
     *
     * @group system
     */
    public function testCustomPageName()
    {
        \System\Request::foundation()->query->set('list_page', 3);
        $paginator = \System\Paginator::make(['a'], 100, 10, 'list_page');
        \System\Request::foundation()->query->remove('list_page');

        $this->assertEquals(3, $paginator->page);
        $this->assertEquals('list_page', $paginator->page_name());
        $this->assertContains('list_page=4', $paginator->next_page_url());
    }

    /**
     * Test through() method.
     *
     * @group system
     */
    public function testThroughTransformsTheResults()
    {
        $paginator = \System\Paginator::make([1, 2, 3], 10, 3);
        $paginator->through(function ($item) {
            return $item * 2;
        });

        $this->assertEquals([2, 4, 6], $paginator->results);
    }

    /**
     * Test that render() is an alias for links().
     *
     * @group system
     */
    public function testRenderIsAnAliasForLinks()
    {
        $paginator = \System\Paginator::make(['a'], 10, 2);
        $this->assertEquals($paginator->links(), $paginator->render());
    }

}
