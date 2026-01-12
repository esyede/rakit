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
     * Test untuk method Autoloader::map().
     *
     * @group system
     */
    public function testMapsCanBeRegistered()
    {
        Autoloader::map(['Foo' => path('app') . 'models' . DS . 'foo.php']);

        $this->assertEquals(path('app') . 'models' . DS . 'foo.php', Autoloader::$mappings['Foo']);
    }

    /**
     * Test untuk method Autoloader::alias().
     *
     * @group system
     */
    public function testAliasesCanBeRegistered()
    {
        Autoloader::alias('Foo\Bar', 'Foo');

        $this->assertEquals('Foo\Bar', Autoloader::$aliases['Foo']);
    }

    /**
     * Test untuk method Autoloader::directories().
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
     * Test untuk method Autoloader::namespaces().
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
     * Test untuk method Autoloader::generate_classmap().
     *
     * @group system
     */
    public function testClassmapCanBeGenerated()
    {
        $directory = path('storage') . 'test_classmap' . DS;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $directory . 'TestClass.php';
        file_put_contents($file, "<?php\n\nnamespace TestNamespace;\n\nclass TestClass {}\n");
        $classmap = Autoloader::generate_classmap([$directory]);

        $this->assertArrayHasKey('TestNamespace\TestClass', $classmap);
        $this->assertTrue(is_file(path('storage') . 'classmap.php'));

        @unlink($file);
        @rmdir($directory);
        @unlink(path('storage') . 'classmap.php');
    }

    /**
     * Test untuk method Autoloader::load_classmap().
     *
     * @group system
     */
    public function testClassmapCanBeLoaded()
    {
        $classmap = path('storage') . 'classmap.php';
        $content = "<?php\n\nreturn [\n    'TestLoadClass' => '" . path('app') . "models' . DS . 'user.php',\n];\n";
        file_put_contents($classmap, $content);

        $this->assertTrue(Autoloader::load_classmap($classmap));
        $this->assertArrayHasKey('TestLoadClass', Autoloader::$mappings);

        @unlink($classmap);
    }

    /**
     * Test untuk method Autoloader::extract_classes_from_file().
     *
     * @group system
     */
    public function testClassExtraction()
    {
        if (is_file($file = path('app') . 'models' . DS . 'user.php')) {
            $reflection = new \ReflectionClass('System\Autoloader');
            $method = $reflection->getMethod('extract_classes_from_file');
            $classes = $method->invoke(null, $file);

            $this->assertTrue(is_array($classes));
        }
    }

    /**
     * Test untuk method Autoloader::get_stats().
     *
     * @group system
     */
    public function testGetStats()
    {
        $stats = Autoloader::get_stats();

        $this->assertArrayHasKey('loaded_files', $stats);
        $this->assertArrayHasKey('mappings', $stats);
        $this->assertArrayHasKey('namespaces', $stats);
        $this->assertArrayHasKey('directories', $stats);
        $this->assertArrayHasKey('aliases', $stats);
        $this->assertTrue(is_int($stats['loaded_files']));
        $this->assertTrue(is_int($stats['mappings']));
    }

    /**
     * Test untuk method Autoloader::scan_directory().
     *
     * @group system
     */
    public function testDirectoryScanning()
    {
        $reflection = new \ReflectionClass('System\Autoloader');
        $method = $reflection->getMethod('scan_directory');
        $files = $method->invoke(null, path('app') . 'models');

        $this->assertTrue(is_array($files));
        $this->assertGreaterThan(0, count($files));

        foreach ($files as $file) {
            $this->assertEquals('php', pathinfo($file, PATHINFO_EXTENSION));
        }
    }
}
