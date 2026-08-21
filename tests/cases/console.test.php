<?php

defined('DS') or exit('No direct access.');

use System\Console\Color;
use System\Console\Table;
use System\Console\Console;

class ConsoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Call a protected/private method.
     *
     * @param mixed  $target
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function call($target, $method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod(is_object($target) ? get_class($target) : $target, $method);
        PHP_VERSION_ID < 80100 && $reflection->setAccessible(true);

        return $reflection->invokeArgs(is_object($target) ? $target : null, $arguments);
    }

    // -------------------------------------------------------------------------
    // Console::options()
    // -------------------------------------------------------------------------

    /**
     * Test for Console::options() - splits arguments from options.
     *
     * @group system
     */
    public function testOptionsSplitsArgumentsAndOptions()
    {
        list($arguments, $options) = Console::options(['rakit', 'migrate', '--env=production', '--force']);

        $this->assertEquals(['rakit', 'migrate'], $arguments);
        $this->assertEquals(['env' => 'production', 'force' => true], $options);
    }

    /**
     * A single dash is an argument, not an option.
     *
     * @group system
     */
    public function testOptionsTreatsSingleDashAsArgument()
    {
        list($arguments, $options) = Console::options(['rakit', 'test:core', '-v']);

        $this->assertEquals(['rakit', 'test:core', '-v'], $arguments);
        $this->assertEquals([], $options);
    }

    /**
     * An option value may itself contain an equals sign.
     *
     * @group system
     */
    public function testOptionsKeepsEqualsInsideTheValue()
    {
        list($arguments, $options) = Console::options(['--dsn=host=db;port=5432']);

        $this->assertEquals([], $arguments);
        $this->assertEquals(['dsn' => 'host=db;port=5432'], $options);
    }

    /**
     * Test for Console::options() - empty input.
     *
     * @group system
     */
    public function testOptionsWithEmptyInput()
    {
        $this->assertEquals([[], []], Console::options([]));
    }

    // -------------------------------------------------------------------------
    // Console::parse()
    // -------------------------------------------------------------------------

    /**
     * Test for Console::parse() - defaults to the 'run' method.
     *
     * @group system
     */
    public function testParseDefaultsToRun()
    {
        $this->assertEquals(
            [DEFAULT_PACKAGE, 'migrate', 'run'],
            $this->call('\System\Console\Console', 'parse', ['migrate'])
        );
    }

    /**
     * Test for Console::parse() - reads the method after the colon.
     *
     * @group system
     */
    public function testParseReadsMethod()
    {
        $this->assertEquals(
            [DEFAULT_PACKAGE, 'migrate', 'rollback'],
            $this->call('\System\Console\Console', 'parse', ['migrate:rollback'])
        );
    }

    /**
     * Test for Console::parse() - reads the package prefix.
     *
     * @group system
     */
    public function testParseReadsPackage()
    {
        $this->assertEquals(
            ['admin', 'migrate', 'rollback'],
            $this->call('\System\Console\Console', 'parse', ['admin::migrate:rollback'])
        );
    }

    /**
     * Test for Console::run() - unknown commands are reported.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testRunThrowsForUnknownCommand()
    {
        Console::run(['this_command_does_not_exist_xyz']);
    }

    // -------------------------------------------------------------------------
    // Color
    // -------------------------------------------------------------------------

    /**
     * Every colour helper returns the text and honours the newline flag.
     *
     * @group system
     */
    public function testColorHelpersReturnTheText()
    {
        $colors = ['black', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white'];

        foreach ($colors as $color) {
            $with = Color::{$color}('halo');
            $without = Color::{$color}('halo', false);

            $this->assertContains('halo', $with, $color);
            $this->assertStringEndsWith(PHP_EOL, $with, $color);
            $this->assertStringEndsNotWith(PHP_EOL, $without, $color);
        }
    }

    /**
     * Color::supported() must answer without blowing up, also when STDOUT is
     * not defined (which is the case outside the CLI SAPI).
     *
     * @group system
     */
    public function testColorSupportedReturnsBoolean()
    {
        $this->assertInternalType('boolean', Color::supported());
    }

    // -------------------------------------------------------------------------
    // Table
    // -------------------------------------------------------------------------

    /**
     * Test for Table - renders headers and rows inside a border.
     *
     * @group system
     */
    public function testTableRendersHeadersAndRows()
    {
        $table = new Table();
        $table->set_headers(['Nama', 'Kota']);
        $table->add_row(['Budi', 'Yogyakarta']);
        $table->add_row(['Ani', 'Bandung']);

        $output = $table->get_table();

        $this->assertContains('| Nama | Kota       |', $output);
        $this->assertContains('| Budi | Yogyakarta |', $output);
        $this->assertContains('| Ani  | Bandung    |', $output);
        $this->assertContains('+------+------------+', $output);
    }

    /**
     * Column widths must be measured the same way they are padded, otherwise a
     * multibyte cell throws the whole table out of alignment.
     *
     * @group system
     */
    public function testTableAlignsMultibyteContent()
    {
        $table = new Table();
        $table->set_headers(['Nama']);
        $table->add_row(['Añá Ölçü']);
        $table->add_row(['Budi']);

        $lines = array_values(array_filter(explode(PHP_EOL, $table->get_table())));
        $widths = array_map('mb_strlen', $lines);

        $this->assertGreaterThan(1, count($lines));
        $this->assertEquals(1, count(array_unique($widths)), 'every rendered line must be the same width');
    }

    /**
     * Test for Table::get_headers() and add_header().
     *
     * @group system
     */
    public function testTableHeaderAccessors()
    {
        $table = new Table();
        $this->assertNull($table->get_headers());

        $table->add_header('Satu')->add_header('Dua');
        $this->assertEquals(['Satu', 'Dua'], $table->get_headers());
    }

    /**
     * Test for Table::hide_border().
     *
     * @group system
     */
    public function testTableWithoutBorder()
    {
        $table = new Table();
        $table->hide_border();
        $table->add_row(['Budi', 'Yogyakarta']);

        $output = $table->get_table();

        $this->assertNotContains('|', $output);
        $this->assertNotContains('+', $output);
        $this->assertContains('Budi', $output);
    }

    /**
     * Test for Table::add_column() and add_border_line().
     *
     * @group system
     */
    public function testTableAddColumnAndBorderLine()
    {
        $table = new Table();
        $table->add_row(['a']);
        $table->add_column('b');
        $table->add_border_line();
        $table->add_row(['c', 'd']);

        $output = $table->get_table();

        $this->assertContains('a', $output);
        $this->assertContains('b', $output);
        $this->assertContains('c', $output);
        $this->assertContains('d', $output);
    }

    /**
     * An empty table must not blow up.
     *
     * @group system
     */
    public function testEmptyTable()
    {
        $table = new Table();
        $this->assertInternalType('string', $table->get_table());
    }

    // -------------------------------------------------------------------------
    // Command helpers
    // -------------------------------------------------------------------------

    /**
     * The filled and the empty part of the progress bar must differ, otherwise
     * the bar looks identical at every percentage.
     *
     * @group system
     */
    public function testProgressBarGrows()
    {
        $command = new ConsoleProbeCommand();

        $zero = $command->probe_progress(0);
        $half = $command->probe_progress(50);
        $full = $command->probe_progress(100);

        $this->assertNotEquals($zero, $half);
        $this->assertNotEquals($half, $full);

        $this->assertEquals(0, mb_substr_count($zero, '▓'));
        $this->assertEquals(5, mb_substr_count($half, '▓'));
        $this->assertEquals(10, mb_substr_count($full, '▓'));

        $this->assertEquals(10, mb_substr_count($zero, '░'));
        $this->assertEquals(5, mb_substr_count($half, '░'));
        $this->assertEquals(0, mb_substr_count($full, '░'));
    }

    /**
     * A percentage above 100 is refused.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testProgressRejectsTooLargePercentage()
    {
        $command = new ConsoleProbeCommand();
        $command->probe_progress(101);
    }

    /**
     * A negative percentage is refused too, it used to reach str_repeat().
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testProgressRejectsNegativePercentage()
    {
        $command = new ConsoleProbeCommand();
        $command->probe_progress(-1);
    }

    /**
     * Test the info/warning/error helpers.
     *
     * @group system
     */
    public function testMessageHelpers()
    {
        $command = new ConsoleProbeCommand();

        $this->assertContains('oke', $command->probe_info('oke', false));
        $this->assertContains('hati-hati', $command->probe_warning('hati-hati', false));
        $this->assertContains('gagal', $command->probe_error('gagal', false));
    }
}

/**
 * Exposes the protected helpers of the base command for testing.
 */
class ConsoleProbeCommand extends \System\Console\Commands\Command
{
    public function probe_progress($percentage)
    {
        return $this->progress($percentage);
    }

    public function probe_info($text, $newline = true)
    {
        return $this->info($text, $newline);
    }

    public function probe_warning($text, $newline = true)
    {
        return $this->warning($text, $newline);
    }

    public function probe_error($text, $newline = true)
    {
        return $this->error($text, $newline);
    }
}
