<?php

defined('DS') or exit('No direct script access.');

use System\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    private static $temp;

    /**
     * Setup.
     */
    public function setUp()
    {
        self::$temp = path('storage').'temp';

        if (! is_dir(self::$temp)) {
            mkdir(self::$temp);
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (is_dir(self::$temp)) {
            File::rmdir(self::$temp);
        }

        self::$temp = null;
    }

    /**
     * Test untuk method File::get().
     *
     * @group system
     */
    public function testGetRetrievesFiles()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'Hello World');
        $this->assertSame('Hello World', File::get(self::$temp.DS.'file.txt'));
    }

    /**
     * Test untuk method File::put().
     *
     * @group system
     */
    public function testPutStoresFiles()
    {
        File::put(self::$temp.DS.'file.txt', 'Hello World');
        $this->assertStringEqualsFile(self::$temp.DS.'file.txt', 'Hello World');
    }

    /**
     * Test untuk method File::chmod() - 1.
     *
     * @group system
     */
    public function testSetChmod()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'Hello World');

        File::chmod(self::$temp.DS.'file.txt', 0755);

        $actual = substr(sprintf('%o', fileperms(self::$temp.DS.'file.txt')), - 4);
        $expected = ('\\' === DS) ? '0666' : '0755';

        $this->assertTrue($expected === $actual);
    }

    /**
     * Test untuk method File::chmod() - 2.
     *
     * @group system
     */
    public function testGetChmod()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'Hello World');
        chmod(self::$temp.DS.'file.txt', 0755);

        $actual = File::chmod(self::$temp.DS.'file.txt');
        $expected = ('\\' === DS) ? '0666' : '0755';

        $this->assertTrue($expected === $actual);
    }

    /**
     * Test untuk method File::delete().
     *
     * @group system
     */
    public function testDeleteRemovesFiles()
    {
        file_put_contents(self::$temp.DS.'file1.txt', 'Hello World');

        File::delete(self::$temp.DS.'file1.txt');
        $this->assertTrue(! is_file(self::$temp.DS.'file1.txt'));
    }

    /**
     * Test untuk method File::prepend() - 1.
     *
     * @group system
     */
    public function testPrependExistingFiles()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'World');

        File::prepend(self::$temp.DS.'file.txt', 'Hello ');
        $this->assertStringEqualsFile(self::$temp.DS.'file.txt', 'Hello World');
    }

    /**
     * Test untuk method File::prepend() - 2.
     *
     * @group system
     */
    public function testPrependNewFiles()
    {
        File::prepend(self::$temp.DS.'file.txt', 'Hello World');
        $this->assertStringEqualsFile(self::$temp.DS.'file.txt', 'Hello World');
    }

    /**
     * Test untuk method File::rmdir() - 1.
     *
     * @group system
     */
    public function testDeleteDirectory()
    {
        mkdir(self::$temp.DS.'foo');
        file_put_contents(self::$temp.DS.'foo'.DS.'file.txt', 'Hello World');

        File::rmdir(self::$temp.DS.'foo');

        $this->assertTrue(! is_dir(self::$temp.DS.'foo'));
        $this->assertTrue(! is_file(self::$temp.DS.'foo'.DS.'file.txt'));
    }

    /**
     * Test untuk method File::rmdir() - 2.
     *
     * @group system
     */
    public function testDeleteDirectoryReturnFalseWhenNotADirectory()
    {
        mkdir(self::$temp.DS.'bar');
        file_put_contents(self::$temp.DS.'bar'.DS.'file.txt', 'Hello World');

        $this->assertFalse(File::rmdir(self::$temp.DS.'bar'.DS.'file.txt'));
    }

    /**
     * Test untuk method File::cleandir().
     *
     * @group system
     */
    public function testCleanDirectory()
    {
        mkdir(self::$temp.DS.'baz');
        file_put_contents(self::$temp.DS.'baz'.DS.'file.txt', 'Hello World');

        File::cleandir(self::$temp.DS.'baz');

        $this->assertTrue(is_dir(self::$temp.DS.'baz'));
        $this->assertTrue(! is_file(self::$temp.DS.'baz'.DS.'file.txt'));
    }

    /**
     * Test untuk method File::cpdir() - 1.
     *
     * @group system
     */
    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $origin = self::$temp.DS.'breeze'.DS.'boom'.DS.'foo'.DS.'bar'.DS.'baz';
        $this->assertFalse(File::cpdir($origin, self::$temp));
    }

    /**
     * Test untuk method File::cpdir() - 2.
     *
     * @group system
     */
    public function testCopyDirectoryMovesEntireDirectory()
    {
        mkdir(self::$temp.DS.'tmp', 0777, true);
        file_put_contents(self::$temp.DS.'tmp'.DS.'foo.txt', '');
        file_put_contents(self::$temp.DS.'tmp'.DS.'bar.txt', '');

        mkdir(self::$temp.DS.'tmp'.DS.'nested', 0777, true);
        file_put_contents(self::$temp.DS.'tmp'.DS.'nested'.DS.'baz.txt', '');

        File::cpdir(self::$temp.DS.'tmp', self::$temp.DS.'tmp2');

        $this->assertTrue(is_dir(self::$temp.DS.'tmp2'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp2'.DS.'foo.txt'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp2'.DS.'bar.txt'));
        $this->assertTrue(is_dir(self::$temp.DS.'tmp2'.DS.'nested'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp2'.DS.'nested'.DS.'baz.txt'));
    }

    /**
     * Test untuk method File::mvdir() - 1.
     *
     * @group system
     */
    public function testMoveDirectoryMovesEntireDirectory()
    {
        mkdir(self::$temp.DS.'tmp2', 0777, true);
        file_put_contents(self::$temp.DS.'tmp2'.DS.'foo.txt', '');
        file_put_contents(self::$temp.DS.'tmp2'.DS.'bar.txt', '');

        mkdir(self::$temp.DS.'tmp2'.DS.'nested', 0777, true);
        file_put_contents(self::$temp.DS.'tmp2'.DS.'nested'.DS.'baz.txt', '');

        File::mvdir(self::$temp.DS.'tmp2', self::$temp.DS.'tmp3');

        $this->assertTrue(is_dir(self::$temp.DS.'tmp3'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp3'.DS.'foo.txt'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp3'.DS.'bar.txt'));
        $this->assertTrue(is_dir(self::$temp.DS.'tmp3'.DS.'nested'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp3'.DS.'nested'.DS.'baz.txt'));

        $this->assertFalse(is_dir(self::$temp.DS.'tmp2'));
    }

    /**
     * Test untuk method File::mvdir() - 2.
     *
     * @group system
     */
    public function testMoveDirectoryMovesEntireDirectoryAndOverwrites()
    {
        mkdir(self::$temp.DS.'tmp4', 0777, true);
        file_put_contents(self::$temp.DS.'tmp4'.DS.'foo.txt', '');
        file_put_contents(self::$temp.DS.'tmp4'.DS.'bar.txt', '');

        mkdir(self::$temp.DS.'tmp4'.DS.'nested', 0777, true);
        file_put_contents(self::$temp.DS.'tmp4'.DS.'nested'.DS.'baz.txt', '');

        mkdir(self::$temp.DS.'tmp5', 0777, true);
        file_put_contents(self::$temp.DS.'tmp5'.DS.'foo2.txt', '');
        file_put_contents(self::$temp.DS.'tmp5'.DS.'bar2.txt', '');

        File::mvdir(self::$temp.DS.'tmp4', self::$temp.DS.'tmp5', true);

        $this->assertTrue(is_dir(self::$temp.DS.'tmp5'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp5'.DS.'foo.txt'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp5'.DS.'bar.txt'));
        $this->assertTrue(is_dir(self::$temp.DS.'tmp5'.DS.'nested'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp5'.DS.'nested'.DS.'baz.txt'));

        $this->assertFalse(is_file(self::$temp.DS.'tmp5'.DS.'foo2.txt'));
        $this->assertFalse(is_file(self::$temp.DS.'tmp5'.DS.'bar2.txt'));
        $this->assertFalse(is_dir(self::$temp.DS.'tmp4'));
    }

    /**
     * Test untuk method File::append().
     *
     * @group system
     */
    public function testAppendAddsDataToFile()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'foo');

        $append = File::append(self::$temp.DS.'file.txt', 'bar');

        $this->assertTrue(mb_strlen('bar', '8bit') === $append);
        $this->assertTrue(is_file(self::$temp.DS.'file.txt'));
        $this->assertSame('foobar', file_get_contents(self::$temp.DS.'file.txt'));
    }

    /**
     * Test untuk method File::move().
     *
     * @group system
     */
    public function testMoveMovesFiles()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        File::move(self::$temp.DS.'foo.txt', self::$temp.DS.'bar.txt');

        $this->assertTrue(is_file(self::$temp.DS.'bar.txt'));
        $this->assertFalse(is_file(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::name().
     *
     * @group system
     */
    public function testNameReturnsName()
    {
        file_put_contents(self::$temp.DS.'foobar.txt', 'foo');

        $this->assertSame('foobar', File::name(self::$temp.DS.'foobar.txt'));
    }

    /**
     * Test untuk method File::extension().
     *
     * @group system
     */
    public function testExtensionReturnsExtension()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('txt', File::extension(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::basename().
     *
     * @group system
     */
    public function testBasenameReturnsBasename()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('foo.txt', File::basename(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::dirname().
     *
     * @group system
     */
    public function testDirnameReturnsDirectory()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame(self::$temp, File::dirname(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::type() - 1.
     *
     * @group system
     */
    public function testTypeIdentifiesFile()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('file', File::type(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::type() - 2.
     *
     * @group system
     */
    public function testTypeIdentifiesDirectory()
    {
        mkdir(self::$temp.DS.'foo-dir');

        $this->assertSame('dir', File::type(self::$temp.DS.'foo-dir'));
    }

    /**
     * Test untuk method File::size().
     *
     * @group system
     */
    public function testSizeOutputsSize()
    {
        $size = file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertTrue($size === (int) File::size(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::mime().
     *
     * @group system
     */
    public function testMimeTypeOutputsMimeType()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('text/plain', File::mime(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::glob().
     *
     * @group system
     */
    public function testGlobFindsFiles()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');
        file_put_contents(self::$temp.DS.'bar.txt', 'bar');

        $glob = File::glob(self::$temp.DS.'*.txt');

        $this->assertTrue(in_array(self::$temp.DS.'foo.txt', $glob));
        $this->assertTrue(in_array(self::$temp.DS.'bar.txt', $glob));
    }

    /**
     * Test untuk method File::mkdir().
     *
     * @group system
     */
    public function testMakeDirectory()
    {
        $this->assertTrue(File::mkdir(self::$temp.DS.'created'));
        $this->assertTrue(is_dir(self::$temp.DS.'created'));
    }

    /**
     * Test untuk method File::cpdir().
     *
     * @group system
     */
    public function testCopyCopiesFileProperly()
    {
        $data = 'contents';
        mkdir(self::$temp.DS.'text');
        file_put_contents(self::$temp.DS.'text'.DS.'foo.txt', $data);

        File::copy(self::$temp.DS.'text'.DS.'foo.txt', self::$temp.DS.'text'.DS.'foo2.txt');

        $this->assertTrue(is_file(self::$temp.DS.'text'.DS.'foo2.txt'));
        $this->assertTrue($data === file_get_contents(self::$temp.DS.'text'.DS.'foo2.txt'));
    }

    /**
     * Test untuk method File::isfile().
     *
     * @group system
     */
    public function testIsFileChecksFilesProperly()
    {
        mkdir(self::$temp.DS.'help');
        file_put_contents(self::$temp.DS.'help'.DS.'foo.txt', 'contents');

        $this->assertTrue(File::isfile(self::$temp.DS.'help'.DS.'foo.txt'));
        $this->assertFalse(File::isfile(self::$temp.DS.'.'.DS.'help'));
    }

    /**
     * Test untuk method File::hash().
     *
     * @group system
     */
    public function testHash()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('acbd18db4cc2f85cedef654fccc4a4d8', File::hash(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method File::protect().
     *
     * @group system
     */
    public function testProtect()
    {
        $dir = path('storage').'protects'.DS;

        File::mkdir($dir);
        $this->assertTrue(is_file($dir.'index.html'));
        File::delete($dir.'index.html');

        File::put($dir.'foo.txt', '');
        $this->assertTrue(is_file($dir.'index.html'));
        File::rmdir($dir);
    }
}
