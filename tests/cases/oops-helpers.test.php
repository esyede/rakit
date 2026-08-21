<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Foundation\Oops\Dumper;
use System\Foundation\Oops\Helpers;

/**
 * Covers the pure helpers of the debugger: HTML escaping, editor links, error
 * type names, suggestions and the variable dumper.
 */
class OopsHelpersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Configuration in place before the test ran.
     *
     * @var mixed
     */
    private $editor;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->editor = Config::get('debugger.editor');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('debugger.editor', $this->editor);
    }

    // -------------------------------------------------------------------------
    // Escaping
    // -------------------------------------------------------------------------

    /**
     * Test for Helpers::escapeHtml().
     *
     * @group system
     */
    public function testEscapeHtml()
    {
        $this->assertEquals('&lt;b&gt;halo&lt;/b&gt;', Helpers::escapeHtml('<b>halo</b>'));
        $this->assertEquals('&quot;a&quot; &amp; &#039;b&#039;', Helpers::escapeHtml('"a" & \'b\''));
        $this->assertEquals('', Helpers::escapeHtml(null));
        $this->assertEquals('123', Helpers::escapeHtml(123));
    }

    /**
     * Test for Helpers::formatHtml() - each '%' takes the next argument.
     *
     * @group system
     */
    public function testFormatHtml()
    {
        $this->assertEquals(
            '<b>&lt;script&gt;</b>',
            Helpers::formatHtml('<b>%</b>', '<script>')
        );

        $this->assertEquals(
            'a=1, b=&amp;',
            Helpers::formatHtml('a=%, b=%', 1, '&')
        );

        $this->assertEquals('tanpa argumen', Helpers::formatHtml('tanpa argumen'));
    }

    /**
     * Test for Helpers::fixEncoding() - invalid bytes are dropped.
     *
     * @group system
     */
    public function testFixEncoding()
    {
        $this->assertEquals('halo', Helpers::fixEncoding('halo'));
        $this->assertEquals('café', Helpers::fixEncoding('café'));

        // The lone lead byte is dropped, the rest of the string survives.
        $this->assertEquals('ha(lo', Helpers::fixEncoding("ha\xC3\x28lo"));
        $this->assertEquals('halo', Helpers::fixEncoding("halo\xFF"));
    }

    // -------------------------------------------------------------------------
    // Editor links
    // -------------------------------------------------------------------------

    /**
     * Test for Helpers::editorUri() with the built-in presets.
     *
     * @group system
     */
    public function testEditorUriPresets()
    {
        Config::set('debugger.editor', 'phpstorm');
        $uri = Helpers::editorUri('/srv/app/index.php', 42);
        $this->assertStringStartsWith('phpstorm://open?file=', $uri);
        $this->assertContains('line=42', $uri);

        Config::set('debugger.editor', 'vscode');
        $uri = Helpers::editorUri('/srv/app/index.php', 7);
        $this->assertStringStartsWith('vscode://file/', $uri);
        $this->assertStringEndsWith(':7', $uri);
    }

    /**
     * A custom template is used as-is.
     *
     * @group system
     */
    public function testEditorUriCustomTemplate()
    {
        Config::set('debugger.editor', 'myeditor://%file%@%line%');

        $this->assertEquals(
            'myeditor:///srv/app/index.php@3',
            Helpers::editorUri('/srv/app/index.php', 3)
        );
    }

    /**
     * A template without the file placeholder, an empty file or a disabled
     * editor all yield nothing.
     *
     * @group system
     */
    public function testEditorUriReturnsNullWhenUnusable()
    {
        Config::set('debugger.editor', 'phpstorm');
        $this->assertNull(Helpers::editorUri(''));

        Config::set('debugger.editor', '');
        $this->assertNull(Helpers::editorUri('/srv/app/index.php'));

        Config::set('debugger.editor', 'noplaceholder://open');
        $this->assertNull(Helpers::editorUri('/srv/app/index.php'));
    }

    /**
     * A line number below one is clamped.
     *
     * @group system
     */
    public function testEditorUriClampsLineNumber()
    {
        Config::set('debugger.editor', 'phpstorm');

        $this->assertContains('line=1', Helpers::editorUri('/srv/app/index.php', 0));
        $this->assertContains('line=1', Helpers::editorUri('/srv/app/index.php', -5));
    }

    /**
     * A relative path is resolved against the application base.
     *
     * @group system
     */
    public function testEditorUriResolvesRelativePath()
    {
        Config::set('debugger.editor', 'myeditor://%file%');
        $uri = Helpers::editorUri('system/str.php', 1);

        $this->assertContains(str_replace('%2F', '/', rawurlencode(str_replace('\\', '/', path('base')))), $uri);
        $this->assertContains('system/str.php', $uri);
    }

    // -------------------------------------------------------------------------
    // Misc helpers
    // -------------------------------------------------------------------------

    /**
     * Test for Helpers::errorTypeToString().
     *
     * @group system
     */
    public function testErrorTypeToString()
    {
        $this->assertEquals('Fatal Error', Helpers::errorTypeToString(E_ERROR));
        $this->assertEquals('Warning', Helpers::errorTypeToString(E_WARNING));
        $this->assertEquals('Notice', Helpers::errorTypeToString(E_NOTICE));
        $this->assertEquals('Deprecated', Helpers::errorTypeToString(E_DEPRECATED));
        $this->assertEquals('User Deprecated', Helpers::errorTypeToString(E_USER_DEPRECATED));

        // E_STRICT is deprecated as a constant since PHP 8.4, so its value is
        // spelled out here as well.
        $this->assertEquals('Strict Standards', Helpers::errorTypeToString(2048));
        $this->assertEquals('Unknown error', Helpers::errorTypeToString(999999));
    }

    /**
     * Test for Helpers::getClass() with an anonymous class.
     *
     * @group system
     */
    public function testGetClass()
    {
        $this->assertEquals('stdClass', Helpers::getClass(new \stdClass()));
        $this->assertEquals('OopsHelpersTest', Helpers::getClass($this));
    }

    /**
     * Test for Helpers::getSuggestion() - finds the closest match.
     *
     * @group system
     */
    public function testGetSuggestion()
    {
        $items = ['name', 'email', 'password', 'address'];

        $this->assertEquals('email', Helpers::getSuggestion($items, 'emial'));
        $this->assertEquals('password', Helpers::getSuggestion($items, 'passwrd'));
        $this->assertNull(Helpers::getSuggestion($items, 'zzzzzzzzzzzz'));

        // An exact match is not a suggestion.
        $this->assertNull(Helpers::getSuggestion(['name'], 'name'));
    }

    /**
     * Test for Helpers::findTrace().
     *
     * @group system
     */
    public function testFindTrace()
    {
        $trace = [
            ['function' => 'first', 'file' => 'a.php'],
            ['function' => 'render', 'class' => 'System\View', 'file' => 'b.php'],
            ['function' => 'last', 'file' => 'c.php'],
        ];

        $index = null;
        $found = Helpers::findTrace($trace, 'System\View::render', $index);

        $this->assertEquals('b.php', $found['file']);
        $this->assertEquals(1, $index);

        $this->assertNull(Helpers::findTrace($trace, 'System\View::nothing'));
        $this->assertEquals('a.php', Helpers::findTrace($trace, 'first')['file']);
    }

    /**
     * Test for Helpers::getSource() - falls back to a CLI description.
     *
     * @group system
     */
    public function testGetSource()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        unset($_SERVER['REQUEST_URI']);

        $this->assertStringStartsWith('CLI (PID:', Helpers::getSource());

        $_SERVER['REQUEST_URI'] = '/halo?a=1';
        $_SERVER['HTTP_HOST'] = 'rakit.test';
        $this->assertEquals('http://rakit.test/halo?a=1', Helpers::getSource());

        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https://rakit.test/halo?a=1', Helpers::getSource());

        unset($_SERVER['REQUEST_URI'], $_SERVER['HTTP_HOST'], $_SERVER['HTTPS']);

        if (!is_null($uri)) {
            $_SERVER['REQUEST_URI'] = $uri;
        }
    }

    /**
     * Test for Helpers::isAjax().
     *
     * @group system
     */
    public function testIsAjax()
    {
        unset($_SERVER['HTTP_X_OOPS_AJAX']);
        $this->assertFalse(Helpers::isAjax());

        $_SERVER['HTTP_X_OOPS_AJAX'] = 'abcdefghij';
        $this->assertEquals(1, Helpers::isAjax());

        $_SERVER['HTTP_X_OOPS_AJAX'] = 'tooshort';
        $this->assertEquals(0, Helpers::isAjax());

        unset($_SERVER['HTTP_X_OOPS_AJAX']);
    }

    // -------------------------------------------------------------------------
    // Dumper
    // -------------------------------------------------------------------------

    /**
     * Test for Dumper::toText() with scalars.
     *
     * @group system
     */
    public function testDumperToTextScalars()
    {
        $this->assertContains('halo', Dumper::toText('halo'));
        $this->assertContains('123', Dumper::toText(123));
        $this->assertContains('TRUE', strtoupper(Dumper::toText(true)));
        $this->assertContains('NULL', strtoupper(Dumper::toText(null)));
        $this->assertContains('1.5', Dumper::toText(1.5));
    }

    /**
     * Test for Dumper::toText() with an array.
     *
     * @group system
     */
    public function testDumperToTextArray()
    {
        $out = Dumper::toText(['a' => 1, 'b' => ['c' => 2]]);

        $this->assertContains('array', strtolower($out));
        $this->assertContains('a', $out);
        $this->assertContains('c', $out);
        $this->assertContains('2', $out);
    }

    /**
     * Test for Dumper::toText() with an object.
     *
     * @group system
     */
    public function testDumperToTextObject()
    {
        $object = new \stdClass();
        $object->name = 'Budi';

        $out = Dumper::toText($object);

        $this->assertContains('stdClass', $out);
        $this->assertContains('name', $out);
        $this->assertContains('Budi', $out);
    }

    /**
     * Test for Dumper::toHtml() - the result is HTML and escaped.
     *
     * @group system
     */
    public function testDumperToHtml()
    {
        $out = Dumper::toHtml('<script>alert(1)</script>');

        $this->assertContains('<pre', $out);
        $this->assertNotContains('<script>', $out);
        $this->assertContains('&lt;script&gt;', $out);
    }

    /**
     * Test for Dumper::toTerminal() - carries ANSI escapes.
     *
     * @group system
     */
    public function testDumperToTerminal()
    {
        $out = Dumper::toTerminal(['a' => 1]);

        $this->assertContains("\033[", $out);
        $this->assertContains('a', $out);
    }

    /**
     * The dumper must stop at the configured depth instead of recursing.
     *
     * @group system
     */
    public function testDumperRespectsDepth()
    {
        $deep = ['l1' => ['l2' => ['l3' => ['l4' => 'terlalu dalam']]]];

        $shallow = Dumper::toText($deep, [Dumper::DEPTH => 1]);
        $this->assertNotContains('terlalu dalam', $shallow);

        $full = Dumper::toText($deep, [Dumper::DEPTH => 10]);
        $this->assertContains('terlalu dalam', $full);
    }

    /**
     * A long string is truncated.
     *
     * @group system
     */
    public function testDumperTruncatesLongStrings()
    {
        $out = Dumper::toText(str_repeat('a', 500), [Dumper::TRUNCATE => 20]);

        $this->assertLessThan(500, strlen($out));
    }

    /**
     * A recursive structure must not loop forever.
     *
     * @group system
     */
    public function testDumperHandlesRecursion()
    {
        $object = new \stdClass();
        $object->self = $object;

        $out = Dumper::toText($object);

        $this->assertContains('stdClass', $out);
        $this->assertLessThan(100000, strlen($out));
    }

    /**
     * Test for Dumper::encodeString().
     *
     * @group system
     */
    public function testDumperEncodeString()
    {
        // Printable text is returned untouched.
        $this->assertEquals('halo', Dumper::encodeString('halo'));
        $this->assertEquals("baris\nbaru", Dumper::encodeString("baris\nbaru"));

        // Control and binary bytes are escaped.
        $this->assertEquals('a\x00b', Dumper::encodeString("a\x00b"));
        $this->assertEquals('a\x1bb', Dumper::encodeString("a\x1bb"));

        // And the result can be shortened.
        $out = Dumper::encodeString("\x00" . str_repeat('a', 100), 10);
        $this->assertStringEndsWith(' ... ', $out);
        $this->assertLessThan(100, strlen($out));
    }
}
