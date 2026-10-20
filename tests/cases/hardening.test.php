<?php

defined('DS') or exit('No direct access.');

use System\Blade;
use System\Input;
use System\Request;
use System\Response;
use System\RSA;
use System\Str;
use System\Routing\Route;
use System\Routing\Throttle;

class HardeningTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tear down.
     */
    public function tearDown()
    {
        Request::$route = null;
        Input::$json = null;
    }

    /**
     * Every value of a route parameter shares one throttle bucket.
     *
     * @group system
     */
    public function testThrottleIsKeyedByTheRoutePattern()
    {
        $_SERVER['REQUEST_URI'] = '/reset/aaa';
        Request::$route = new Route('POST', 'reset/(:any)', []);
        $first = Throttle::key();

        $_SERVER['REQUEST_URI'] = '/reset/bbb';
        Request::$route = new Route('POST', 'reset/(:any)', []);
        $this->assertEquals($first, Throttle::key());

        Request::$route = new Route('POST', 'login', []);
        $this->assertNotEquals($first, Throttle::key());
    }

    /**
     * The key file and the config are never served, although they sit in an allowed root.
     *
     * @group system
     */
    public function testDownloadRefusesTheKeyAndTheConfig()
    {
        foreach ([path('rakit_key'), path('app').'config'.DS.'application.php'] as $path) {
            try {
                Response::download($path);
                $this->fail('Served '.$path);
            } catch (\PHPUnit_Framework_AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->assertContains('outside allowed directory', $e->getMessage());
            }
        }

        $this->assertInstanceOf('\System\Response', Response::file(__FILE__));
    }

    /**
     * RSA defaults to OAEP, and a failed decryption says nothing about why.
     *
     * @group system
     */
    public function testRsaDefaultsToOaepWithAGenericError()
    {
        $encrypted = RSA::encrypt('secret');
        $this->assertEquals('secret', RSA::decrypt($encrypted, OPENSSL_PKCS1_OAEP_PADDING));
        $this->assertEquals('secret', RSA::decrypt($encrypted));

        try {
            RSA::decrypt(str_repeat("\x01", strlen($encrypted)));
            $this->fail('Decrypted garbage');
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->assertEquals('Failed to decrypt the data.', $e->getMessage());
        }
    }

    /**
     * The decoded JSON body follows replace() and clear().
     *
     * @group system
     */
    public function testJsonInputMemoFollowsReplaceAndClear()
    {
        Request::foundation()->headers->set('Content-Type', 'application/json');
        Input::$json = '{"a":"one"}';
        $this->assertEquals('one', Input::get('a'));

        Input::replace(['a' => 'two']);
        $this->assertEquals('two', Input::get('a'));

        Input::clear();
        $this->assertNull(Input::get('a'));

        Request::foundation()->headers->remove('Content-Type');
    }

    /**
     * Compiling writes the file aside and renames it, leaving no temp file behind.
     *
     * @group system
     */
    public function testBladeCompileLeavesNoTempFile()
    {
        $template = path('app').'views'.DS.'hardeningprobe.blade.php';
        file_put_contents($template, 'probe {{ $n }}');

        $compiled = Blade::compiled($template);
        is_file($compiled) && unlink($compiled);

        $output = \System\View::make('hardeningprobe', ['n' => 3])->render();

        unlink($template);
        $this->assertEquals('probe 3', trim($output));
        $this->assertTrue(is_file($compiled));
        $this->assertSame([], glob($compiled.'.*.tmp'));
        unlink($compiled);
    }

    /**
     * Str::bytes() returns exactly the asked length.
     *
     * @group system
     */
    public function testBytesLength()
    {
        $this->assertEquals(32, strlen(Str::bytes(32)));
        $this->assertNotEquals(Str::bytes(16), Str::bytes(16));
    }
}
