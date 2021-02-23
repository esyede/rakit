<?php

namespace Docs\Tests;

defined('DS') or exit('No direct script access.');

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
     * @group docs
     */
    public function testSomethingIsTrue()
    {
        $this->assertTrue(true);
    }
}
