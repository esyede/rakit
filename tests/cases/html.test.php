<?php

defined('DS') or exit('No direct script access.');

use System\Request;
use System\Foundation\Http\Request as FoundationRequest;

class HtmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        URL::$base = null;
        Config::set('application.url', 'http://localhost');
        Config::set('application.index', 'index.php');
        Router::$names = [];
        Router::$routes = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('application.url', '');
        Config::set('application.index', 'index.php');
        Router::$names = [];
        Router::$routes = [];
    }

    /**
     * Test untuk method HTML::script().
     *
     * @group system
     */
    public function testGeneratingScript()
    {
        $html1 = HTML::script('a.js');
        $html2 = HTML::script('http://site.com/a.js');
        $html3 = HTML::script('a.js', ['type' => 'text/javascript']);

        $out1 = '<script src="http://localhost/assets/a.js"></script>';
        $out2 = '<script src="http://site.com/a.js"></script>';
        $out3 = '<script src="http://localhost/assets/a.js" type="text/javascript"></script>';

        $this->assertEquals($out1.PHP_EOL, $html1);
        $this->assertEquals($out2.PHP_EOL, $html2);
        $this->assertEquals($out3.PHP_EOL, $html3);
    }

    /**
     * Test untuk method HTML::style().
     *
     * @group system
     */
    public function testGeneratingStyle()
    {
        $html1 = HTML::style('a.css');
        $html2 = HTML::style('http://site.com/a.css');
        $html3 = HTML::style('a.css', ['media' => 'screen']);

        $out1 = '<link href="http://localhost/assets/a.css" media="all" type="text/css" rel="stylesheet">';
        $out2 = '<link href="http://site.com/a.css" media="all" type="text/css" rel="stylesheet">';
        $out3 = '<link href="http://localhost/assets/a.css" media="screen" type="text/css" rel="stylesheet">';

        $this->assertEquals($out1.PHP_EOL, $html1);
        $this->assertEquals($out2.PHP_EOL, $html2);
        $this->assertEquals($out3.PHP_EOL, $html3);
    }

    /**
     * Test untuk method HTML::span().
     *
     * @group system
     */
    public function testGeneratingSpan()
    {
        $this->assertEquals('<span>y</span>', HTML::span('y'));
        $this->assertEquals('<span class="x">y</span>', HTML::span('y', ['class' => 'x']));
    }

    /**
     * Test untuk method HTML::link().
     *
     * @group system
     */
    public function testGeneratingLink()
    {
        $html1 = HTML::link('z');
        $html2 = HTML::link('z', 'n');
        $html3 = HTML::link('z', 'n', ['class' => 'x']);
        $html4 = HTML::link('http://site.com', 'g');

        $this->assertEquals('<a href="http://localhost/index.php/z">http://localhost/index.php/z</a>', $html1);
        $this->assertEquals('<a href="http://localhost/index.php/z">n</a>', $html2);
        $this->assertEquals('<a href="http://localhost/index.php/z" class="x">n</a>', $html3);
        $this->assertEquals('<a href="http://site.com">g</a>', $html4);
    }

    /**
     * Test untuk method HTML::link_to() - 2 (https).
     *
     * @group system
     */
    public function testGeneratingLinkToSecure()
    {
        $this->setServerVar('HTTPS', 'on');
        $html1 = HTML::link('b');
        $html2 = HTML::link('b', 'c');
        $html3 = HTML::link('b', 'c', ['class' => 'x']);
        $html4 = HTML::link('https://site.com', 'g');
        $this->setServerVar('HTTPS', 'off');

        $this->assertEquals('<a href="https://localhost/index.php/b">https://localhost/index.php/b</a>', $html1);
        $this->assertEquals('<a href="https://localhost/index.php/b">c</a>', $html2);
        $this->assertEquals('<a href="https://localhost/index.php/b" class="x">c</a>', $html3);
        $this->assertEquals('<a href="https://site.com">g</a>', $html4);
    }

    /**
     * Test untuk method HTML::link_to_asset().
     *
     * @group system
     */
    public function testGeneratingAssetLink()
    {
        $html1 = HTML::link_to_asset('a.css');
        $html2 = HTML::link_to_asset('a.css', 'b');
        $html3 = HTML::link_to_asset('a.css', 'b', ['class' => 'x']);
        $html4 = HTML::link_to_asset('https://site.com/g.jpg', 'g');

        $this->assertEquals('<a href="http://localhost/assets/a.css">http://localhost/assets/a.css</a>', $html1);
        $this->assertEquals('<a href="http://localhost/assets/a.css">b</a>', $html2);
        $this->assertEquals('<a href="http://localhost/assets/a.css" class="x">b</a>', $html3);
        $this->assertEquals('<a href="https://site.com/g.jpg">g</a>', $html4);
    }

    /**
     * Test untuk method HTML::link_to_asset() - 2 (https).
     *
     * @group system
     */
    public function testGeneratingAssetLinkToSecure()
    {
        $this->setServerVar('HTTPS', 'on');
        $html1 = HTML::link_to_asset('a.css');
        $html2 = HTML::link_to_asset('a.css', 'b');
        $html3 = HTML::link_to_asset('a.css', 'b', ['class' => 'x']);
        $html4 = HTML::link_to_asset('https://site.com/g.jpg', 'g');
        $this->setServerVar('HTTPS', 'off');

        $this->assertEquals('<a href="https://localhost/assets/a.css">https://localhost/assets/a.css</a>', $html1);
        $this->assertEquals('<a href="https://localhost/assets/a.css">b</a>', $html2);
        $this->assertEquals('<a href="https://localhost/assets/a.css" class="x">b</a>', $html3);
        $this->assertEquals('<a href="https://site.com/g.jpg">g</a>', $html4);
    }

    /**
     * Test untuk method HTML::link_to_route().
     *
     * @group system
     */
    public function testGeneratingLinkToRoute()
    {
        Route::get('b', ['as' => 'b', function () {
            return 'b page';
        }]);

        $html1 = HTML::link_to_route('b');
        $html2 = HTML::link_to_route('b', 'c');
        $html3 = HTML::link_to_route('b', 'c', [], ['class' => 'x']);

        $this->assertEquals('<a href="http://localhost/index.php/b">http://localhost/index.php/b</a>', $html1);
        $this->assertEquals('<a href="http://localhost/index.php/b">c</a>', $html2);
        $this->assertEquals('<a href="http://localhost/index.php/b" class="x">c</a>', $html3);
    }

    /**
     * Test untuk method HTML::link_to_action().
     *
     * @group system
     */
    public function testGeneratingLinkToAction()
    {
        $html1 = HTML::link_to_action('a@b');
        $html2 = HTML::link_to_action('a@b', 'c');
        $html3 = HTML::link_to_action('a@b', 'c', [], ['class' => 'x']);

        $this->assertEquals('<a href="http://localhost/index.php/a/b">http://localhost/index.php/a/b</a>', $html1);
        $this->assertEquals('<a href="http://localhost/index.php/a/b">c</a>', $html2);
        $this->assertEquals('<a href="http://localhost/index.php/a/b" class="x">c</a>', $html3);
    }

    /**
     * Test untuk method HTML::ul() dan HTML::ol().
     *
     * @group system
     */
    public function testGeneratingListing()
    {
        $list = ['b', 'c' => ['d', 'e']];

        $html1 = HTML::ul($list);
        $html2 = HTML::ul($list, ['class' => 'x']);
        $html3 = HTML::ol($list);
        $html4 = HTML::ol($list, ['class' => 'x']);

        $this->assertEquals('<ul><li>b</li><li>c<ul><li>d</li><li>e</li></ul></li></ul>', $html1);
        $this->assertEquals('<ul class="x"><li>b</li><li>c<ul><li>d</li><li>e</li></ul></li></ul>', $html2);
        $this->assertEquals('<ol><li>b</li><li>c<ol><li>d</li><li>e</li></ol></li></ol>', $html3);
        $this->assertEquals('<ol class="x"><li>b</li><li>c<ol><li>d</li><li>e</li></ol></li></ol>', $html4);
    }

    /**
     * Test untuk method HTML::dl().
     *
     * @group system
     */
    public function testGeneratingDefinition()
    {
        $definition = ['a' => 'b', 'c' => 'd'];

        $html1 = HTML::dl($definition);
        $html2 = HTML::dl($definition, ['class' => 'x']);

        $this->assertEquals('<dl><dt>a</dt><dd>b</dd><dt>c</dt><dd>d</dd></dl>', $html1);
        $this->assertEquals('<dl class="x"><dt>a</dt><dd>b</dd><dt>c</dt><dd>d</dd></dl>', $html2);
    }

    /**
     * Test untuk method HTML::image().
     *
     * @group system
     */
    public function testGeneratingAssetLinkImage()
    {
        $html1 = HTML::image('a.jpg');
        $html2 = HTML::image('a.jpg', 'b');
        $html3 = HTML::image('a.jpg', 'b', ['class' => 'x']);
        $html4 = HTML::image('https://site.com/g.jpg', 'g');

        $this->assertEquals('<img src="http://localhost/assets/a.jpg" alt="">', $html1);
        $this->assertEquals('<img src="http://localhost/assets/a.jpg" alt="b">', $html2);
        $this->assertEquals('<img src="http://localhost/assets/a.jpg" class="x" alt="b">', $html3);
        $this->assertEquals('<img src="https://site.com/g.jpg" alt="g">', $html4);
    }

    /**
     * Helper: set variabel $_SERVER.
     *
     * @param string $key
     * @param mixed  $value
     */
    protected function setServerVar($key, $value)
    {
        $_SERVER[$key] = $value;

        $this->restartRequest();
    }

    /**
     * Inisialisasi ulang global request.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        Request::$foundation = FoundationRequest::createFromGlobals();
    }
}
