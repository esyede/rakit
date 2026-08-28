<?php

defined('DS') or exit('No direct access.');

use System\Package;
use System\Database;
use System\Database\Schema;
use System\Console\Commands\Migrate\Migrator;
use System\Console\Commands\Migrate\Resolver;
use System\Console\Commands\Migrate\Database as MigrateDatabase;

class MigrateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Package the temporary migration belongs to.
     */
    const PACKAGE = 'dummy';

    /**
     * Name of the temporary migration (without the .php extension).
     */
    const MIGRATION = '2026_01_01_000000_create_migrate_probe_table';

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->cleanup();

        $path = $this->path();

        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($path.self::MIGRATION.'.php', $this->stub());
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->cleanup();
    }

    /**
     * Test for Migrate\Database::last() - hands back a plain array.
     *
     * @group system
     */
    public function testLastReturnsAnArray()
    {
        $database = new MigrateDatabase();
        $this->install($database);

        $database->log(self::PACKAGE, self::MIGRATION, 1);

        $this->assertTrue(is_array($database->last()));
        $this->assertCount(1, $database->last());
    }

    /**
     * Test for Migrator::migrate() - runs the outstanding migration.
     *
     * @group system
     */
    public function testMigrateRunsOutstandingMigration()
    {
        $migrator = $this->migrator();

        $this->silently($migrator, 'run', [self::PACKAGE]);

        $this->assertTrue(Schema::has_table('migrate_probe'));
    }

    /**
     * Test for Migrator::rollback() - reverts the last batch.
     *
     * @group system
     */
    public function testRollbackRevertsTheLastBatch()
    {
        $migrator = $this->migrator();

        $this->silently($migrator, 'run', [self::PACKAGE]);
        $this->assertTrue(Schema::has_table('migrate_probe'));

        $this->silently($migrator, 'rollback', [self::PACKAGE]);
        $this->assertFalse(Schema::has_table('migrate_probe'));
    }

    /**
     * Test for Migrator::migrate() - a migration only runs once.
     *
     * @group system
     */
    public function testMigrateSkipsMigrationThatHasRun()
    {
        $migrator = $this->migrator();

        $this->silently($migrator, 'run', [self::PACKAGE]);
        $output = $this->silently($migrator, 'migrate', [self::PACKAGE]);

        $this->assertContains('No outstanding migrations', $output);
    }

    /**
     * Get a migrator wired to a fresh migration table.
     *
     * @return Migrator
     */
    private function migrator()
    {
        $database = new MigrateDatabase();
        $this->install($database);

        return new Migrator(new Resolver($database), $database);
    }

    /**
     * Create an empty migration table.
     *
     * @param MigrateDatabase $database
     */
    private function install(MigrateDatabase $database)
    {
        Schema::drop_if_exists('rakit_migrations');

        Schema::create('rakit_migrations', function ($table) {
            $table->string('package', 50);
            $table->string('name', 200);
            $table->integer('batch');
            $table->primary(['package', 'name']);
        });
    }

    /**
     * Run a migrator method without letting it write to the output.
     *
     * @param Migrator $migrator
     * @param string   $method
     * @param array    $arguments
     *
     * @return string
     */
    private function silently(Migrator $migrator, $method, array $arguments)
    {
        ob_start();
        $migrator->{$method}($arguments);

        return (string) ob_get_clean();
    }

    /**
     * Get the migration directory of the test package.
     *
     * @return string
     */
    private function path()
    {
        return Package::path(self::PACKAGE).'migrations'.DS;
    }

    /**
     * Delete everything the test leaves behind.
     */
    private function cleanup()
    {
        Schema::drop_if_exists('migrate_probe');
        Schema::drop_if_exists('rakit_migrations');

        $path = $this->path();

        if (is_file($file = $path.self::MIGRATION.'.php')) {
            unlink($file);
        }

        if (is_dir($path) && ! glob($path.'*.php')) {
            @rmdir($path);
        }
    }

    /**
     * Get the source of the temporary migration.
     *
     * @return string
     */
    private function stub()
    {
        return '<?php'.LF.LF
            .'class Dummy_Create_Migrate_Probe_Table'.LF
            .'{'.LF
            .'    public function up()'.LF
            .'    {'.LF
            .'        System\Database\Schema::create("migrate_probe", function ($table) {'.LF
            .'            $table->increments("id");'.LF
            .'            $table->string("name");'.LF
            .'        });'.LF
            .'    }'.LF
            .LF
            .'    public function down()'.LF
            .'    {'.LF
            .'        System\Database\Schema::drop("migrate_probe");'.LF
            .'    }'.LF
            .'}'.LF;
    }
}
