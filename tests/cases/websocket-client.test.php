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
        $this->server = new Server('tcp://127.0.0.1:0');
        $this->socket = fopen('php://temp', 'r+');
        $this->client = new Client('test_id', $this->socket);
        $this->client->of($this->server);
        $this->client->uri = '/ws';
        $this->client->headers = [];
        $this->client->user = null; // Initialize user property
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (isset($this->server)) {
            $this->server->shutdown();
        }
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
        $this->assertInstanceOf('\System\Websocket\Client', $this->client);
        $this->assertSame($this->server, $this->client->server());
        $this->assertSame($this->socket, $this->client->socket());
        $this->assertEquals('test_id', $this->client->id());
        $this->assertEquals('/ws', $this->client->uri());
        $this->assertInternalType('array', $this->client->headers());
        $this->assertInternalType('int', $this->client->last_activity());
        $this->assertNull($this->client->user); // Test user property
    }

    /**
     * Test getters.
     *
     * @group system
     */
    public function testGetters()
    {
        $this->assertEquals('test_id', $this->client->id());
        $this->assertEquals('GET', $this->client->method());
        $this->assertEquals('/ws', $this->client->uri());
        $this->assertInternalType('array', $this->client->headers());
        $this->assertInternalType('int', $this->client->last_activity());
    }

    /**
     * Test user property.
     *
     * @group system
     */
    public function testUserProperty()
    {
        $this->assertNull($this->client->user);
        $mockUser = (object) ['name' => 'John', 'email' => 'john@example.com'];
        $this->client->user = $mockUser;
        $this->assertEquals($mockUser, $this->client->user);
    }

    /**
     * Test send text.
     *
     * @group system
     */
    public function testSendText()
    {
        $mock = $this->getMockBuilder('\System\Websocket\Server')
            ->setConstructorArgs(['tcp://127.0.0.1:0'])
            ->setMethods(['frame'])
            ->getMock();
        $mock->expects($this->once())->method('frame')->with('Hello', $this->anything(), 'text')->willReturn('framed_message');

        $client = new Client('test_id', $this->socket);
        $client->of($mock);
        $client->send(Server::TEXT, 'Hello');
    }

    /**
     * Test send close.
     *
     * @group system
     */
    public function testSendClose()
    {
        $mock = $this->getMockBuilder('\System\Websocket\Server')
            ->setConstructorArgs(['tcp://127.0.0.1:0'])
            ->setMethods(['frame'])
            ->getMock();
        $mock->expects($this->once())->method('frame')->with('', $this->anything(), 'close')->willReturn('framed_close');

        $client = new Client('test_id', $this->socket);
        $client->of($mock);
        $client->send(Server::CLOSE);
    }

    /**
     * Test send binary.
     *
     * @group system
     */
    public function testSendBinary()
    {
        $mock = $this->getMockBuilder('\System\Websocket\Server')
            ->setConstructorArgs(['tcp://127.0.0.1:0'])
            ->setMethods(['frame'])
            ->getMock();
        $mock->expects($this->once())->method('frame')->with('data', $this->anything(), 'binary')->willReturn('framed_binary');

        $client = new Client('test_id', $this->socket);
        $client->of($mock);
        $client->send(Server::BINARY, 'data');
    }

    /**
     * Test send ping.
     *
     * @group system
     */
    public function testSendPing()
    {
        $mock = $this->getMockBuilder('\System\Websocket\Server')
            ->setConstructorArgs(['tcp://127.0.0.1:0'])
            ->setMethods(['frame'])
            ->getMock();
        $mock->expects($this->once())->method('frame')->with('ping', $this->anything(), 'ping')->willReturn('framed_ping');

        $client = new Client('test_id', $this->socket);
        $client->of($mock);
        $client->send(Server::PING, 'ping');
    }

    /**
     * Test send pong.
     *
     * @group system
     */
    public function testSendPong()
    {
        $mock = $this->getMockBuilder('\System\Websocket\Server')
            ->setConstructorArgs(['tcp://127.0.0.1:0'])
            ->setMethods(['frame'])
            ->getMock();
        $mock->expects($this->once())->method('frame')->with('pong', $this->anything(), 'pong')->willReturn('framed_pong');

        $client = new Client('test_id', $this->socket);
        $client->of($mock);
        $client->send(Server::PONG, 'pong');
    }
}
