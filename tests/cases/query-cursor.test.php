<?php

defined('DS') or exit('No direct access.');

use System\Database;

class QueryCursorTest extends \PHPUnit_Framework_TestCase
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
        // Cleanup
    }

    /**
     * Test that cursor() method exists.
     *
     * @group system
     */
    public function testCursorMethodExists()
    {
        $query = Database::table('users');
        $this->assertTrue(method_exists($query, 'cursor'));
    }

    /**
     * Test that cursor() returns a Generator on PHP >= 5.5.
     *
     * @group system
     */
    public function testCursorReturnsGeneratorOnPhp55Plus()
    {
        if (PHP_VERSION_ID < 50500) {
            $this->markTestSkipped('This test is for PHP 5.5.0+ only');
        }

        $cursor = Database::table('users')->cursor();
        $this->assertInstanceOf('\Generator', $cursor);
    }

    /**
     * Test that cursor() returns an array on PHP <= 5.4.
     *
     * @group system
     */
    public function testCursorReturnsArrayOnPhp54()
    {
        if (PHP_VERSION_ID >= 50500) {
            $this->markTestSkipped('This test is for PHP 5.4 only');
        }

        $cursor = Database::table('users')->cursor();
        $this->assertTrue(is_array($cursor));
    }

    /**
     * Test that cursor is iterable.
     *
     * @group system
     */
    public function testCursorCanBeIterated()
    {
        $count = 0;
        $cursor = Database::table('users')->cursor();

        foreach ($cursor as $user) {
            $count++;
            $this->assertTrue(is_object($user));

            // Check only first 3 records
            if ($count >= 3) {
                break;
            }
        }

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test cursor with custom chunk size.
     *
     * @group system
     */
    public function testCursorWithCustomChunkSize()
    {
        $cursor = Database::table('users')->cursor(['*'], 100);
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor with where clause.
     *
     * @group system
     */
    public function testCursorWithWhereClause()
    {
        $cursor = Database::table('users')->where('id', '>', 0)->cursor();
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor with select columns.
     *
     * @group system
     */
    public function testCursorWithSelectColumns()
    {
        $cursor = Database::table('users')->cursor(['id', 'name']);
        $this->assertNotNull($cursor);
    }
}
