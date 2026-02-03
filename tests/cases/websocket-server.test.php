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
     * Set config value using reflection.
     */
    protected function setConfig(Server $server, $key, $value)
    {
        $reflection = new ReflectionProperty(Server::class, 'config');
        /** @disregard */
        $reflection->setAccessible(true);
        $config = $reflection->getValue($server);
        $config[$key] = $value;
        $reflection->setValue($server, $config);
    }

    /**
     * Call protected method using reflection.
     */
    protected function callProtectedMethod(Server $server, $method, $args = [])
    {
        $reflection = new ReflectionMethod(Server::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($server, $args);
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
        $this->assertEquals('258EAFA5-E914-47DA-95CA-C5AB0DC85B11', Server::MAGIC);
    }

    /**
     * Test konstruktor.
     *
     * @group system
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('\System\Websocket\Server', new Server('tcp://127.0.0.1:0'));
        $server = new Server('tcp://127.0.0.1:0');
        $this->assertInstanceOf('\System\Websocket\Server', $server);
        $server->shutdown();
    }

    /**
     * Test clients.
     *
     * @group system
     */
    public function testClients()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $clients = $server->clients();
        $this->assertInternalType('array', $clients);
        $this->assertEmpty($clients);
        $server->shutdown();
    }

    /**
     * Test check_origin.
     *
     * @group system
     */
    public function testCheckOrigin()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->setConfig($server, 'allowed_origins', []);
        $this->assertTrue($this->callProtectedMethod($server, 'check_origin', ['any-origin']));

        $this->setConfig($server, 'allowed_origins', ['example.com']);
        $this->assertTrue($this->callProtectedMethod($server, 'check_origin', ['example.com']));
        $this->assertFalse($this->callProtectedMethod($server, 'check_origin', ['other.com']));
        $server->shutdown();
    }

    /**
     * Test check_host.
     *
     * @group system
     */
    public function testCheckHost()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->setConfig($server, 'allowed_hosts', []);
        $this->assertTrue($this->callProtectedMethod($server, 'check_host', ['any-host']));

        $this->setConfig($server, 'allowed_hosts', ['localhost']);
        $this->assertTrue($this->callProtectedMethod($server, 'check_host', ['localhost']));
        $this->assertFalse($this->callProtectedMethod($server, 'check_host', ['other-host']));
        $server->shutdown();
    }

    /**
     * Test check_protocol.
     *
     * @group system
     */
    public function testCheckProtocol()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->setConfig($server, 'supported_protocols', []);
        $this->assertTrue($this->callProtectedMethod($server, 'check_protocol', ['any-protocol']));

        $this->setConfig($server, 'supported_protocols', ['chat']);
        $this->assertTrue($this->callProtectedMethod($server, 'check_protocol', ['chat']));
        $this->assertFalse($this->callProtectedMethod($server, 'check_protocol', ['other-protocol']));
        $this->assertTrue($this->callProtectedMethod($server, 'check_protocol', ['chat, binary']));
        $server->shutdown();
    }

    /**
     * Test check_extensions.
     *
     * @group system
     */
    public function testCheckExtensions()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->setConfig($server, 'supported_extensions', []);
        $this->assertTrue($this->callProtectedMethod($server, 'check_extensions', ['any-extension']));

        $this->setConfig($server, 'supported_extensions', ['permessage-deflate']);
        $this->assertTrue($this->callProtectedMethod($server, 'check_extensions', ['permessage-deflate']));
        $this->assertFalse($this->callProtectedMethod($server, 'check_extensions', ['other-extension']));
        $this->assertTrue($this->callProtectedMethod($server, 'check_extensions', ['permessage-deflate, other']));
        $server->shutdown();
    }

    /**
     * Test protocol.
     *
     * @group system
     */
    public function testProtocol()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->setConfig($server, 'supported_protocols', ['chat']);
        $this->assertEquals("Sec-WebSocket-Protocol: chat\r\n", $this->callProtectedMethod($server, 'protocol', ['chat']));
        $this->assertEquals('', $this->callProtectedMethod($server, 'protocol', ['unsupported']));
        $this->assertEquals("Sec-WebSocket-Protocol: chat\r\n", $this->callProtectedMethod($server, 'protocol', ['chat, binary']));
        $server->shutdown();
    }

    /**
     * Test extensions.
     *
     * @group system
     */
    public function testExtensions()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->setConfig($server, 'supported_extensions', ['permessage-deflate']);
        $this->assertEquals(
            "Sec-WebSocket-Extensions: permessage-deflate\r\n",
            $this->callProtectedMethod($server, 'extensions', ['permessage-deflate'])
        );
        $this->assertEquals('', $this->callProtectedMethod($server, 'extensions', ['unsupported']));
        $this->assertEquals(
            "Sec-WebSocket-Extensions: permessage-deflate\r\n",
            $this->callProtectedMethod($server, 'extensions', ['permessage-deflate, other'])
        );
        $server->shutdown();
    }
}
