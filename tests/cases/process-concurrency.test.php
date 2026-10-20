<?php

defined('DS') or exit('No direct access.');

use System\Concurrency;
use System\Process;

class ProcessConcurrencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('The commands below are POSIX shell commands.');
        }
    }

    /**
     * Output, error output, exit code, input, working directory and environment.
     *
     * @group system
     */
    public function testRun()
    {
        $result = Process::run('echo hello; echo oops >&2');
        $this->assertTrue($result->successful());
        $this->assertSame(0, $result->exit_code());
        $this->assertEquals("hello\n", $result->output());
        $this->assertEquals("oops\n", $result->error_output());

        $failed = Process::run('exit 3');
        $this->assertTrue($failed->failed());
        $this->assertSame(3, $failed->exit_code());

        $this->assertEquals('piped', Process::run('cat', ['input' => 'piped'])->output());
        $this->assertEquals(realpath(sys_get_temp_dir()), realpath(trim(Process::run('pwd', ['path' => sys_get_temp_dir()])->output())));
        $this->assertEquals("rakit\n", Process::run('echo $RAKIT_PROCESS_TEST', ['env' => ['RAKIT_PROCESS_TEST' => 'rakit']])->output());

        // Arguments are escaped: no shell expansion.
        $this->assertEquals("a b; echo injected\n", Process::run(['echo', 'a b; echo injected'])->output());

        // More output than a pipe buffer does not block.
        $this->assertEquals(200000, strlen(Process::run(['php', '-r', 'echo str_repeat("x", 200000);'])->output()));

        try {
            $failed->ensure_successful();
            $this->fail('failure ignored');
        } catch (\RuntimeException $e) {
            $this->assertContains('failed with exit code 3', $e->getMessage());
        }
    }

    /**
     * Streaming output, async start, stop and timeout.
     *
     * @group system
     */
    public function testStreamingAsyncAndTimeout()
    {
        $chunks = [];
        Process::run('echo one; echo two >&2', [], function ($type, $chunk) use (&$chunks) {
            $chunks[] = $type.':'.trim($chunk);
        });
        sort($chunks);
        $this->assertEquals(['err:two', 'out:one'], $chunks);

        $process = Process::start('sleep 5');
        $this->assertTrue($process->running());
        $this->assertNull($process->exit_code());
        $started = microtime(true);
        $process->stop();
        $this->assertFalse($process->running());
        $this->assertLessThan(3, microtime(true) - $started);

        $started = microtime(true);

        try {
            Process::run('sleep 5', ['timeout' => 1]);
            $this->fail('timeout ignored');
        } catch (\RuntimeException $e) {
            $this->assertContains('exceeded the timeout of 1 seconds', $e->getMessage());
        }

        $this->assertLessThan(4, microtime(true) - $started);
    }

    /**
     * Closures run side by side, results keep their keys, errors come back.
     *
     * @group system
     */
    public function testConcurrency()
    {
        $started = microtime(true);

        $results = Concurrency::run([
            'a' => function () {
                usleep(600000);
                return getmypid();
            },
            'b' => function () {
                usleep(600000);
                return ['x' => 1];
            },
            'c' => function () {
                usleep(600000);
                return 'c';
            },
        ]);

        $this->assertEquals(['a', 'b', 'c'], array_keys($results));
        $this->assertEquals(['x' => 1], $results['b']);
        $this->assertEquals('c', $results['c']);

        if (Concurrency::forkable()) {
            $this->assertNotEquals(getmypid(), $results['a']);
            $this->assertLessThan(1.5, microtime(true) - $started);
        }

        $this->setExpectedException('RuntimeException', 'child failed');
        Concurrency::run([
            function () {
                return 1;
            },
            function () {
                throw new \RuntimeException('child failed');
            },
        ]);
    }
}
