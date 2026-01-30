<?php

defined('DS') or exit('No direct access.');

use System\Websocket\Server;

class WebsocketServerTest extends \PHPUnit_Framework_TestCase
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

    /**
     * Test konstanta.
     *
     * @group system
     */
    public function testConstants()
    {
        $this->assertEquals(1, Server::TEXT);
        $this->assertEquals(2, Server::BINARY);
        $this->assertEquals(8, Server::CLOSE);
        $this->assertEquals(9, Server::PING);
        $this->assertEquals(10, Server::PONG);
        $this->assertEquals(15, Server::OPCODE);
        $this->assertEquals(128, Server::FINALE);
        $this->assertEquals(127, Server::LENGTH);
        $this->assertEquals(65536, Server::PACKET);
        $this->assertEquals('258EAFA5-E914-47DA-95CA-C5AB0DC85B11', Server::MAGIC);
    }

    /**
     * Test konstruktor.
     *
     * @group system
     */
    public function testConstructor()
    {
        $server = new Server('tcp://127.0.0.1:8080');
        $this->assertInstanceOf(Server::class, $server);
    }

    /**
     * Test clients.
     *
     * @group system
     */
    public function testClients()
    {
        $server = new Server('tcp://127.0.0.1:8080');
        $clients = $server->clients();
        $this->assertInternalType('array', $clients);
        $this->assertEmpty($clients);
    }
}