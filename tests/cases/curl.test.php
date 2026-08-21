<?php

defined('DS') or exit('No direct access.');

use System\Curl;

class CurlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Base URL of the endpoint that echoes requests back as JSON.
     *
     * @var string
     */
    private static $base;

    /**
     * @var bool|null
     */
    private static $skipNetworkTests;

    /**
     * @var resource|null
     */
    private static $process;

    /**
     * Bring up the mock endpoint. These tests used to fire ~30 live requests
     * at https://rakit.esyede.my.id/mock on every run, including one that asks
     * the remote to stall for 1000 seconds — enough to keep a worker pinned
     * long after the client gave up, which is a good way to take a small
     * shared host offline. They now run against a local PHP built-in server
     * serving tests/mock/server.php, so the suite is offline-capable, fast,
     * and harmless to the production host.
     *
     * Set RAKIT_CURL_MOCK_URL to point the suite at a different endpoint (for
     * example the real one) when you specifically want to test against it.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $override = getenv('RAKIT_CURL_MOCK_URL');

        if (is_string($override) && '' !== $override) {
            self::$base = rtrim($override, '/');
            self::$skipNetworkTests = !self::reachable(self::$base);

            return;
        }

        $port = self::freePort();
        self::$base = 'http://127.0.0.1:' . $port . '/mock';

        $command = escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $port . ' '
            . escapeshellarg(dirname(__DIR__) . DS . 'mock' . DS . 'server.php');

        $pipes = [];
        $options = null;

        if (DIRECTORY_SEPARATOR === '\\') {
            // NUL is Windows' /dev/null. Pipes are not an option: nothing here
            // reads them, and `php -S` logs a line per request to stderr, so
            // the buffer would fill and the server would block mid-run.
            $descriptors = [
                0 => ['file', 'NUL', 'r'],
                1 => ['file', 'NUL', 'w'],
                2 => ['file', 'NUL', 'w'],
            ];

            // Without this, proc_open wraps the command in `cmd.exe /c`, so the
            // pid we hold belongs to cmd; terminating it would leave the server
            // running, and Windows has no posix_kill to fall back on.
            $options = ['bypass_shell' => true];
        } else {
            $descriptors = [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ];

            // Same problem, different shell: proc_open runs the command through
            // `sh -c`, so proc_get_status() would report the shell's pid. `exec`
            // makes the shell replace itself with PHP, so the pid is the
            // server's.
            $command = 'exec ' . $command;
        }

        $process = @proc_open($command, $descriptors, $pipes, null, null, $options);
        self::$process = is_resource($process) ? $process : null;

        // The server needs a moment before it accepts connections.
        for ($i = 0; $i < 50; $i++) {
            if (self::reachable(self::$base)) {
                self::$skipNetworkTests = false;

                return;
            }

            usleep(100000);
        }

        self::$skipNetworkTests = true;
    }

    /**
     * @return void
     */
    public static function tearDownAfterClass()
    {
        if (is_resource(self::$process)) {
            @proc_terminate(self::$process);

            $status = proc_get_status(self::$process);

            // proc_terminate does not wait, and on some platforms it does not
            // land at all; follow up with a signal before giving up the handle
            // so the server never outlives the test run.
            if (!empty($status['running']) && function_exists('posix_kill')) {
                @posix_kill($status['pid'], defined('SIGKILL') ? SIGKILL : 9);
            }

            @proc_close(self::$process);
            self::$process = null;
        }
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private static function reachable($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        @curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
            curl_close($ch);
        }

        return 200 === (int) $code;
    }

    /**
     * Ask the OS for an unused port by binding to port 0 and reading back
     * what it handed out, so parallel runs do not collide on a fixed port.
     *
     * @return int
     */
    private static function freePort()
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if (!$socket) {
            return 8910;
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        $port = (int) substr($name, strrpos($name, ':') + 1);

        return $port > 0 ? $port : 8910;
    }

    public function setUp()
    {
        Curl::timeout(240);
    }

    public function tearDown()
    {
        Curl::timeout(null);
    }

    private function skipIfNoNetwork()
    {
        if (self::$skipNetworkTests) {
            $this->markTestSkipped('Mock endpoint tidak tersedia di ' . self::$base);
        }
    }

    public function testCurlExists()
    {
        $this->assertTrue(extension_loaded('curl'));
    }

    public function testCurlOptions()
    {
        $this->skipIfNoNetwork();

        Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
        $response = Curl::get(self::$base);

        if (!is_object($response->body) || !isset($response->body->headers)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals($response->body->headers->Cookie, 'foo=bar');
        Curl::clear_curl_options();
    }

    public function testTimeoutFail()
    {
        // Without this the test does not skip along with the rest when the
        // endpoint is unreachable: it would still fire a request, get
        // "connection refused" instead of a timeout, and fail. That turns any
        // environment where the mock server cannot start into a red build
        // rather than a skipped one.
        $this->skipIfNoNetwork();

        $caught = null;

        try {
            Curl::timeout(1);
            Curl::get(self::$base . '/1000');
        } catch (\Throwable $e) {
            $caught = $e;
        } catch (\Exception $e) {
            $caught = $e;
        }

        // Kept outside the try: the call above is meant to throw, so leaving
        // this after it would never reset the timeout.
        Curl::timeout(null);

        $this->assertNotNull($caught, 'Expected the request to time out.');
        $this->assertTrue(false !== strpos(strtolower($caught->getMessage()), 'timed out'));
    }

    public function testDefaultHeaders()
    {
        $this->skipIfNoNetwork();

        Curl::default_headers(['header1' => 'Hello', 'header2' => 'world']);

        $response = Curl::get(self::$base);
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('Hello', $response->body->headers->Header1);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('world', $response->body->headers->Header2);

        $response = Curl::get(self::$base, ['header1' => 'Custom value']);
        $this->assertEquals(200, $response->code);
        $this->assertObjectHasAttribute('Header1', $response->body->headers);
        $this->assertEquals('Custom value', $response->body->headers->Header1);

        Curl::clear_default_headers();

        $response = Curl::get(self::$base);
        $this->assertEquals(200, $response->code);
        $this->assertObjectNotHasAttribute('Header1', $response->body->headers);
        $this->assertObjectNotHasAttribute('Header2', $response->body->headers);
    }

    public function testDefaultHeader()
    {
        $this->skipIfNoNetwork();

        Curl::default_header('Hello', 'custom');

        $response = Curl::get(self::$base);
        $this->assertEquals(200, $response->code);
        $this->assertTrue(property_exists($response->body->headers, 'Hello'));
        $this->assertEquals('custom', $response->body->headers->Hello);

        Curl::clear_default_headers();

        $response = Curl::get(self::$base);

        $this->assertEquals(200, $response->code);
        $this->assertFalse(property_exists($response->body->headers, 'hello'));
    }

    public function testBasicAuthentication()
    {
        $this->skipIfNoNetwork();

        Curl::auth('user', 'password');
        $response = Curl::get(self::$base);

        if (!is_object($response->body) || !isset($response->body->headers)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals('Basic dXNlcjpwYXNzd29yZA==', $response->body->headers->Authorization);
    }

    public function testCustomHeaders()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, ['user-agent' => 'dummy-agent']);
        $this->assertEquals(200, $response->code);
        $this->assertEquals('dummy-agent', $response->body->headers->{'User-Agent'});
    }

    public function testGet()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base . '?name=Budi', [
            'Accept' => 'application/json',
        ], ['age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetMultidimensionalArray()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['key' => 'value', 'items' => ['item1', 'item2']]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('value', $response->body->queries->key);
        $this->assertEquals('item1', $response->body->queries->items[0]);
        $this->assertEquals('item2', $response->body->queries->items[1]);
    }

    public function testGetWithDots()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->user_name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetWithDotsAlt()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi Purnomo', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->queries->user_name);
        $this->assertEquals(28, $response->body->queries->age);
    }

    public function testGetWithEqualSign()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->queries->name);
    }

    public function testGetWithEqualSignAlt()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi=Hello=Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi=Hello=Dewi', $response->body->queries->name);
    }

    public function testGetWithComplexQuery()
    {
        $this->skipIfNoNetwork();

        $query = '[{"type":"/music/album","name":null,"artist":{"id":"/id/denny_caknan"},"limit":3}]';
        $response = Curl::get(self::$base . '?query=' . $query . '&cursor');

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('', $response->body->queries->cursor);
        $this->assertEquals($query, $response->body->queries->query);
    }

    public function testGetArray()
    {
        $this->skipIfNoNetwork();

        $response = Curl::get(self::$base, [], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('GET', $response->body->method);
        $this->assertEquals('Budi', $response->body->queries->name[0]);
        $this->assertEquals('Dewi', $response->body->queries->name[1]);
    }

    public function testHead()
    {
        $this->skipIfNoNetwork();

        $response = Curl::head(self::$base . '?name=Budi', ['Accept' => 'application/json']);
        $this->assertEquals(200, $response->code);
    }

    public function testPost()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostForm()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_form(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        if (!is_object($response->body) || !isset($response->body->method)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('application/x-www-form-urlencoded', $response->body->headers->{'Content-Type'});
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostMultipart()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_multipart(['name' => 'Budi', 'age' => 28]);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        if (!is_object($response->body) || !isset($response->body->method)) {
            $this->markTestSkipped('Response format tidak sesuai');
        }

        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('multipart/form-data', explode(';', $response->body->headers->{'Content-Type'})[0]);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testPostWithEqualSign()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_form(['name' => 'Budi=Hello']);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi=Hello', $response->body->data->name);
    }

    public function testPostArray()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], ['name[0]' => 'Budi', 'name[1]' => 'Dewi']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name[0]);
        $this->assertEquals('Dewi', $response->body->data->name[1]);
    }

    public function testPostWithDots()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], ['user.name' => 'Budi', 'age' => 28]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->user_name);
        $this->assertEquals(28, $response->body->data->age);
    }

    public function testRawPost()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], json_encode(['author' => 'Budi Purnomo']));

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi Purnomo', $response->body->data->json->author);
    }

    public function testPostMultidimensionalArray()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_form(['key' => 'value', 'items' => ['item1', 'item2']]);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('value', $response->body->data->key);
        $this->assertEquals('item1', $response->body->data->items[0]);
        $this->assertEquals('item2', $response->body->data->items[1]);
    }

    public function testPut()
    {
        $this->skipIfNoNetwork();

        $response = Curl::put(self::$base, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], ['name' => 'Budi', 'gender' => 'Male']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PUT', $response->body->method);
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Budi'));
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Male'));
    }

    public function testPatch()
    {
        $this->skipIfNoNetwork();

        $response = Curl::patch(self::$base, [
            'Accept' => 'application/json',
        ], ['name' => 'Budi', 'gender' => 'Male']);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('PATCH', $response->body->method);
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Budi'));
        $this->assertTrue(false !== strpos($response->body->data->stdin, 'Male'));
    }

    public function testDelete()
    {
        $this->skipIfNoNetwork();

        $response = Curl::delete(self::$base);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('DELETE', $response->body->method);
    }

    public function testUpload()
    {
        $this->skipIfNoNetwork();

        $body = Curl::body_multipart(['name' => 'Budi'], ['file' => __DIR__ . DS . 'index.html']);
        $response = Curl::post(self::$base, ['Accept' => 'application/json'], $body);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->file->size > 0);
    }

    public function testUploadWithoutHelper()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], [
            'name' => 'Budi',
            'file' => Curl::body_file(__DIR__ . DS . 'index.html'),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->file->size > 0);
    }

    public function testUploadIfFilePartOfData()
    {
        $this->skipIfNoNetwork();

        $response = Curl::post(self::$base, [
            'Accept' => 'application/json',
        ], [
            'name' => 'Budi',
            'files[owl.gif]' => Curl::body_file(__DIR__ . DS . 'index.html'),
        ]);

        $this->assertEquals(200, $response->code);
        $this->assertEquals('POST', $response->body->method);
        $this->assertEquals('Budi', $response->body->data->name);
        $this->assertTrue($response->body->data->files->size->{'owl.gif'} > 0);
    }
}
