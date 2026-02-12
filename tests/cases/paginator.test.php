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
        // Clean up
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

        $this->assertInstanceOf(\System\Paginator::class, $paginator);
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
        $reflection = new \ReflectionClass(\System\Paginator::class);
        $method = $reflection->getMethod('valid');
        /** @disregard */
        $method->setAccessible(true);

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
     * Test slider method.
     *
     * @group system
     */
    public function testSlider()
    {
        $paginator = \System\Paginator::make(['item'], 20, 2);
        $slider = $paginator->slider();
        $this->assertContains('page-link', $slider);
    }

    /**
     * Test previous method.
     *
     * @group system
     */
    public function testPrevious()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $prev = $paginator->previous();
        $this->assertContains('disabled', $prev); // On page 1, previous is disabled
    }

    /**
     * Test next method.
     *
     * @group system
     */
    public function testNext()
    {
        $paginator = \System\Paginator::make(['item'], 10, 2);
        $paginator->speaks('en');
        $next = $paginator->next();
        $this->assertContains('Next', $next); // Should have next link
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
        $property->setAccessible(true);
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
        $property->setAccessible(true);
        $this->assertEquals('en', $property->getValue($paginator));
    }
}
