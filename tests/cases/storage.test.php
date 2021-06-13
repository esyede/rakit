<?php

defined('DS') or exit('No direct script access.');

use System\Storage;

class StorageTest extends \PHPUnit_Framework_TestCase
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
            Storage::rmdir(self::$temp);
        }

        self::$temp = null;
    }

    /**
     * Test untuk method Storage::get().
     *
     * @group system
     */
    public function testGetRetrievesStorages()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'Hello World');
        $this->assertSame('Hello World', Storage::get(self::$temp.DS.'file.txt'));
    }

    /**
     * Test untuk method Storage::put().
     *
     * @group system
     */
    public function testPutStoresStorages()
    {
        Storage::put(self::$temp.DS.'file.txt', 'Hello World');
        $this->assertStringEqualsStorage(self::$temp.DS.'file.txt', 'Hello World');
    }

    /**
     * Test untuk method Storage::chmod() - 1.
     *
     * @group system
     */
    public function testSetChmod()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'Hello World');

        Storage::chmod(self::$temp.DS.'file.txt', 0755);

        $actual = substr(sprintf('%o', fileperms(self::$temp.DS.'file.txt')), -4);
        $expected = ('\\' === DS) ? '0666' : '0755';

        $this->assertTrue($expected === $actual);
    }

    /**
     * Test untuk method Storage::chmod() - 2.
     *
     * @group system
     */
    public function testGetChmod()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'Hello World');
        chmod(self::$temp.DS.'file.txt', 0755);

        $actual = Storage::chmod(self::$temp.DS.'file.txt');
        $expected = ('\\' === DS) ? '0666' : '0755';

        $this->assertTrue($expected === $actual);
    }

    /**
     * Test untuk method Storage::delete().
     *
     * @group system
     */
    public function testDeleteRemovesStorages()
    {
        file_put_contents(self::$temp.DS.'file1.txt', 'Hello World');

        Storage::delete(self::$temp.DS.'file1.txt');
        $this->assertTrue(! is_file(self::$temp.DS.'file1.txt'));
    }

    /**
     * Test untuk method Storage::prepend() - 1.
     *
     * @group system
     */
    public function testPrependExistingStorages()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'World');

        Storage::prepend(self::$temp.DS.'file.txt', 'Hello ');
        $this->assertStringEqualsStorage(self::$temp.DS.'file.txt', 'Hello World');
    }

    /**
     * Test untuk method Storage::prepend() - 2.
     *
     * @group system
     */
    public function testPrependNewStorages()
    {
        Storage::prepend(self::$temp.DS.'file.txt', 'Hello World');
        $this->assertStringEqualsStorage(self::$temp.DS.'file.txt', 'Hello World');
    }

    /**
     * Test untuk method Storage::rmdir() - 1.
     *
     * @group system
     */
    public function testDeleteDirectory()
    {
        mkdir(self::$temp.DS.'foo');
        file_put_contents(self::$temp.DS.'foo'.DS.'file.txt', 'Hello World');

        Storage::rmdir(self::$temp.DS.'foo');

        $this->assertTrue(! is_dir(self::$temp.DS.'foo'));
        $this->assertTrue(! is_file(self::$temp.DS.'foo'.DS.'file.txt'));
    }

    /**
     * Test untuk method Storage::rmdir() - 2.
     *
     * @group system
     */
    public function testDeleteDirectoryReturnFalseWhenNotADirectory()
    {
        mkdir(self::$temp.DS.'bar');
        file_put_contents(self::$temp.DS.'bar'.DS.'file.txt', 'Hello World');

        $this->assertFalse(Storage::rmdir(self::$temp.DS.'bar'.DS.'file.txt'));
    }

    /**
     * Test untuk method Storage::cleandir().
     *
     * @group system
     */
    public function testCleanDirectory()
    {
        mkdir(self::$temp.DS.'baz');
        file_put_contents(self::$temp.DS.'baz'.DS.'file.txt', 'Hello World');

        Storage::cleandir(self::$temp.DS.'baz');

        $this->assertTrue(is_dir(self::$temp.DS.'baz'));
        $this->assertTrue(! is_file(self::$temp.DS.'baz'.DS.'file.txt'));
    }

    /**
     * Test untuk method Storage::cpdir() - 1.
     *
     * @group system
     */
    public function testCopyDirectoryReturnsFalseIfSourceIsntDirectory()
    {
        $origin = self::$temp.DS.'breeze'.DS.'boom'.DS.'foo'.DS.'bar'.DS.'baz';
        $this->assertFalse(Storage::cpdir($origin, self::$temp));
    }

    /**
     * Test untuk method Storage::cpdir() - 2.
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

        Storage::cpdir(self::$temp.DS.'tmp', self::$temp.DS.'tmp2');

        $this->assertTrue(is_dir(self::$temp.DS.'tmp2'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp2'.DS.'foo.txt'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp2'.DS.'bar.txt'));
        $this->assertTrue(is_dir(self::$temp.DS.'tmp2'.DS.'nested'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp2'.DS.'nested'.DS.'baz.txt'));
    }

    /**
     * Test untuk method Storage::mvdir() - 1.
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

        Storage::mvdir(self::$temp.DS.'tmp2', self::$temp.DS.'tmp3');

        $this->assertTrue(is_dir(self::$temp.DS.'tmp3'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp3'.DS.'foo.txt'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp3'.DS.'bar.txt'));
        $this->assertTrue(is_dir(self::$temp.DS.'tmp3'.DS.'nested'));
        $this->assertTrue(is_file(self::$temp.DS.'tmp3'.DS.'nested'.DS.'baz.txt'));

        $this->assertFalse(is_dir(self::$temp.DS.'tmp2'));
    }

    /**
     * Test untuk method Storage::mvdir() - 2.
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

        Storage::mvdir(self::$temp.DS.'tmp4', self::$temp.DS.'tmp5', true);

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
     * Test untuk method Storage::append().
     *
     * @group system
     */
    public function testAppendAddsDataToStorage()
    {
        file_put_contents(self::$temp.DS.'file.txt', 'foo');

        $append = Storage::append(self::$temp.DS.'file.txt', 'bar');

        $this->assertTrue(mb_strlen('bar', '8bit') === $append);
        $this->assertTrue(is_file(self::$temp.DS.'file.txt'));
        $this->assertSame('foobar', file_get_contents(self::$temp.DS.'file.txt'));
    }

    /**
     * Test untuk method Storage::move().
     *
     * @group system
     */
    public function testMoveMovesStorages()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        Storage::move(self::$temp.DS.'foo.txt', self::$temp.DS.'bar.txt');

        $this->assertTrue(is_file(self::$temp.DS.'bar.txt'));
        $this->assertFalse(is_file(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::name().
     *
     * @group system
     */
    public function testNameReturnsName()
    {
        file_put_contents(self::$temp.DS.'foobar.txt', 'foo');

        $this->assertSame('foobar', Storage::name(self::$temp.DS.'foobar.txt'));
    }

    /**
     * Test untuk method Storage::extension().
     *
     * @group system
     */
    public function testExtensionReturnsExtension()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('txt', Storage::extension(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::basename().
     *
     * @group system
     */
    public function testBasenameReturnsBasename()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('foo.txt', Storage::basename(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::dirname().
     *
     * @group system
     */
    public function testDirnameReturnsDirectory()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame(self::$temp, Storage::dirname(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::type() - 1.
     *
     * @group system
     */
    public function testTypeIdentifiesStorage()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('file', Storage::type(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::type() - 2.
     *
     * @group system
     */
    public function testTypeIdentifiesDirectory()
    {
        mkdir(self::$temp.DS.'foo-dir');

        $this->assertSame('dir', Storage::type(self::$temp.DS.'foo-dir'));
    }

    /**
     * Test untuk method Storage::size().
     *
     * @group system
     */
    public function testSizeOutputsSize()
    {
        $size = file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertTrue($size === (int) Storage::size(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::mime().
     *
     * @group system
     */
    public function testMimeTypeOutputsMimeType()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('text/plain', Storage::mime(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::glob().
     *
     * @group system
     */
    public function testGlobFindsStorages()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');
        file_put_contents(self::$temp.DS.'bar.txt', 'bar');

        $glob = Storage::glob(self::$temp.DS.'*.txt');

        $this->assertTrue(in_array(self::$temp.DS.'foo.txt', $glob));
        $this->assertTrue(in_array(self::$temp.DS.'bar.txt', $glob));
    }

    /**
     * Test untuk method Storage::mkdir().
     *
     * @group system
     */
    public function testMakeDirectory()
    {
        $this->assertTrue(Storage::mkdir(self::$temp.DS.'created'));
        $this->assertTrue(is_dir(self::$temp.DS.'created'));
    }

    /**
     * Test untuk method Storage::cpdir().
     *
     * @group system
     */
    public function testCopyCopiesStorageProperly()
    {
        $data = 'contents';
        mkdir(self::$temp.DS.'text');
        file_put_contents(self::$temp.DS.'text'.DS.'foo.txt', $data);

        Storage::copy(self::$temp.DS.'text'.DS.'foo.txt', self::$temp.DS.'text'.DS.'foo2.txt');

        $this->assertTrue(is_file(self::$temp.DS.'text'.DS.'foo2.txt'));
        $this->assertTrue($data === file_get_contents(self::$temp.DS.'text'.DS.'foo2.txt'));
    }

    /**
     * Test untuk method Storage::isfile().
     *
     * @group system
     */
    public function testIsStorageChecksStoragesProperly()
    {
        mkdir(self::$temp.DS.'help');
        file_put_contents(self::$temp.DS.'help'.DS.'foo.txt', 'contents');

        $this->assertTrue(Storage::isfile(self::$temp.DS.'help'.DS.'foo.txt'));
        $this->assertFalse(Storage::isfile(self::$temp.DS.'.'.DS.'help'));
    }

    /**
     * Test untuk method Storage::hash().
     *
     * @group system
     */
    public function testHash()
    {
        file_put_contents(self::$temp.DS.'foo.txt', 'foo');

        $this->assertSame('acbd18db4cc2f85cedef654fccc4a4d8', Storage::hash(self::$temp.DS.'foo.txt'));
    }

    /**
     * Test untuk method Storage::protect().
     *
     * @group system
     */
    public function testProtect()
    {
        $dir = path('storage').'protects'.DS;

        Storage::mkdir($dir);
        $this->assertTrue(is_file($dir.'index.html'));
        Storage::delete($dir.'index.html');

        Storage::put($dir.'foo.txt', '');
        $this->assertTrue(is_file($dir.'index.html'));
        Storage::rmdir($dir);
    }
}
