<?php

defined('DS') or exit('No direct access.');

use System\Autoloader;

class AutoloaderTest extends \PHPUnit_Framework_TestCase
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
     * Test for Autoloader::map().
     *
     * @group system
     */
    public function testMapsCanBeRegistered()
    {
        Autoloader::map(['Foo' => path('app') . 'models' . DS . 'foo.php']);
        $this->assertEquals(path('app') . 'models' . DS . 'foo.php', Autoloader::$mappings['Foo']);
    }

    /**
     * Test for Autoloader::alias().
     *
     * @group system
     */
    public function testAliasesCanBeRegistered()
    {
        Autoloader::alias('Foo\Bar', 'Foo');
        $this->assertEquals('Foo\Bar', Autoloader::$aliases['Foo']);
    }

    /**
     * Test for Autoloader::directories().
     *
     * @group system
     */
    public function testPsrDirectoriesCanBeRegistered()
    {
        Autoloader::directories([
            path('app') . 'foo' . DS . 'bar',
            path('app') . 'foo' . DS . 'baz' . DS . DS, // test trim()
        ]);

        $this->assertTrue(in_array(path('app') . 'foo' . DS . 'bar' . DS, Autoloader::$directories));
        $this->assertTrue(in_array(path('app') . 'foo' . DS . 'baz' . DS, Autoloader::$directories));
    }

    /**
     * Test for Autoloader::namespaces().
     *
     * @group system
     */
    public function testNamespacesCanBeRegistered()
    {
        Autoloader::namespaces([
            'NsOne' => path('package') . 'autoload' . DS . 'models',
            'NsTwo' => path('package') . 'autoload' . DS . 'libraries' . DS . DS,
        ]);

        $this->assertEquals(path('package') . 'autoload' . DS . 'models' . DS, Autoloader::$namespaces['NsOne\\']);
        $this->assertEquals(path('package') . 'autoload' . DS . 'libraries' . DS, Autoloader::$namespaces['NsTwo\\']);
    }

    /**
     * Test loading model dan library menggunakan PSR-0.
     *
     * @group system
     */
    public function testPsrLibrariesAndModelsCanBeLoaded()
    {
        $this->assertInstanceOf('User', new User());
        $this->assertInstanceOf('Repositories\User', new Repositories\User());
    }

    /**
     * Test loading kelas yang di hard-code.
     *
     * @group system
     */
    public function testHardcodedClassesCanBeLoaded()
    {
        Autoloader::map(['Hardcoded' => path('app') . 'models' . DS . 'hardcoded.php']);
        $this->assertInstanceOf('Hardcoded', new Hardcoded());
    }

    /**
     * Test untuk loading kelas berdasarkan namespace.
     *
     * @group system
     */
    public function testClassesMappedByNamespaceCanBeLoaded()
    {
        Autoloader::namespaces(['Dashboard' => path('package') . 'dashboard' . DS . 'models']);
        $this->assertInstanceOf('Dashboard\Repository', new Dashboard\Repository());
    }

    /**
     * Test for Autoloader::stats().
     *
     * @group system
     */
    public function testStats()
    {
        $stats = Autoloader::stats();

        $this->assertArrayHasKey('loaded_files', $stats);
        $this->assertArrayHasKey('mappings', $stats);
        $this->assertArrayHasKey('namespaces', $stats);
        $this->assertArrayHasKey('directories', $stats);
        $this->assertArrayHasKey('aliases', $stats);
        $this->assertTrue(is_int($stats['loaded_files']));
        $this->assertTrue(is_int($stats['mappings']));
    }

    /**
     * Every class name that resolves to nothing leaves probes behind, so a
     * process that stays up — the websocket server runs an endless loop — must
     * not be able to grow the cache without bound. Left unbounded this is not
     * a slow leak but a way to exhaust the server's memory.
     *
     * @group system
     */
    public function testProbeCacheStaysBounded()
    {
        $limit = Autoloader::$limit;
        Autoloader::$limit = 40;

        // Each miss leaves one probe per registered directory, so this is
        // several times over the limit.
        for ($i = 0; $i < 200; $i++) {
            class_exists('Bounded\Probe\Ghost' . $i);
        }

        $stats = Autoloader::stats();
        Autoloader::$limit = $limit;

        $this->assertLessThanOrEqual(40, $stats['caches']);
    }

    /**
     * Setting the limit to zero opts out of the ceiling entirely.
     *
     * @group system
     */
    public function testProbeCacheCanBeLeftUnbounded()
    {
        $limit = Autoloader::$limit;
        Autoloader::$limit = 0;

        $before = Autoloader::stats();

        for ($i = 0; $i < 50; $i++) {
            class_exists('Unbounded\Probe\Ghost' . $i);
        }

        $after = Autoloader::stats();
        Autoloader::$limit = $limit;

        $this->assertGreaterThan($before['caches'], $after['caches']);
    }

    /**
     * A class name that matches no file at all is not an error: the next
     * autoloader in the SPL stack has to get its turn, and class_exists()
     * has to keep answering false instead of blowing up.
     *
     * @group system
     */
    public function testMissingClassesAreNotAnError()
    {
        $this->assertFalse(class_exists('This\Class\Does\Not\Exist\Anywhere'));
        $this->assertNull(Autoloader::load('Another\Missing\Class'));
    }

    /**
     * When a class file itself fails while being included, the failure has to
     * reach the caller. Swallowing it would surface later as PHP's own
     * "Class not found", which points at the wrong file entirely.
     *
     * @group system
     */
    public function testFailureInsideAClassFileIsNotSwallowed()
    {
        $directory = path('app') . 'libraries' . DS;
        $parent = $directory . 'autoloadprobeparent.php';
        $child = $directory . 'autoloadprobechild.php';

        // The parent lives in its own file so the child cannot be early-bound
        // at compile time; otherwise PHP would declare it despite the throw.
        file_put_contents($parent, '<?php' . "\n\n" . 'class AutoloadProbeParent {}' . "\n");
        file_put_contents(
            $child,
            '<?php' . "\n\n" . 'throw new \RuntimeException(\'boom from inside the class file\');'
                . "\n\n" . 'class AutoloadProbeChild extends AutoloadProbeParent {}' . "\n"
        );

        $caught = null;

        try {
            class_exists('AutoloadProbeChild');
        } catch (\Throwable $e) {
            $caught = $e;
        } catch (\Exception $e) {
            $caught = $e;
        }

        unlink($parent);
        unlink($child);

        $this->assertNotNull($caught, 'The failure inside the class file was swallowed.');
        $this->assertEquals('boom from inside the class file', $caught->getMessage());

        // Compared by basename rather than in full: Windows may hand back the
        // path with a different separator or letter case than the one we built.
        $this->assertEquals('autoloadprobechild.php', basename($caught->getFile()));
    }
}
