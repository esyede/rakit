<?php

defined('DS') or exit('No direct access.');

use System\Paginator;
use System\Transformer;

/**
 * Covers the transformer that shapes a model, or a list of them, into the
 * array that goes out as JSON.
 */
class TransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tear down.
     */
    public function tearDown()
    {
        Transformer::$wrap = 'data';
    }

    // -------------------------------------------------------------------------
    // Shaping
    // -------------------------------------------------------------------------

    /**
     * Without a to_array() of its own, the transformer asks the resource.
     *
     * @group system
     */
    public function testResourceIsAskedForItsOwnArray()
    {
        $this->assertEquals(
            ['data' => ['id' => 1, 'nama' => 'Budi']],
            Transformer::make(new TransformerProbe())->resolve()
        );

        $this->assertEquals(
            ['data' => ['a' => 1]],
            Transformer::make(['a' => 1])->resolve()
        );
    }

    /**
     * A resource that is nothing at all does not blow up.
     *
     * @group system
     */
    public function testEmptyResource()
    {
        $this->assertEquals(['data' => []], Transformer::make(null)->resolve());
        $this->assertEquals(['data' => []], Transformer::make([])->resolve());
    }

    /**
     * The attributes and the methods of the resource are reachable.
     *
     * @group system
     */
    public function testResourceIsReachable()
    {
        $transformer = Transformer::make(new TransformerProbe());

        $this->assertEquals('Budi', $transformer->nama);
        $this->assertEquals('halo Budi', $transformer->sapa());
        $this->assertTrue(isset($transformer->nama));
        $this->assertFalse(isset($transformer->entah));
        $this->assertNull($transformer->entah);
    }

    /**
     * The resource is reachable as an array too.
     *
     * @group system
     */
    public function testResourceIsReachableAsAnArray()
    {
        $transformer = Transformer::make(['nama' => 'Budi']);

        $this->assertTrue(isset($transformer['nama']));
        $this->assertEquals('Budi', $transformer['nama']);
        $this->assertNull($transformer['entah']);
    }

    /**
     * A method that is on neither side says so.
     *
     * @group system
     *
     * @expectedException BadMethodCallException
     */
    public function testUnknownMethodThrows()
    {
        Transformer::make(new TransformerProbe())->tidak_ada();
    }

    // -------------------------------------------------------------------------
    // Conditional keys
    // -------------------------------------------------------------------------

    /**
     * A key left out by when() does not appear at all, rather than appearing
     * as null.
     *
     * @group system
     */
    public function testWhenLeavesTheKeyOut()
    {
        $transformer = new TransformerConditional([]);
        $transformer->admin = false;

        $data = $transformer->resolve();

        $this->assertArrayNotHasKey('email', $data['data']);
        $this->assertArrayHasKey('id', $data['data']);
    }

    /**
     * With the condition held, the key is there.
     *
     * @group system
     */
    public function testWhenKeepsTheKey()
    {
        $transformer = new TransformerConditional([]);
        $transformer->admin = true;

        $data = $transformer->resolve();

        $this->assertEquals('budi@site.com', $data['data']['email']);
    }

    /**
     * A default given to when() is used when the condition does not hold, even
     * when that default is null.
     *
     * @group system
     */
    public function testWhenFallsBackToItsDefault()
    {
        $transformer = Transformer::make([]);

        $this->assertEquals('cadangan', $transformer->when(false, 'nilai', 'cadangan'));
        $this->assertNull($transformer->when(false, 'nilai', null));
        $this->assertInstanceOf('System\Transformer\Missing', $transformer->when(false, 'nilai'));
    }

    /**
     * A closure handed to when() is only run when it is needed.
     *
     * @group system
     */
    public function testWhenTakesAClosure()
    {
        $transformer = Transformer::make([]);
        $run = false;

        $callback = function () use (&$run) {
            $run = true;

            return 'dihitung';
        };

        $this->assertInstanceOf('System\Transformer\Missing', $transformer->when(false, $callback));
        $this->assertFalse($run);

        $this->assertEquals('dihitung', $transformer->when(true, $callback));
        $this->assertTrue($run);
    }

    /**
     * merge_when() folds its values into the array around it.
     *
     * @group system
     */
    public function testMergeWhen()
    {
        $data = TransformerMerging::make([])->resolve();

        $this->assertEquals(
            ['id' => 1, 'x' => 10, 'y' => 20, 'nested' => ['l' => 'ada']],
            $data['data']
        );
    }

    // -------------------------------------------------------------------------
    // Nesting
    // -------------------------------------------------------------------------

    /**
     * A transformer inside another one contributes its data, not a wrapper of
     * its own.
     *
     * @group system
     */
    public function testNestedTransformerIsNotWrappedAgain()
    {
        $data = TransformerNesting::make([])->resolve();

        $this->assertEquals(['id' => 7, 'nama' => 'Dewi'], $data['data']['penulis']);
        $this->assertEquals([['a' => 1], ['a' => 2]], $data['data']['komentar']);
        $this->assertEquals(['x' => 1], $data['data']['dalam']['lagi']);
    }

    // -------------------------------------------------------------------------
    // Wrapping
    // -------------------------------------------------------------------------

    /**
     * The wrapper key can be changed, and dropped.
     *
     * @group system
     */
    public function testWrapping()
    {
        $this->assertEquals(['data' => ['a' => 1]], Transformer::make(['a' => 1])->resolve());
        $this->assertEquals(['hasil' => ['a' => 1]], TransformerWrapped::make(['a' => 1])->resolve());
        $this->assertEquals(['a' => 1], TransformerUnwrapped::make(['a' => 1])->resolve());
    }

    /**
     * A wrapper set on one transformer is not the wrapper of every other one.
     *
     * @group system
     */
    public function testWrapperOfASubclassStaysThere()
    {
        TransformerWrapped::make(['a' => 1])->resolve();

        $this->assertEquals(['data' => ['b' => 2]], Transformer::make(['b' => 2])->resolve());
    }

    /**
     * without_wrapping() drops the wrapper everywhere.
     *
     * @group system
     */
    public function testWithoutWrapping()
    {
        Transformer::without_wrapping();

        $this->assertEquals(['a' => 1], Transformer::make(['a' => 1])->resolve());
    }

    /**
     * additional() puts data beside the wrapped data, and with() does the same
     * from inside the transformer.
     *
     * @group system
     */
    public function testDataBesideTheWrappedData()
    {
        $this->assertEquals(
            ['data' => ['a' => 1], 'versi' => '1.0'],
            Transformer::make(['a' => 1])->additional(['versi' => '1.0'])->resolve()
        );

        $this->assertEquals(
            ['data' => ['a' => 1], 'penulis' => 'rakit'],
            TransformerWithMeta::make(['a' => 1])->resolve()
        );
    }

    /**
     * Without a wrapper, the extra data is merged into the data itself.
     *
     * @group system
     */
    public function testExtraDataWithoutAWrapper()
    {
        $this->assertEquals(
            ['a' => 1, 'versi' => '1.0'],
            TransformerUnwrapped::make(['a' => 1])->additional(['versi' => '1.0'])->resolve()
        );
    }

    // -------------------------------------------------------------------------
    // Collections
    // -------------------------------------------------------------------------

    /**
     * A list of resources is handed to one transformer each.
     *
     * @group system
     */
    public function testCollection()
    {
        $collection = TransformerSimple::collection([['a' => 1], ['a' => 2]]);

        $this->assertInstanceOf('System\Transformer\Collection', $collection);
        $this->assertEquals(['data' => [['a' => 1], ['a' => 2]]], $collection->resolve());
        $this->assertCount(2, $collection);
    }

    /**
     * The collection is walkable, and hands out transformers.
     *
     * @group system
     */
    public function testCollectionIsWalkable()
    {
        $seen = [];

        foreach (TransformerSimple::collection([['a' => 1], ['a' => 2]]) as $item) {
            $seen[] = get_class($item);
        }

        $this->assertEquals(['TransformerSimple', 'TransformerSimple'], $seen);
    }

    /**
     * An empty list is an empty list, not a missing one.
     *
     * @group system
     */
    public function testEmptyCollection()
    {
        $this->assertEquals(['data' => []], TransformerSimple::collection([])->resolve());
    }

    /**
     * A collection takes whatever the list arrived as.
     *
     * @group system
     */
    public function testCollectionTakesEveryShapeOfList()
    {
        $expected = ['data' => [['a' => 1], ['a' => 2]]];

        $this->assertEquals(
            $expected,
            TransformerSimple::collection(new System\Collection([['a' => 1], ['a' => 2]]))->resolve()
        );
        $this->assertEquals(
            $expected,
            TransformerSimple::collection(new \ArrayIterator([['a' => 1], ['a' => 2]]))->resolve()
        );
    }

    /**
     * An item that is already a transformer is left alone.
     *
     * @group system
     */
    public function testCollectionKeepsATransformerItIsGiven()
    {
        $collection = TransformerSimple::collection([TransformerSimple::make(['a' => 9])]);

        $this->assertEquals(['data' => [['a' => 9]]], $collection->resolve());
    }

    /**
     * A collection of a paginator carries its links and its counts.
     *
     * @group system
     */
    public function testCollectionOfAPaginator()
    {
        $paginator = Paginator::make([['a' => 1], ['a' => 2]], 25, 2);
        $data = TransformerSimple::collection($paginator)->resolve();

        $this->assertEquals([['a' => 1], ['a' => 2]], $data['data']);

        $this->assertArrayHasKey('first', $data['links']);
        $this->assertArrayHasKey('last', $data['links']);
        $this->assertArrayHasKey('prev', $data['links']);
        $this->assertArrayHasKey('next', $data['links']);

        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(2, $data['meta']['per_page']);
        $this->assertEquals(25, $data['meta']['total']);
        $this->assertEquals(13, $data['meta']['last_page']);
    }

    /**
     * The results of a paginator are a collection as often as they are an
     * array, and both have to be read as a list of items.
     *
     * @group system
     */
    public function testCollectionOfAPaginatorWhoseResultsAreACollection()
    {
        $paginator = Paginator::make(new System\Collection([['a' => 1], ['a' => 2]]), 25, 2);
        $data = TransformerSimple::collection($paginator)->resolve();

        $this->assertEquals([['a' => 1], ['a' => 2]], $data['data']);
        $this->assertEquals(25, $data['meta']['total']);
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    /**
     * The transformer turns into JSON, and into a response.
     *
     * @group system
     */
    public function testOutput()
    {
        $transformer = Transformer::make(['a' => 1]);

        $this->assertEquals('{"data":{"a":1}}', $transformer->to_json());
        $this->assertEquals('{"data":{"a":1}}', (string) $transformer);
        $this->assertEquals('{"data":{"a":1}}', json_encode($transformer));

        $response = $transformer->to_response(201);

        $this->assertInstanceOf('System\Response', $response);
        $this->assertEquals(201, $response->status());
        $this->assertEquals('{"data":{"a":1}}', $response->content);
    }
}

/**
 * A resource with an array of its own.
 */
class TransformerProbe
{
    public $id = 1;

    public $nama = 'Budi';

    /**
     * Get the array of the resource.
     *
     * @return array
     */
    public function to_array()
    {
        return ['id' => $this->id, 'nama' => $this->nama];
    }

    /**
     * A method the transformer should be able to reach.
     *
     * @return string
     */
    public function sapa()
    {
        return 'halo ' . $this->nama;
    }
}

/**
 * A transformer that hands its resource straight through.
 */
class TransformerSimple extends Transformer
{
    /**
     * Shape the resource.
     *
     * @return array
     */
    public function to_array()
    {
        return (array) $this->resource;
    }
}

/**
 * A transformer with a key that comes and goes.
 */
class TransformerConditional extends Transformer
{
    public $admin = false;

    /**
     * Shape the resource.
     *
     * @return array
     */
    public function to_array()
    {
        return [
            'id' => 1,
            'email' => $this->when($this->admin, 'budi@site.com'),
        ];
    }
}

/**
 * A transformer that folds values into its own array.
 */
class TransformerMerging extends Transformer
{
    /**
     * Shape the resource.
     *
     * @return array
     */
    public function to_array()
    {
        return [
            'id' => 1,
            'a' => $this->merge_when(true, ['x' => 10, 'y' => 20]),
            'b' => $this->merge_when(false, ['z' => 30]),
            'nested' => ['k' => $this->when(false, 'hilang'), 'l' => 'ada'],
        ];
    }
}

/**
 * A transformer that carries other transformers.
 */
class TransformerNesting extends Transformer
{
    /**
     * Shape the resource.
     *
     * @return array
     */
    public function to_array()
    {
        return [
            'penulis' => TransformerSimple::make(['id' => 7, 'nama' => 'Dewi']),
            'komentar' => TransformerSimple::collection([['a' => 1], ['a' => 2]]),
            'dalam' => ['lagi' => TransformerSimple::make(['x' => 1])],
        ];
    }
}

/**
 * A transformer with a wrapper of its own.
 */
class TransformerWrapped extends Transformer
{
    public static $wrap = 'hasil';
}

/**
 * A transformer with no wrapper at all.
 */
class TransformerUnwrapped extends Transformer
{
    public static $wrap = null;
}

/**
 * A transformer that puts something beside its data.
 */
class TransformerWithMeta extends Transformer
{
    /**
     * Get the data beside the wrapped data.
     *
     * @return array
     */
    public function with()
    {
        return ['penulis' => 'rakit'];
    }
}
