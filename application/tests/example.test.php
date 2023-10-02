<?php

namespace Application\Tests;

defined('DS') or exit('No direct access.');

class ExampleTest extends \PHPUnit_Framework_TestCase
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
     * Contoh test sederhana.
     *
     * @group application
     */
    public function testSomethingIsTrue()
    {
        $this->assertTrue(true);
    }
}
