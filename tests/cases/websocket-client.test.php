<?php

defined('DS') or exit('No direct access.');

use System\Websocket\Server;
use System\Websocket\Client;

class WebsocketClientTest extends \PHPUnit_Framework_TestCase
{
    protected $server;
    protected $socket;
    protected $client;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->server = new Server('tcp://127.0.0.1:8080');
        $this->socket = fopen('php://temp', 'r+');
        $this->client = new Client($this->server, $this->socket, 'GET', '/ws', []);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    /**
     * Test konstruktor.
     *
     * @group system
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertSame($this->server, $this->client->server());
        $this->assertSame($this->socket, $this->client->socket());
        $this->assertEquals('GET', $this->client->method());
        $this->assertEquals('/ws', $this->client->uri());
        $this->assertInternalType('array', $this->client->headers());
        $this->assertInternalType('int', $this->client->last_activity());
    }

    /**
     * Test getters.
     *
     * @group system
     */
    public function testGetters()
    {
        $this->assertEquals(stream_socket_get_name($this->socket, true), $this->client->id());
        $this->assertEquals('GET', $this->client->method());
        $this->assertEquals('/ws', $this->client->uri());
        $this->assertInternalType('array', $this->client->headers());
        $this->assertInternalType('int', $this->client->last_activity());
    }

    /**
     * Test send text.
     *
     * @group system
     */
    public function testSendText()
    {
        $mock = $this->getMockBuilder(Server::class)
            ->setConstructorArgs(['tcp://127.0.0.1:8080'])
            ->setMethods(['write'])
            ->getMock();
        $mock->expects($this->once())->method('write')->willReturn(5);

        $client = new Client($mock, $this->socket, 'GET', '/ws', []);
        $result = $client->send(Server::TEXT, 'Hello');
        $this->assertEquals('Hello', $result);
    }

    /**
     * Test send close.
     *
     * @group system
     */
    public function testSendClose()
    {
        $mock = $this->getMockBuilder(Server::class)
            ->setConstructorArgs(['tcp://127.0.0.1:8080'])
            ->setMethods(['write'])
            ->getMock();
        $mock->expects($this->once())->method('write')->willReturn(0);

        $client = new Client($mock, $this->socket, 'GET', '/ws', []);
        $result = $client->send(Server::CLOSE);
        $this->assertSame('', $result);
    }
}
