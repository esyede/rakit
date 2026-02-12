<?php

defined('DS') or exit('No direct access.');

use System\Section;

class SectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Section::$sections = [];
        Section::$last = [];
        Section::$stacks = [];
    }

    /**
     * Test start and yield_section.
     *
     * @group system
     */
    public function testStartAndYield()
    {
        Section::start('header');
        echo 'Title';
        Section::yield_section();
        $this->assertEquals('Title', Section::yield_content('header'));
    }

    /**
     * Test for inject.
     *
     * @group system
     */
    public function testInject()
    {
        Section::inject('footer', 'Copyright');
        $this->assertEquals('Copyright', Section::yield_content('footer'));
    }

    /**
     * Test for has.
     *
     * @group system
     */
    public function testHas()
    {
        $this->assertFalse(Section::has('missing'));
        Section::inject('existing', 'data');
        $this->assertTrue(Section::has('existing'));
    }

    /**
     * Test for append.
     *
     * @group system
     */
    public function testAppend()
    {
        Section::inject('content', 'First');
        Section::append('content', ' Second');
        $this->assertEquals('First Second', Section::yield_content('content'));
    }

    /**
     * Test for extend with @parent.
     *
     * @group system
     */
    public function testExtendWithParent()
    {
        Section::inject('layout', 'Base @parent End');
        Section::start('layout');
        echo 'Content';
        Section::yield_section();
        $this->assertEquals('Base Content End', Section::yield_content('layout'));
    }

    /**
     * Test for push and stack.
     *
     * @group system
     */
    public function testPushAndStack()
    {
        Section::push('scripts');
        echo '<script>alert(1);</script>';
        Section::endpush();

        Section::push('scripts');
        echo '<script>alert(2);</script>';
        Section::endpush();

        $this->assertEquals('<script>alert(1);</script><script>alert(2);</script>', Section::stack('scripts'));
    }



    /**
     * Test for yield_content with empty value.
     *
     * @group system
     */
    public function testYieldContentEmpty()
    {
        $this->assertEquals('', Section::yield_content('nonexistent'));
    }

    /**
     * Test for stack with empty value.
     *
     * @group system
     */
    public function testStackEmpty()
    {
        $this->assertEquals('', Section::stack('nonexistent'));
    }
}
