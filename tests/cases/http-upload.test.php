<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Upload;

class HttpUploadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        if (!ini_get('file_uploads')) {
            $this->markTestSkipped('file_uploads is disabled in php.ini');
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function testConstructWhenFileNotExists()
    {
        $this->setExpectedException('\Exception');
        new Upload($this->getFilePath('null-not-there'), 'original.png', null);
    }

    public function testFileUploadsWithNoMimeType()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            null,
            filesize($this->getFilePath('test.png')),
            UPLOAD_ERR_OK
        );

        $this->assertEquals('application/octet-stream', $file->getClientMimeType());

        if (extension_loaded('fileinfo')) {
            $this->assertEquals('image/png', $file->getMimeType());
        }
    }

    public function testFileUploadsWithUnknownMimeType()
    {
        $file = new Upload(
            $this->getFilePath('test.unknown'),
            'original.png',
            null,
            filesize($this->getFilePath('test.unknown')),
            UPLOAD_ERR_OK
        );

        $this->assertEquals('application/octet-stream', $file->getClientMimeType());
    }

    public function testErrorIsOkByDefault()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            'image/png',
            filesize($this->getFilePath('test.png')),
            null
        );

        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
    }

    public function testGetClientOriginalName()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            'image/png',
            filesize($this->getFilePath('test.png')),
            null
        );

        $this->assertEquals('original.png', $file->getClientOriginalName());
    }

    /**
     * @expectedException \Exception
     */
    public function testMoveLocalFileIsNotAllowed()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            'image/png',
            filesize($this->getFilePath('test.png')),
            UPLOAD_ERR_OK
        );

        $moved = $file->move(__DIR__.'/Fixtures/directory');
    }

    public function testMoveLocalFileIsAllowedInTestMode()
    {
        $path = $this->getFilePath('test.copy.png');
        $target = $this->getFilePath('logs/test.copy.png');
        @unlink($path);
        @unlink($target);
        copy($this->getFilePath('test.png'), $path);

        $file = new Upload(
            $path,
            'original.png',
            'image/png',
            filesize($path),
            UPLOAD_ERR_OK,
            true
        );

        $moved = $file->move($this->getFilePath('logs'));

        $this->assertTrue(file_exists($target));
        $this->assertFalse(file_exists($path));
        $this->assertEquals(realpath($target), realpath($moved));

        @unlink($target);
    }

    public function testGetClientOriginalNameSanitizeFilename()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            '../../original.png',
            'image/png',
            filesize($this->getFilePath('test.png')),
            null
        );

        $this->assertEquals('original.png', $file->getClientOriginalName());
    }

    public function testGetSize()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            'image/png',
            filesize($this->getFilePath('test.png')),
            null
        );

        $this->assertEquals(filesize($this->getFilePath('test.png')), $file->getSize());

        $file = new Upload(
            $this->getFilePath('test'),
            'original.png',
            'image/png'
        );

        $this->assertEquals(filesize($this->getFilePath('test')), $file->getSize());
    }

    public function testGetExtension()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            null
        );

        $this->assertEquals('png', $file->getExtension());
    }

    public function testIsValid()
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            null,
            filesize($this->getFilePath('test.png')),
            UPLOAD_ERR_OK
        );

        $this->assertTrue($file->isValid());
    }

    /**
     * @dataProvider uploadErrorProvider
     */
    public function testIsInvalidOnUploadError($error)
    {
        $file = new Upload(
            $this->getFilePath('test.png'),
            'original.png',
            null,
            filesize($this->getFilePath('test.png')),
            $error
        );

        $this->assertFalse($file->isValid());
    }

    public function uploadErrorProvider()
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_EXTENSION],
        ];
    }

    private function getFilePath($path)
    {
        return path('storage') . ltrim(str_replace(['\\', '/'], [DS, DS], $path), DS);
    }
}
