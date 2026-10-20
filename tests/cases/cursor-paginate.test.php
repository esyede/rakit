<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;

class CursorPaginateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The connection before the test.
     *
     * @var string
     */
    protected $connection;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->connection = Config::get('database.default');
        Config::set('database.default', 'sqlite');
        Database::$connections = [];

        Schema::create('cursor_items', function ($table) {
            $table->increments('id');
            $table->integer('score');
        });

        // Repeated scores: the id breaks the ties.
        foreach ([5, 3, 5, 1, 4, 3, 5, 2, 2, 4, 1, 5, 3] as $score) {
            Database::table('cursor_items')->insert(['score' => $score]);
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('cursor_items');
        Config::set('database.default', $this->connection);
        Database::$connections = [];
    }

    /**
     * Get the ids of a page.
     *
     * @param \System\Paginator\Cursor $page
     *
     * @return array
     */
    protected function ids($page)
    {
        $ids = [];

        foreach ($page as $row) {
            $ids[] = (int) $row->id;
        }

        return $ids;
    }

    /**
     * Walking forward then back visits every row once, in order, with mixed directions.
     *
     * @group system
     */
    public function testWalksForwardAndBack()
    {
        $query = function ($cursor) {
            return Database::table('cursor_items')->order_by('score', 'desc')->order_by('id', 'asc')
                ->cursor_paginate(4, ['*'], 'cursor', $cursor);
        };

        $expected = array_map('intval', Database::table('cursor_items')->order_by('score', 'desc')->order_by('id', 'asc')->lists('id'));

        $pages = [];
        $page = $query(null);
        $this->assertTrue($page->on_first_page());
        $this->assertNull($page->previous_page_url());

        while (true) {
            $pages[] = $this->ids($page);

            if (! $page->has_more_pages()) {
                break;
            }

            $page = $query($page->next_cursor);
        }

        $this->assertEquals($expected, call_user_func_array('array_merge', $pages));
        $this->assertCount(4, $pages);
        $this->assertTrue($page->on_last_page());

        // Back from the last page: the same pages, in reverse.
        for ($i = count($pages) - 2; $i >= 0; --$i) {
            $page = $query($page->previous_cursor);
            $this->assertEquals($pages[$i], $this->ids($page));
        }

        $this->assertNull($page->previous_cursor);
        $this->assertNotNull($page->next_cursor);
    }

    /**
     * Without ORDER BY it orders by id; Facile models are hydrated; bad cursors start over.
     *
     * @group system
     */
    public function testDefaultsAndModels()
    {
        $page = CursorPaginateItem::cursor_paginate(5);
        $this->assertInstanceOf('CursorPaginateItem', $page[0]);
        $this->assertEquals([1, 2, 3, 4, 5], $this->ids($page));

        $second = CursorPaginateItem::where('score', '>', 0)->cursor_paginate(5, ['*'], 'cursor', $page->next_cursor);
        $this->assertEquals([6, 7, 8, 9, 10], $this->ids($second));

        // A forged or foreign cursor is ignored: values only, never columns.
        $forged = rtrim(strtr(base64_encode(json_encode(['v' => ['score' => 1], 'b' => false])), '+/', '-_'), '=');
        $this->assertEquals([1, 2, 3, 4, 5], $this->ids(CursorPaginateItem::cursor_paginate(5, ['*'], 'cursor', $forged)));
        $this->assertEquals([1, 2, 3, 4, 5], $this->ids(CursorPaginateItem::cursor_paginate(5, ['*'], 'cursor', '%%%')));

        $array = $second->to_array();
        $this->assertEquals(['data', 'path', 'per_page', 'next_cursor', 'next_page_url', 'prev_cursor', 'prev_page_url'], array_keys($array));
        $this->assertContains('cursor='.$second->next_cursor, $second->next_page_url());

        $elements = $second->link_elements();
        $this->assertEquals(['previous', 'next'], [$elements[0]['type'], $elements[1]['type']]);
        $this->assertFalse($elements[0]['disabled']);
    }

    /**
     * Raw orderings and unselected order columns are refused.
     *
     * @group system
     */
    public function testRefusesWhatItCannotPaginate()
    {
        try {
            Database::table('cursor_items')->order_by_raw('score + 1')->cursor_paginate(3);
            $this->fail('raw ordering accepted');
        } catch (\Exception $e) {
            $this->assertContains('plain ORDER BY', $e->getMessage());
        }

        $this->setExpectedException('Exception', 'column selected');
        Database::table('cursor_items')->order_by('score')->cursor_paginate(3, ['id']);
    }
}

class CursorPaginateItem extends \System\Database\Facile\Model
{
    public static $table = 'cursor_items';
    public static $timestamps = false;
}
