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
        $reflection = new ReflectionProperty('\System\Websocket\Server', 'config');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $reflection->setAccessible(true);
        $config = $reflection->getValue($server);
        $config[$key] = $value;
        $reflection->setValue($server, $config);
    }

    /**
     * Call protected method using reflection.
     */
    protected function callProtectedMethod(Server $server, $method, $args = [])
    {
        $reflection = new ReflectionMethod('\System\Websocket\Server', $method);
        /** @disregard */
        PHP_VERSION_ID < 80100 && $reflection->setAccessible(true);
        return $reflection->invokeArgs($server, $args);
    }

    /**
     * Test constants.
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
     * Test constructor.
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
            'Sec-WebSocket-Extensions: permessage-deflate' . CRLF,
            $this->callProtectedMethod($server, 'extensions', ['permessage-deflate'])
        );
        $this->assertEquals('', $this->callProtectedMethod($server, 'extensions', ['unsupported']));
        $this->assertEquals(
            'Sec-WebSocket-Extensions: permessage-deflate' . CRLF,
            $this->callProtectedMethod($server, 'extensions', ['permessage-deflate, other'])
        );
        $server->shutdown();
    }

    // -------------------------------------------------------------------------
    // Frame protocol
    // -------------------------------------------------------------------------

    /**
     * Build a user object the frame helpers can work with.
     *
     * @return object
     */
    protected function user()
    {
        $user = new \stdClass();
        $user->continuous = false;
        $user->disconnecting = false;
        $user->busy = false;
        $user->buffer = '';
        $user->message = '';
        $user->socket = null;

        return $user;
    }

    /**
     * Build a client frame the way a browser would: masked, FIN set.
     *
     * @param string $payload
     * @param int    $opcode
     *
     * @return string
     */
    protected function client_frame($payload, $opcode = 1)
    {
        $mask = 'abcd';
        $length = strlen($payload);
        $frame = chr(128 | $opcode);

        if ($length < 126) {
            $frame .= chr(128 | $length);
        } elseif ($length < 65536) {
            $frame .= chr(128 | 126) . chr(($length >> 8) & 255) . chr($length & 255);
        } else {
            $frame .= chr(128 | 127) . str_pad(pack('J', $length), 8, "\0", STR_PAD_LEFT);
        }

        $frame .= $mask;
        $masked = '';

        for ($i = 0; $i < $length; $i++) {
            $masked .= $payload[$i] ^ $mask[$i % 4];
        }

        return $frame . $masked;
    }

    /**
     * A short text frame round-trips through frame().
     *
     * @group system
     */
    public function testFrameBuildsAShortTextFrame()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $frame = $this->callProtectedMethod($server, 'frame', ['halo', $this->user()]);

        $this->assertEquals(129, ord($frame[0]));  // FIN + opcode 1
        $this->assertEquals(4, ord($frame[1]));    // unmasked, length 4
        $this->assertEquals('halo', substr($frame, 2));
    }

    /**
     * A medium payload switches to the 2 byte length field.
     *
     * @group system
     */
    public function testFrameUsesExtendedLengthField()
    {
        $server = new Server('tcp://127.0.0.1:0');

        $payload = str_repeat('a', 200);
        $frame = $this->callProtectedMethod($server, 'frame', [$payload, $this->user()]);

        $this->assertEquals(126, ord($frame[1]));
        $this->assertEquals(200, ord($frame[2]) * 256 + ord($frame[3]));
        $this->assertEquals($payload, substr($frame, 4));

        $payload = str_repeat('a', 70000);
        $frame = $this->callProtectedMethod($server, 'frame', [$payload, $this->user()]);

        $this->assertEquals(127, ord($frame[1]));
        $this->assertEquals($payload, substr($frame, 10));
    }

    /**
     * Every control frame type gets its own opcode.
     *
     * @group system
     */
    public function testFrameOpcodes()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $opcodes = ['text' => 1, 'binary' => 2, 'close' => 8, 'ping' => 9, 'pong' => 10];

        foreach ($opcodes as $type => $opcode) {
            $frame = $this->callProtectedMethod($server, 'frame', ['x', $this->user(), $type]);
            $this->assertEquals(128 | $opcode, ord($frame[0]), $type);
        }
    }

    /**
     * An unknown frame type is refused instead of silently producing opcode 0.
     *
     * @group system
     *
     * @expectedException InvalidArgumentException
     */
    public function testFrameRejectsUnknownType()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $this->callProtectedMethod($server, 'frame', ['x', $this->user(), 'nonsense']);
    }

    /**
     * A masked client frame is unmasked back to the original payload.
     *
     * @group system
     */
    public function testDeframeUnmasksClientPayload()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $user = $this->user();

        $result = $this->callProtectedMethod(
            $server,
            'deframe',
            [$this->client_frame('halo dunia'), &$user]
        );

        $this->assertEquals('halo dunia', $result);
    }

    /**
     * The same for a payload long enough to need the extended length field.
     *
     * @group system
     */
    public function testDeframeUnmasksLongPayload()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $user = $this->user();
        $payload = str_repeat('rakit', 100);

        $result = $this->callProtectedMethod(
            $server,
            'deframe',
            [$this->client_frame($payload), &$user]
        );

        $this->assertEquals($payload, $result);
    }

    /**
     * A close frame marks the connection as closing.
     *
     * @group system
     */
    public function testDeframeHandlesCloseFrame()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $user = $this->user();

        $result = $this->callProtectedMethod($server, 'deframe', [$this->client_frame('', 8), &$user]);

        $this->assertEquals('', $result);
        $this->assertTrue($user->disconnecting);
    }

    /**
     * A pong is a control frame, it must never surface as application data.
     *
     * @group system
     */
    public function testDeframeIgnoresPongFrame()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $user = $this->user();

        $result = $this->callProtectedMethod($server, 'deframe', [$this->client_frame('halo', 10), &$user]);

        $this->assertFalse($result);
        $this->assertFalse($user->disconnecting);
    }

    /**
     * Test for Server::extract_headers().
     *
     * @group system
     */
    public function testExtractHeaders()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $headers = $this->callProtectedMethod($server, 'extract_headers', [$this->client_frame('halo')]);

        $this->assertEquals(128, $headers['fin']);
        $this->assertEquals(1, $headers['opcode']);
        $this->assertEquals(128, $headers['hasmask']);
        $this->assertEquals(4, $headers['length']);
        $this->assertEquals('abcd', $headers['mask']);
    }

    /**
     * Test for Server::calc_offset() for each length class.
     *
     * @group system
     */
    public function testCalcOffset()
    {
        $server = new Server('tcp://127.0.0.1:0');

        $offset = $this->callProtectedMethod($server, 'calc_offset', [['hasmask' => 128, 'length' => 4]]);
        $this->assertEquals(6, $offset);

        $offset = $this->callProtectedMethod($server, 'calc_offset', [['hasmask' => 0, 'length' => 4]]);
        $this->assertEquals(2, $offset);

        $offset = $this->callProtectedMethod($server, 'calc_offset', [['hasmask' => 128, 'length' => 200]]);
        $this->assertEquals(8, $offset);

        $offset = $this->callProtectedMethod($server, 'calc_offset', [['hasmask' => 128, 'length' => 70000]]);
        $this->assertEquals(14, $offset);
    }

    /**
     * Test for Server::apply_mask() - masking is its own inverse.
     *
     * @group system
     */
    public function testApplyMaskIsItsOwnInverse()
    {
        $server = new Server('tcp://127.0.0.1:0');
        $headers = ['hasmask' => 128, 'mask' => 'abcd'];
        $payload = 'halo dunia yang panjang sekali';

        $masked = $this->callProtectedMethod($server, 'apply_mask', [$headers, $payload]);
        $this->assertNotEquals($payload, $masked);

        $unmasked = $this->callProtectedMethod($server, 'apply_mask', [$headers, $masked]);
        $this->assertEquals($payload, $unmasked);
    }

    /**
     * Without a mask the payload is returned untouched.
     *
     * @group system
     */
    public function testApplyMaskWithoutMask()
    {
        $server = new Server('tcp://127.0.0.1:0');

        $this->assertEquals(
            'halo',
            $this->callProtectedMethod($server, 'apply_mask', [['hasmask' => 0, 'mask' => ''], 'halo'])
        );
    }
}
