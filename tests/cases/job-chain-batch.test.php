<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Hook;
use System\Job;
use System\Job\Batch;

class JobChainBatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * What the test jobs saw: [name, payload, batch id].
     *
     * @var array
     */
    public static $log = [];

    /**
     * Framework state replaced by the tests.
     *
     * @var array
     */
    protected $backup = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->backup = ['job' => Config::get('job'), 'cache' => Config::get('cache.driver')];

        Config::set('job.driver', 'file');
        Config::set('job.max_job', 50);
        Config::set('job.max_retries', 1);
        Config::set('job.sleep_ms', 0);
        Config::set('job.logging', false);
        Config::set('cache.driver', 'file');
        Job::$drivers = [];
        static::$log = [];
        $this->clean();

        // Other tests clear the job hooks: register them again.
        Hook::clear('rakit.jobs.process');
        $discovered = new \ReflectionProperty('System\Job', 'discovered');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $discovered->setAccessible(true);
        $discovered->setValue(null, false);

        foreach (['step-a', 'step-b', 'step-c', 'ok', 'then', 'failure', 'finish'] as $name) {
            Hook::clear('rakit.jobs.run: chainbatch-'.$name);
            Hook::listen('rakit.jobs.run: chainbatch-'.$name, function ($payload) use ($name) {
                JobChainBatchTest::$log[] = [$name, $payload, Batch::$current];
            });
        }

        Hook::clear('rakit.jobs.run: chainbatch-boom');
        Hook::listen('rakit.jobs.run: chainbatch-boom', function () {
            JobChainBatchTest::$log[] = ['boom', null, Batch::$current];
            throw new \RuntimeException('boom');
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->clean();
        Config::set('job', $this->backup['job']);
        Config::set('cache.driver', $this->backup['cache']);
        Job::$drivers = [];
    }

    /**
     * Remove the queued and failed job files.
     */
    protected function clean()
    {
        foreach ((array) glob(path('storage').'jobs'.DS.'*.php') as $file) {
            @unlink($file);
        }
    }

    /**
     * Run the worker until the queue is empty.
     */
    protected function work()
    {
        for ($i = 0; $i < 10; ++$i) {
            $queued = array_filter((array) glob(path('storage').'jobs'.DS.'*.job.php'), function ($file) {
                return 0 !== strpos(basename($file), 'failed__');
            });

            if (empty($queued)) {
                return;
            }

            Job::runall();
        }

        $this->fail('The queue did not empty.');
    }

    /**
     * Names the jobs ran in.
     *
     * @return array
     */
    protected function ran()
    {
        return array_map(function ($entry) {
            return $entry[0];
        }, static::$log);
    }

    /**
     * A chain runs its jobs in order, each with its own payload.
     *
     * @group system
     */
    public function testChainRunsInOrder()
    {
        Job::chain([
            Job::dispatch('chainbatch-step-a', ['n' => 1]),
            Job::dispatch('chainbatch-step-b', ['n' => 2]),
            Job::dispatch('chainbatch-step-c', ['n' => 3]),
        ]);

        $this->work();

        $this->assertEquals(['step-a', 'step-b', 'step-c'], $this->ran());
        $this->assertEquals(['n' => 1], static::$log[0][1]); // no bookkeeping keys
        $this->assertEquals(['n' => 3], static::$log[2][1]);
    }

    /**
     * A job failing for good stops the chain.
     *
     * @group system
     */
    public function testChainStopsOnFailure()
    {
        Job::chain([
            Job::dispatch('chainbatch-step-a'),
            Job::dispatch('chainbatch-boom'),
            Job::dispatch('chainbatch-step-c'),
        ]);

        $this->work();

        $this->assertEquals(['step-a', 'boom'], $this->ran());
    }

    /**
     * A batch that succeeds queues then and finish once, and its jobs know their batch.
     *
     * @group system
     */
    public function testBatchSucceeds()
    {
        $batch = Job::batch([
            Job::dispatch('chainbatch-ok', ['n' => 1]),
            Job::dispatch('chainbatch-ok', ['n' => 2]),
            Job::dispatch('chainbatch-ok', ['n' => 3]),
        ])->name('import')
            ->then(Job::dispatch('chainbatch-then'))
            ->on_failure(Job::dispatch('chainbatch-failure'))
            ->on_finish(Job::dispatch('chainbatch-finish'))
            ->dispatch();

        $this->assertEquals(3, $batch->total_jobs);
        $this->assertEquals(0, $batch->progress());

        $this->work();

        $ran = $this->ran();
        sort($ran);
        $this->assertEquals(['finish', 'ok', 'ok', 'ok', 'then'], $ran);

        foreach (static::$log as $entry) {
            $this->assertEquals($batch->id, $entry[2]);
        }

        $batch = Job::find_batch($batch->id);
        $this->assertEquals('import', $batch->name);
        $this->assertTrue($batch->finished());
        $this->assertFalse($batch->has_failures());
        $this->assertEquals(100, $batch->progress());
        $this->assertNull(Batch::$current);

        $batch->delete();
        $this->assertNull(Job::find_batch($batch->id));
    }

    /**
     * A failure cancels the batch: failure and finish run once, then never, the rest is dropped.
     *
     * @group system
     */
    public function testBatchFailureCancels()
    {
        $batch = Job::batch([
            Job::dispatch('chainbatch-boom'),
            Job::dispatch('chainbatch-ok'),
            Job::dispatch('chainbatch-ok'),
        ])->then(Job::dispatch('chainbatch-then'))
            ->on_failure(Job::dispatch('chainbatch-failure'))
            ->on_finish(Job::dispatch('chainbatch-finish'))
            ->dispatch();

        $this->work();

        $ran = array_count_values($this->ran());
        $this->assertEquals(1, $ran['boom']);
        $this->assertEquals(1, $ran['failure']);
        $this->assertEquals(1, $ran['finish']);
        $this->assertArrayNotHasKey('then', $ran);

        $batch = Job::find_batch($batch->id);
        $this->assertTrue($batch->cancelled());
        $this->assertTrue($batch->finished());
        $this->assertEquals(1, $batch->failed_jobs);
        $this->assertEquals(0, $batch->pending_jobs);
        $batch->delete();
    }

    /**
     * allow_failures() runs every job; cancel() drops what has not run.
     *
     * @group system
     */
    public function testAllowFailuresAndCancel()
    {
        $batch = Job::batch([Job::dispatch('chainbatch-boom'), Job::dispatch('chainbatch-ok'), Job::dispatch('chainbatch-ok')])
            ->allow_failures()
            ->then(Job::dispatch('chainbatch-then'))
            ->on_finish(Job::dispatch('chainbatch-finish'))
            ->dispatch();

        $this->work();

        $ran = array_count_values($this->ran());
        $this->assertEquals(2, $ran['ok']);
        $this->assertEquals(1, $ran['finish']);
        $this->assertArrayNotHasKey('then', $ran);
        $this->assertFalse(Job::find_batch($batch->id)->cancelled());
        $batch->delete();

        static::$log = [];
        $batch = Job::batch([Job::dispatch('chainbatch-ok'), Job::dispatch('chainbatch-ok')])
            ->on_finish(Job::dispatch('chainbatch-finish'))
            ->dispatch()
            ->cancel();

        $this->assertTrue($batch->cancelled());
        $this->work();
        $this->assertEquals(['finish'], $this->ran());
        $batch->delete();

        // An empty batch is finished at once.
        static::$log = [];
        $batch = Job::batch([])->then(Job::dispatch('chainbatch-then'))->dispatch();
        $this->assertTrue($batch->finished());
        $this->work();
        $this->assertEquals(['then'], $this->ran());
        $batch->delete();
    }
}
