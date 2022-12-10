<?php

defined('DS') or exit('No direct script access.');

use System\Email;

class EmailTest extends \PHPUnit_Framework_TestCase
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
     * @group system
     */
    public function testSomethingIsTrue()
    {
        $this->assertTrue(true);
    }
}
