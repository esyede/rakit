<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Provider\Uuid as BaseUuid;

class FakerEnUuidTest extends \PHPUnit_Framework_TestCase
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

    public function testUuidReturnsUuid()
    {
        $this->assertTrue($this->isUuid(BaseUuid::uuid()));
    }

    protected function isUuid($uuid)
    {
        return is_string($uuid) && (bool) preg_match('/^[a-f0-9]{8,8}-(?:[a-f0-9]{4,4}-){3,3}[a-f0-9]{12,12}$/i', $uuid);
    }
}
