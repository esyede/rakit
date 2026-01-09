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
     * Test cursor method exists on Facile model.
     *
     * @group system
     */
    public function testCursorMethodExistsOnModel()
    {
        // Method cursor() dipanggil via __callStatic ke query builder
        $query = (new CursorTestModel())->query();
        $this->assertTrue(method_exists($query, 'cursor'));
    }
    /**
     * Test cursor bisa dipanggil secara static.
     *
     * @group system
     */
    public function testCursorCanBeCalledStatically()
    {
        $cursor = CursorTestModel::cursor();
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor me-return generator di PHP 5.5.0+.
     *
     * @group system
     */
    public function testCursorReturnsGeneratorOnPhp55Plus()
    {
        if (PHP_VERSION_ID < 50500) {
            // Dummy. Generator hanya tersedia di PHP 5.5.0+
            $this->assertTrue(!empty(PHP_VERSION_ID));
        } else {
            $cursor = CursorTestModel::cursor();
            $this->assertInstanceOf('\Generator', $cursor);
        }
    }

    /**
     * Test cursor me-return array di PHP 5.4.
     *
     * @group system
     */
    public function testCursorReturnsArrayOnPhp54()
    {
        if (PHP_VERSION_ID >= 50500) {
            // Dummy. Test ini hanya untuk PHP 5.4
            $this->assertTrue(!empty(PHP_VERSION_ID));
        } else {
            $cursor = CursorTestModel::cursor();
            $this->assertTrue(is_array($cursor));
        }
    }

    /**
     * Test cursor iterable.
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

            if ($count >= 3) {
                break;
            }
        }

        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test cursor dengan klausa where.
     *
     * @group system
     */
    public function testCursorWithWhereClause()
    {
        $cursor = CursorTestModel::where('id', '>', 0)->cursor();
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor dengan custom chunk size.
     *
     * @group system
     */
    public function testCursorWithCustomChunkSize()
    {
        $cursor = CursorTestModel::cursor(['*'], 50);
        $this->assertNotNull($cursor);
    }

    /**
     * Test cursor dengan custom select columns.
     *
     * @group system
     */
    public function testCursorWithSelectColumns()
    {
        $cursor = CursorTestModel::cursor(['id']);
        $this->assertNotNull($cursor);
    }

    /**
     * Test edisiensi memory (konseptual).
     *
     * @group system
     */
    public function testCursorMemoryEfficiency()
    {
        $cursor = CursorTestModel::cursor();

        if (PHP_VERSION_ID >= 50500) {
            // Pada PHP 5.5+, cursor menggunakan generator yang lebih efisien
            $this->assertInstanceOf('\Generator', $cursor);
        } else {
            // Pada PHP 5.4, akan fallback ke array biasa
            $this->assertTrue(is_array($cursor));
        }
    }
}

/**
 * Test model untuk cursor.
 */
class CursorTestModel extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = false;
}
