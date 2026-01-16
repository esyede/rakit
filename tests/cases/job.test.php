<?php

defined('DS') or exit('No direct access.');

use System\Job;
use System\Event;
use System\Config;
use System\Carbon;
use System\Storage;

class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('job.driver', 'file');
        Config::set('job.max_job', 10);
        Config::set('job.max_retries', 3);
        Config::set('job.sleep_ms', 100);
        Config::set('job.logging', false);

        // Cleanup job files
        if (is_dir($path = path('storage') . 'jobs' . DS)) {
            $files = glob($path . '*.job.php');
            if (is_array($files) && !empty($files)) {
                foreach ($files as $file) {
                    @unlink($file);
                    // echo 'ok';
                }
            }
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Event::$events = [];
        Job::$drivers = [];

        if (is_dir($path = path('storage') . 'jobs' . DS)) {
            $files = glob($path . '*.job.php');
            if (is_array($files) && !empty($files)) {
                foreach ($files as $file) {
                    @unlink($file);
                    // echo 'ok';
                }
            }
        }
    }

    /**
     * Test dispatch job dengan string schedule.
     *
     * @group system
     */
    public function testDispatchJobWithStringSchedule()
    {
        $result = Job::dispatch('test-job', ['email' => 'user@example.com'], '2025-12-31 10:00:00');
        $this->assertInstanceOf('System\Job\Pending', $result);
    }

    /**
     * Test dispatch job dengan Carbon instance.
     *
     * @group system
     */
    public function testDispatchJobWithCarbonInstance()
    {
        $schedule = Carbon::now()->addMinutes(5);
        $result = Job::dispatch('test-job', ['email' => 'user@example.com'], $schedule);

        $this->assertInstanceOf('System\Job\Pending', $result);
    }

    /**
     * Test dispatch job dengan DateTime instance.
     *
     * @group system
     */
    public function testDispatchJobWithDateTimeInstance()
    {
        $schedule = new DateTime('+1 hour');
        $result = Job::dispatch('test-job', ['email' => 'user@example.com'], $schedule);

        $this->assertInstanceOf('System\Job\Pending', $result);
    }

    /**
     * Test dispatch job dengan unix timestamp.
     *
     * @group system
     */
    public function testDispatchJobWithUnixTimestamp()
    {
        $schedule = time() + 600;
        $result = Job::dispatch('test-job', ['email' => 'user@example.com'], $schedule);

        $this->assertInstanceOf('System\Job\Pending', $result);
    }

    /**
     * Test dispatch job tanpa schedule (immediate).
     *
     * @group system
     */
    public function testDispatchJobImmediate()
    {
        $result = Job::dispatch('test-job', ['email' => 'user@example.com']);

        $this->assertInstanceOf('System\Job\Pending', $result);
    }

    /**
     * Test job driver file add.
     *
     * @group system
     */
    public function testFileDriverAdd()
    {
        $driver = Job::driver('file');
        $result = $driver->add('test-job', ['email' => 'user@example.com'], null, 'default', false);

        $this->assertTrue($result);

        $path = path('storage') . 'jobs' . DS;
        $files = glob($path . 'test-job__*.job.php');

        $this->assertNotEmpty($files);

    }

    /**
     * Test job driver file has_overlapping.
     *
     * @group system
     */
    public function testFileDriverHasOverlapping()
    {
        $driver = Job::driver('file');

        // Add job with without_overlapping
        $driver->add('test-overlap', ['email' => 'test@example.com'], null, 'default', true);

        $this->assertTrue($driver->has_overlapping('test-overlap', 'default'));
        $this->assertFalse($driver->has_overlapping('test-overlap', 'high'));
    }

    /**
     * Test job driver file forget.
     *
     * @group system
     */
    public function testFileDriverForget()
    {
        $driver = Job::driver('file');

        // Add jobs
        $driver->add('test-forget', ['id' => 1], null, 'default', false);
        $driver->add('test-forget', ['id' => 2], null, 'high', false);

        $path = path('storage') . 'jobs' . DS;
        $files = glob($path . 'test-forget__*.job.php');
        $this->assertCount(2, $files);

        // Forget specific queue
        $driver->forget('test-forget', 'default');
        $files = glob($path . 'test-forget__*.job.php');
        $this->assertCount(1, $files);

        // Forget all
        $driver->forget('test-forget');
        $files = glob($path . 'test-forget__*.job.php');
        $this->assertEmpty($files);
    }

    /**
     * Test job driver file run.
     *
     * @group system
     */
    public function testFileDriverRun()
    {
        $driver = Job::driver('file');
        $executed = false;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$executed) {
            $executed = true;
        });

        // Add job with past schedule
        $driver->add('test-run', ['email' => 'test@example.com'], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);

        // Run job
        $driver->run('test-run', 1, 0, 'default');

        $this->assertTrue($executed);

        // Job should be deleted after successful run
        $path = path('storage') . 'jobs' . DS;
        $files = glob($path . 'test-run__*.job.php');
        $this->assertEmpty($files);
    }

    /**
     * Test job driver file runall.
     *
     * @group system
     */
    public function testFileDriverRunAll()
    {
        $driver = Job::driver('file');
        $count = 0;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Add multiple jobs
        $driver->add('job-one', ['id' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        $driver->add('job-two', ['id' => 2], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        $driver->add('job-three', ['id' => 3], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'high', false);

        // Run all jobs
        $driver->runall(1, 0);

        $this->assertEquals(3, $count);
    }

    /**
     * Test job driver file runall dengan queue filter.
     *
     * @group system
     */
    public function testFileDriverRunAllWithQueueFilter()
    {
        $driver = Job::driver('file');
        $count = 0;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Add jobs to different queues
        $driver->add('job-one', ['id' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        $driver->add('job-two', ['id' => 2], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'high', false);

        // Run only 'default' queue
        $driver->runall(1, 0, ['default']);

        $this->assertEquals(1, $count);

        // High queue job should still exist
        $path = path('storage') . 'jobs' . DS;
        $files = glob($path . 'job-two__*.job.php');
        $this->assertNotEmpty($files);
    }

    /**
     * Test job driver file dengan scheduled job (future).
     *
     * @group system
     */
    public function testFileDriverScheduledJobNotExecuted()
    {
        $driver = Job::driver('file');
        $executed = false;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$executed) {
            $executed = true;
        });

        // Add job with future schedule
        $driver->add('future-job', ['id' => 1], Carbon::now()->addHours(1)->format('Y-m-d H:i:s'), 'default', false);

        // Try to run - should not execute
        $driver->run('future-job', 1, 0, 'default');

        $this->assertFalse($executed);

        // Job should still exist
        $path = path('storage') . 'jobs' . DS;
        $files = glob($path . 'future-job__*.job.php');
        $this->assertNotEmpty($files);
    }

    /**
     * Test job slug conversion.
     *
     * @group system
     */
    public function testJobNameSlugConversion()
    {
        $driver = Job::driver('file');

        // Add job with non-slug name
        $driver->add('Send Email Job', ['email' => 'test@example.com']);

        $path = path('storage') . 'jobs' . DS;
        $files = glob($path . 'send-email-job__*.job.php');

        $this->assertNotEmpty($files);
    }

    /**
     * Test file driver exclude failed jobs.
     *
     * @group system
     */
    public function testFileDriverExcludeFailedJobs()
    {
        $driver = Job::driver('file');
        $path = path('storage') . 'jobs' . DS;

        // Create a regular job and a failed job
        $driver->add('normal-job', ['id' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);

        // Manually create a failed job file
        $data = [
            'id' => 'failed123',
            'name' => 'failed-job',
            'queue' => 'default',
            'payloads' => serialize(['id' => 2]),
            'exception' => 'Test exception',
            'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        file_put_contents($path . 'failed__failed123.job.php', $guard . serialize($data));

        $count = 0;
        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Run all - should only execute normal job
        $driver->runall(1, 0);

        $this->assertEquals(1, $count);

        // Failed job should still exist
        $this->assertFileExists($path . 'failed__failed123.job.php');
    }

    /**
     * Test max_job limit.
     *
     * @group system
     */
    public function testMaxJobLimit()
    {
        Config::set('job.max_job', 2);
        $driver = Job::driver('file');
        $count = 0;

        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Add 5 jobs
        for ($i = 1; $i <= 5; $i++) {
            $driver->add('job-' . $i, ['id' => $i], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        }

        // Run all - should only process 2
        $driver->runall(1, 0);

        $this->assertEquals(2, $count);
    }
}

class JobDatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('job.driver', 'database');
        Config::set('job.table', 'rakit_jobs');
        Config::set('job.failed_table', 'rakit_failed_jobs');
        Config::set('job.max_job', 10);
        Config::set('job.max_retries', 3);
        Config::set('job.sleep_ms', 100);
        Config::set('job.logging', false);

        // Cleanup job tables
        try {
            System\Database::table('rakit_jobs')->delete();
            System\Database::table('rakit_failed_jobs')->delete();
        } catch (Exception $e) {
            // Tables might not exist in test environment
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Event::$events = [];
        Job::$drivers = [];

        // Cleanup
        try {
            System\Database::table('rakit_jobs')->delete();
            System\Database::table('rakit_failed_jobs')->delete();
        } catch (Exception $e) {
            // Ignore
        }
    }

    /**
     * Final cleanup setelah semua test selesai.
     */
    public static function tearDownAfterClass()
    {
        try {
            System\Database::table('rakit_jobs')->delete();
            System\Database::table('rakit_failed_jobs')->delete();
        } catch (Exception $e) {
            // Ignore
        }
    }

    /**
     * Test database driver add.
     *
     * @group system
     */
    public function testDatabaseDriverAdd()
    {
        $driver = Job::driver('database');
        $result = $driver->add('test-job', ['email' => 'user@example.com'], null, 'default', false);

        $this->assertTrue($result);

        $count = System\Database::table('rakit_jobs')->where('name', 'test-job')->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test database driver has_overlapping.
     *
     * @group system
     */
    public function testDatabaseDriverHasOverlapping()
    {
        $driver = Job::driver('database');

        // Add job with without_overlapping
        $driver->add('test-overlap', ['email' => 'test@example.com'], null, 'default', true);

        $this->assertTrue($driver->has_overlapping('test-overlap', 'default'));
        $this->assertFalse($driver->has_overlapping('test-overlap', 'high'));
    }

    /**
     * Test database driver forget.
     *
     * @group system
     */
    public function testDatabaseDriverForget()
    {
        $driver = Job::driver('database');

        // Add jobs
        $driver->add('test-forget', ['id' => 1], null, 'default', false);
        $driver->add('test-forget', ['id' => 2], null, 'high', false);

        $count = System\Database::table('rakit_jobs')->where('name', 'test-forget')->count();
        $this->assertEquals(2, $count);

        // Forget specific queue
        $driver->forget('test-forget', 'default');
        $count = System\Database::table('rakit_jobs')->where('name', 'test-forget')->count();
        $this->assertEquals(1, $count);

        // Forget all
        $driver->forget('test-forget');
        $count = System\Database::table('rakit_jobs')->where('name', 'test-forget')->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test database driver run.
     *
     * @group system
     */
    public function testDatabaseDriverRun()
    {
        $driver = Job::driver('database');
        $executed = false;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$executed) {
            $executed = true;
        });

        // Add job with past schedule
        $driver->add('test-run', ['email' => 'test@example.com'], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);

        // Run job
        $driver->run('test-run', 1, 0, 'default');

        $this->assertTrue($executed);

        // Job should be deleted after successful run
        $count = System\Database::table('rakit_jobs')->where('name', 'test-run')->count();
        $this->assertEquals(0, $count);
    }

    /**
     * Test database driver runall.
     *
     * @group system
     */
    public function testDatabaseDriverRunAll()
    {
        $driver = Job::driver('database');
        $count = 0;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Add multiple jobs
        $driver->add('job-one', ['id' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        $driver->add('job-two', ['id' => 2], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        $driver->add('job-three', ['id' => 3], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'high', false);

        // Run all jobs
        $driver->runall(1, 0);

        $this->assertEquals(3, $count);
    }

    /**
     * Test database driver runall dengan queue filter.
     *
     * @group system
     */
    public function testDatabaseDriverRunAllWithQueueFilter()
    {
        $driver = Job::driver('database');
        $count = 0;

        // Register event listener
        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Add jobs to different queues
        $driver->add('job-one', ['id' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        $driver->add('job-two', ['id' => 2], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'high', false);

        // Run only 'default' queue
        $driver->runall(1, 0, ['default']);

        $this->assertEquals(1, $count);

        // High queue job should still exist
        $remaining = System\Database::table('rakit_jobs')->where('name', 'job-two')->count();
        $this->assertEquals(1, $remaining);
    }

    /**
     * Test database driver failed jobs dengan queue column.
     *
     * @group system
     */
    public function testDatabaseDriverFailedJobsWithQueue()
    {
        $driver = Job::driver('database');

        // Register event listener yang throw exception
        Event::listen('rakit.jobs.process', function ($data) {
            throw new Exception('Test job failure');
        });

        // Add job
        $driver->add('failing-job', ['id' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'high', false);

        // Run job - should fail and move to failed_jobs
        $driver->run('failing-job', 1, 0, 'high');

        // Check failed job exists with queue
        $failed = System\Database::table('rakit_failed_jobs')->where('name', 'failing-job')->first();

        $this->assertNotNull($failed);
        $this->assertEquals('high', $failed->queue);
        $this->assertContains('Test job failure', $failed->exception);
    }

    /**
     * Test database driver max_job limit.
     *
     * @group system
     */
    public function testDatabaseDriverMaxJobLimit()
    {
        Config::set('job.max_job', 2);
        $driver = Job::driver('database');
        $count = 0;

        Event::listen('rakit.jobs.process', function ($data) use (&$count) {
            $count++;
        });

        // Add 5 jobs
        for ($i = 1; $i <= 5; $i++) {
            $driver->add('job-' . $i, ['id' => $i], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'), 'default', false);
        }

        // Run all - should only process 2
        $driver->runall(1, 0);

        $this->assertEquals(2, $count);

        // 3 jobs should remain
        $remaining = System\Database::table('rakit_jobs')->count();
        $this->assertEquals(3, $remaining);
    }
}
