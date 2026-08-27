<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;

class PaginationQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('database.connections.pagination', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $pdo = Database::connection('pagination')->pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS pagination_test (id INTEGER PRIMARY KEY, group_id INTEGER, name VARCHAR)');
        $pdo->exec('DELETE FROM pagination_test');

        // The group_id 1 owns 7 rows, group_id 2 owns 2 rows and group_id 3 owns 1 row.
        // So counting a grouped query naively would return 7 instead of 3.
        $groups = [1, 1, 1, 1, 1, 1, 1, 2, 2, 3];

        foreach ($groups as $index => $group) {
            $pdo->exec("INSERT INTO pagination_test (group_id, name) VALUES ($group, 'name$index')");
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Database::connection('pagination')->pdo()->exec('DROP TABLE IF EXISTS pagination_test');
    }

    /**
     * Get a fresh query builder for the test table.
     *
     * @return \System\Database\Query
     */
    protected function query()
    {
        return Database::connection('pagination')->table('pagination_test');
    }

    /**
     * Test that LIMIT and OFFSET do not leak into the count query.
     *
     * @group system
     */
    public function testPaginateIgnoresExistingLimitAndOffsetWhenCounting()
    {
        $paginator = $this->query()->skip(5)->take(3)->paginate(2);

        $this->assertEquals(10, $paginator->total);
        $this->assertEquals(5, $paginator->last);
        $this->assertEquals(2, count($paginator->results));
    }

    /**
     * Test that a grouped query counts the groups instead of the rows of the first group.
     *
     * @group system
     */
    public function testPaginateCountsGroupsInsteadOfRowsOfTheFirstGroup()
    {
        $paginator = $this->query()->group_by('group_id')->paginate(2, ['group_id']);

        $this->assertEquals(3, $paginator->total);
        $this->assertEquals(2, $paginator->last);
        $this->assertEquals(2, count($paginator->results));
    }

    /**
     * Test that a grouped query with a WHERE clause is counted correctly.
     *
     * @group system
     */
    public function testPaginateCountsGroupsOfAConstrainedQuery()
    {
        $paginator = $this->query()
            ->where('group_id', '=', 1)
            ->group_by('group_id')
            ->paginate(2, ['group_id']);

        $this->assertEquals(1, $paginator->total);
        $this->assertEquals(1, $paginator->last);
    }

    /**
     * Test that a query with a HAVING clause is counted correctly.
     *
     * @group system
     */
    public function testPaginateCountsGroupsOfAQueryWithHavingClause()
    {
        $paginator = $this->query()
            ->group_by('group_id')
            ->having('group_id', '>', 1)
            ->paginate(5, ['group_id']);

        $this->assertEquals(2, $paginator->total);
        $this->assertEquals(1, $paginator->last);
    }

    /**
     * Test that the ORDER BY clause is kept for the result query.
     *
     * @group system
     */
    public function testPaginateKeepsOrderingsForTheResultQuery()
    {
        $paginator = $this->query()->order_by('id', 'desc')->paginate(3);

        $this->assertEquals(10, $paginator->total);
        $this->assertEquals(10, $paginator->results[0]->id);
    }

    /**
     * Test that column aliases are stripped off the count query.
     *
     * @group system
     */
    public function testPaginateStripsColumnAliasesFromTheCountQuery()
    {
        $paginator = $this->query()->paginate(3, ['id as identifier']);

        $this->assertEquals(10, $paginator->total);
        $this->assertEquals(3, count($paginator->results));
    }

    /**
     * Test that a distinct query counts the distinct values.
     *
     * @group system
     */
    public function testPaginateCountsDistinctValues()
    {
        $paginator = $this->query()->distinct()->paginate(2, ['group_id']);

        $this->assertEquals(3, $paginator->total);
    }

    /**
     * Test that no result is returned when there is nothing to count.
     *
     * @group system
     */
    public function testPaginateReturnsEmptyResultWhenTotalIsZero()
    {
        $paginator = $this->query()->where('id', '>', 9999)->paginate(5);

        $this->assertEquals(0, $paginator->total);
        $this->assertEquals(1, $paginator->last);
        $this->assertEquals(1, $paginator->page);
        $this->assertEquals(0, count($paginator->results));
    }

    /**
     * Test that the page number may be given explicitly.
     *
     * @group system
     */
    public function testPaginateAcceptsAnExplicitPageNumber()
    {
        $paginator = $this->query()->order_by('id', 'asc')->paginate(3, ['*'], 'page', 2);

        $this->assertEquals(2, $paginator->page);
        $this->assertEquals(4, $paginator->results[0]->id);
    }

    /**
     * Test that facile pagination returns model instances.
     *
     * @group system
     */
    public function testFacilePaginateReturnsModelInstances()
    {
        $paginator = PaginationTestModel::paginate();

        $this->assertEquals(10, $paginator->total);
        $this->assertEquals(4, $paginator->last);
        $this->assertEquals(3, count($paginator->results));
        $this->assertInstanceOf('PaginationTestModel', $paginator->results[0]);
    }

    /**
     * Test that facile pagination is converted into a laravel shaped array.
     *
     * @group system
     */
    public function testFacilePaginateIsConvertedIntoArray()
    {
        $array = PaginationTestModel::paginate()->to_array();

        $this->assertEquals(1, $array['current_page']);
        $this->assertEquals(4, $array['last_page']);
        $this->assertEquals(10, $array['total']);
        $this->assertEquals(3, $array['per_page']);
        $this->assertEquals(1, $array['from']);
        $this->assertEquals(3, $array['to']);
        $this->assertTrue(is_array($array['data'][0]));
        $this->assertEquals(1, $array['data'][0]['id']);
    }

    /**
     * Test that a facile model is JSON serializable.
     *
     * @group system
     */
    public function testFacileModelIsJsonSerializable()
    {
        $model = PaginationTestModel::find(1);

        $this->assertInstanceOf('\JsonSerializable', $model);
        $this->assertEquals($model->to_array(), $model->jsonSerialize());
        $this->assertEquals(json_encode($model->to_array()), $model->to_json());

        $decoded = json_decode(json_encode($model), true);

        $this->assertEquals(1, $decoded['id']);
        $this->assertFalse(isset($decoded['attributes']));
    }
}

/**
 * Test model for pagination.
 */
class PaginationTestModel extends \System\Database\Facile\Model
{
    public static $connection = 'pagination';
    public static $table = 'pagination_test';
    public static $timestamps = false;
    public static $perpage = 3;
}
