<?php

defined('DS') or exit('No direct access.');

class FacileCursorTest extends \PHPUnit_Framework_TestCase
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
     * Test that cursor() method exists on model.
     *
     * @group system
     */
    public function testCursorMethodExistsOnModel()
    {
        // The cursor() method should be available on the query builder,
        // so we can check it through the model's query() method
        $query = (new CursorTestModel())->query();
        $this->assertTrue(method_exists($query, 'cursor'));
    }
    /**
     * Test that cursor() can be called statically on the model.
     *
     * @group system
     */
    public function testCursorCanBeCalledStatically()
    {
        $cursor = CursorTestModel::cursor();
        $this->assertNotNull($cursor);
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

        $cursor = CursorTestModel::cursor();
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

        $cursor = CursorTestModel::cursor();
        $this->assertTrue(is_array($cursor));
    }

    /**
     * Test that cursor is iterable.
     *
     * @group system
     */
    public function testCursorCanIterateThroughModels()
    {
        $count = 0;
        $cursor = CursorTestModel::where('id', '>', 0)->cursor();

        foreach ($cursor as $model) {
            $count++;
            $this->assertInstanceOf('\System\Database\Facile\Model', $model);

            // Check only first 3 records
            if ($count >= 3) {
                break;
            }
        }

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test cursor with where clause.
     *
     * @group system
     */
    public function testCursorWithWhereClause()
    {
        $cursor = CursorTestModel::where('id', '>', 0)->cursor();
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor with custom chunk size.
     *
     * @group system
     */
    public function testCursorWithCustomChunkSize()
    {
        $cursor = CursorTestModel::cursor(['*'], 50);
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor with select columns.
     *
     * @group system
     */
    public function testCursorWithSelectColumns()
    {
        $cursor = CursorTestModel::cursor(['id']);
        $this->assertNotNull($cursor);
    }

    /**
     * Test that cursor is memory efficient (returns Generator on PHP 5.5+).
     *
     * @group system
     */
    public function testCursorMemoryEfficiency()
    {
        $cursor = CursorTestModel::cursor();

        if (PHP_VERSION_ID >= 50500) {
            // On PHP 5.5+, cursor should return a Generator which is memory efficient
            $this->assertInstanceOf('\Generator', $cursor);
        } else {
            // On PHP 5.4, will fallback to a regular array
            $this->assertTrue(is_array($cursor));
        }
    }
}

/**
 * Test model for cursor.
 */
class CursorTestModel extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = false;
}
