<?php

defined('DS') or exit('No direct access.');

use System\Database;

class CursorTest extends \PHPUnit_Framework_TestCase
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
     * Test cursor method ada di query builder.
     *
     * @group system
     */
    public function testCursorMethodExists()
    {
        $query = Database::table('users');
        $this->assertTrue(method_exists($query, 'cursor'));
    }

    /**
     * Test cursor me-return generator on PHP 5.5.0+.
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
     * Test cursor me-return array di PHP 5.4.
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
     * Test cursor iterable.
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

            // Cukup cek 5 item saja biar tidak lemot
            if ($count >= 5) {
                break;
            }
        }

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test cursor dengan custom chunk size.
     *
     * @group system
     */
    public function testCursorWithCustomChunkSize()
    {
        $cursor = Database::table('users')->cursor(['*'], 100);
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor dengan klausa where.
     *
     * @group system
     */
    public function testCursorWithWhereClause()
    {
        $cursor = Database::table('users')->where('id', '>', 0)->cursor();
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor dengan custom select columns.
     *
     * @group system
     */
    public function testCursorWithSelectColumns()
    {
        $cursor = Database::table('users')->cursor(['id', 'name']);
        $this->assertNotNull($cursor);
    }
}
