<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\File;
use System\Foundation\Http\Upload;

class HttpFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        mkdir(sys_get_temp_dir() . '/form_test', 0777, true);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $files = glob(sys_get_temp_dir() . '/form_test/*');

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir(sys_get_temp_dir() . '/form_test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFileMustBeAnArrayOrUpload()
    {
        new File(['file' => 'foo']);
    }

    public function testShouldConvertsUploads()
    {
        $tmpFile = $this->createTempFile();
        $file = new Upload($tmpFile, basename($tmpFile), 'text/plain', 100, 0);
        $bag = new File(['file' => [
            'name' => basename($tmpFile),
            'type' => 'text/plain',
            'tmp_name' => $tmpFile,
            'error' => 0,
            'size' => 100,
        ]]);

        $this->assertEquals($file, $bag->get('file'));
    }

    public function testShouldSetEmptyUploadsToNull()
    {
        $bag = new File(['file' => [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ]]);

        $this->assertNull($bag->get('file'));
    }

    public function testShouldConvertUploadsWithPhpBug()
    {
        $tmpFile = $this->createTempFile();
        $file = new Upload($tmpFile, basename($tmpFile), 'text/plain', 100, 0);

        $bag = new File(['child' => [
            'name' => ['file' => basename($tmpFile)],
            'type' => ['file' => 'text/plain'],
            'tmp_name' => ['file' => $tmpFile],
            'error' => ['file' => 0],
            'size' => ['file' => 100],
        ]]);

        $files = $bag->all();
        $this->assertEquals($file, $files['child']['file']);
    }

    public function testShouldConvertNestedUploadsWithPhpBug()
    {
        $tmpFile = $this->createTempFile();
        $file = new Upload($tmpFile, basename($tmpFile), 'text/plain', 100, 0);

        $bag = new File(['child' => [
            'name' => ['sub' => ['file' => basename($tmpFile)]],
            'type' => ['sub' => ['file' => 'text/plain']],
            'tmp_name' => ['sub' => ['file' => $tmpFile]],
            'error' => ['sub' => ['file' => 0]],
            'size' => ['sub' => ['file' => 100]],
        ]]);

        $files = $bag->all();
        $this->assertEquals($file, $files['child']['sub']['file']);
    }

    public function testShouldNotConvertNestedUploads()
    {
        $tmpFile = $this->createTempFile();
        $file = new Upload($tmpFile, basename($tmpFile), 'text/plain', 100, 0);
        $bag = new File(['image' => ['file' => $file]]);

        $files = $bag->all();
        $this->assertEquals($file, $files['image']['file']);
    }

    protected function createTempFile()
    {
        return tempnam(sys_get_temp_dir() . '/form_test', 'FormTest');
    }
}
