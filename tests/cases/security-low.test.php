<?php

defined('DS') or exit('No direct access.');

use System\Image;
use System\Foundation\Http\Upload;

class SecurityLowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Run the upload checks for a target name.
     *
     * @param string $name
     *
     * @return string|null the refusal, or NULL when accepted
     */
    protected function refusal($name)
    {
        $path = path('storage').'test.png';
        $upload = new Upload($path, $name, 'image/png', filesize($path), UPLOAD_ERR_OK, true);
        $validate = new \ReflectionMethod($upload, 'validate_upload');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $validate->setAccessible(true);

        try {
            $validate->invoke($upload, sys_get_temp_dir(), $name);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Server configuration files, executable and script-carrying names are refused.
     *
     * @group system
     */
    public function testDangerousUploadNamesAreRefused()
    {
        // .user.ini: its auto_prepend_file runs any file under PHP-FPM.
        $refused = [
            '.user.ini', 'php.ini', 'shell.phps', 'shell.php2', 'shell.pgif',
            'shell.php.png', 'page.xml', 'page.xsl', 'page.mht', 'movie.swf', 'icon.svg',
        ];

        foreach ($refused as $name) {
            $this->assertNotNull($this->refusal($name), 'Accepted: '.$name);
        }

        $this->assertNull($this->refusal('holiday.png'));

        // A leading dot is dropped, so no upload lands as a dotfile.
        $upload = new Upload(path('storage').'test.png', 'x.png', 'image/png', null, UPLOAD_ERR_OK, true);
        $name = new \ReflectionMethod($upload, 'getName');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $name->setAccessible(true);
        $this->assertEquals('htaccess', $name->invoke($upload, '.htaccess'));
    }

    /**
     * An image whose header declares too many pixels is refused before decoding.
     *
     * @group system
     */
    public function testDecompressionBombsAreRefused()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        // A few bytes of PNG header declaring 100000 x 100000 pixels: 40 GB once decoded.
        $ihdr = 'IHDR'.pack('NN', 100000, 100000).chr(8).chr(2).chr(0).chr(0).chr(0);
        $png = "\\x89PNG\\r\\n\\x1a\\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
        $png = str_replace(['\\x89', '\\r', '\\n', '\\x1a'], ["\x89", "\r", "\n", "\x1a"], $png);

        // Image takes paths relative to the project root.
        $relative = substr(path('storage'), strlen(path('base'))).'bomb.png';
        $path = path('base').$relative;
        file_put_contents($path, $png);

        try {
            Image::open($relative);
            $this->fail('A decompression bomb was loaded');
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            unlink($path);
            throw $e;
        } catch (\Exception $e) {
            $this->assertContains('Image too large', $e->getMessage());
        }

        unlink($path);
        $this->assertInstanceOf('System\Image', Image::open(substr(path('storage'), strlen(path('base'))).'test.png'));
    }
}
