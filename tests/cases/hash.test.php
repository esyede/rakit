<?php

defined('DS') or exit('No direct access.');

use System\Hash;

class HashTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test untuk method Hash::make().
     *
     * @group system
     */
    public function testHashProducesValidBcryptHash()
    {
        $this->assertTrue(mb_strlen(Hash::make('foo'), '8bit') === 60);
    }

    /**
     * Test untuk method Hash::check().
     *
     * @group system
     */
    public function testHashCheckMethod()
    {
        $hash = Hash::make('foo');

        $this->assertTrue(Hash::check('foo', $hash));
        $this->assertFalse(Hash::check('bar', $hash));
    }

    /**
     * Test untuk method Hash::weak().
     *
     * @group system
     */
    public function testHashWeakBasedOnCost($value = '')
    {
        $hash = Hash::make('foo');

        $this->assertFalse(Hash::weak($hash, 10));
        $this->assertTrue(Hash::weak($hash, 11));
    }
}
