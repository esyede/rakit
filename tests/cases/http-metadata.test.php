<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Metadata;

class HttpMetadataTest extends \PHPUnit_Framework_TestCase
{
    protected $bag;
    protected $array = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->bag = new Metadata();
        $this->array = [Metadata::CREATED => 1234567, Metadata::UPDATED => 12345678, Metadata::LIFETIME => 0];
        $this->bag->initialize($this->array);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->array = [];
        $this->bag = null;
    }

    public function testInitialize()
    {
        $p = new \ReflectionProperty('\System\Foundation\Http\Metadata', 'meta');
        /** @disregard */
        $p->setAccessible(true);

        $bag1 = new Metadata();
        $array = [];
        $bag1->initialize($array);
        $this->assertGreaterThanOrEqual(time(), $bag1->getCreated());
        $this->assertEquals($bag1->getCreated(), $bag1->getLastUsed());

        sleep(1);
        $bag2 = new Metadata();
        $array2 = $p->getValue($bag1);
        $bag2->initialize($array2);
        $this->assertEquals($bag1->getCreated(), $bag2->getCreated());
        $this->assertEquals($bag1->getLastUsed(), $bag2->getLastUsed());
        $this->assertEquals($bag2->getCreated(), $bag2->getLastUsed());

        sleep(1);
        $bag3 = new Metadata();
        $array3 = $p->getValue($bag2);
        $bag3->initialize($array3);
        $this->assertEquals($bag1->getCreated(), $bag3->getCreated());
        $this->assertGreaterThan($bag2->getLastUsed(), $bag3->getLastUsed());
        $this->assertNotEquals($bag3->getCreated(), $bag3->getLastUsed());
    }

    public function testGetSetName()
    {
        $this->assertEquals('__metadata', $this->bag->getName());
        $this->bag->setName('foo');
        $this->assertEquals('foo', $this->bag->getName());

    }

    public function testGetStorageKey()
    {
        $this->assertEquals('_rakit_meta', $this->bag->getStorageKey());
    }

    public function testGetCreated()
    {
        $this->assertEquals(1234567, $this->bag->getCreated());
    }

    public function testGetLastUsed()
    {
        $this->assertLessThanOrEqual(time(), $this->bag->getLastUsed());
    }

    public function testClear()
    {
        $this->bag->clear();
    }
}
