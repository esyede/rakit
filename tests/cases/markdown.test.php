<?php

defined('DS') or exit('No direct access.');

class MarkdownTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Temp markdown file path.
     *
     * @var string
     */
    protected $tempFile;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->tempFile = sys_get_temp_dir() . DS . 'test_markdown_' . time() . '.md';
        file_put_contents($this->tempFile, "# Test Header\n\nThis is a test.");
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        // Reset factory singleton and properties
        $reflection = new \ReflectionClass('\System\Markdown');
        $factory = $reflection->getProperty('factory');
        /** @disregard */
        $factory->setAccessible(true);
        $factory->setValue(null);
    }

    /**
     * Test factory returns Markdown instance.
     */
    public function testFactory()
    {
        $markdown = \System\Markdown::factory();
        $this->assertInstanceOf('\System\Markdown', $markdown);
    }

    /**
     * Test factory returns singleton.
     */
    public function testFactorySingleton()
    {
        $markdown1 = \System\Markdown::factory();
        $markdown2 = \System\Markdown::factory();
        $this->assertSame($markdown1, $markdown2);
    }

    /**
     * Test parse basic markdown.
     */
    public function testParseBasic()
    {
        $input = "# Header\n\nParagraph.";
        $expected = "<h1>Header</h1>\n<p>Paragraph.</p>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse headers.
     */
    public function testParseHeaders()
    {
        $input = "# H1\n## H2\n### H3\n#### H4\n##### H5\n###### H6";
        $expected = "<h1>H1</h1>\n<h2>H2</h2>\n<h3>H3</h3>\n<h4>H4</h4>\n<h5>H5</h5>\n<h6>H6</h6>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse emphasis.
     */
    public function testParseEmphasis()
    {
        $input = "*italic* **bold**";
        $expected = "<p><em>italic</em> <strong>bold</strong></p>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse lists.
     */
    public function testParseLists()
    {
        $input = "- Item 1\n- Item 2\n\n1. Numbered\n2. Item";
        $expected = "<ul>\n<li>Item 1</li>\n<li>Item 2</li>\n</ul>\n<ol>\n<li>Numbered</li>\n<li>Item</li>\n</ol>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse links.
     */
    public function testParseLinks()
    {
        $input = "[Link](http://example.com)";
        $expected = "<p><a href=\"http://example.com\">Link</a></p>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse code.
     */
    public function testParseCode()
    {
        $input = "`inline code`";
        $expected = "<p><code>inline code</code></p>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse fenced code.
     */
    public function testParseFencedCode()
    {
        $input = "```\ncode block\n```";
        $expected = "<pre><code>code block</code></pre>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse blockquote.
     */
    public function testParseBlockquote()
    {
        $input = "> Quote";
        $expected = "<blockquote>\n<p>Quote</p>\n</blockquote>";
        $output = \System\Markdown::parse($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test render file.
     */
    public function testRender()
    {
        $output = \System\Markdown::render($this->tempFile);
        $expected = "<h1>Test Header</h1>\n<p>This is a test.</p>";
        $this->assertEquals($expected, $output);
    }

    /**
     * Test breaks configuration.
     */
    public function testBreaks()
    {
        $markdown = new \System\Markdown();
        $markdown->breaks(true);

        $reflection = new \ReflectionClass($markdown);
        $breaks = $reflection->getProperty('breaks');
        /** @disregard */
        $breaks->setAccessible(true);
        $this->assertTrue($breaks->getValue($markdown));
    }

    /**
     * Test escaping configuration.
     */
    public function testEscaping()
    {
        $markdown = new \System\Markdown();
        $markdown->escaping(true);

        $reflection = new \ReflectionClass($markdown);
        $escaping = $reflection->getProperty('escaping');
        /** @disregard */
        $escaping->setAccessible(true);
        $this->assertTrue($escaping->getValue($markdown));
    }

    /**
     * Test linkify configuration.
     */
    public function testLinkify()
    {
        $markdown = new \System\Markdown();
        $markdown->linkify(false);

        $reflection = new \ReflectionClass($markdown);
        $linking = $reflection->getProperty('linking');
        /** @disregard */
        $linking->setAccessible(true);
        $this->assertFalse($linking->getValue($markdown));
    }

    /**
     * Test safety configuration.
     */
    public function testSafety()
    {
        $markdown = new \System\Markdown();
        $markdown->safety(true);

        $reflection = new \ReflectionClass($markdown);
        $safety = $reflection->getProperty('safety');
        /** @disregard */
        $safety->setAccessible(true);
        $this->assertTrue($safety->getValue($markdown));
    }

    /**
     * Test parse with breaks enabled.
     */
    public function testParseWithBreaks()
    {
        $markdown = new \System\Markdown();
        $markdown->breaks(true);

        $input = "Line 1\nLine 2";
        $expected = "<p>Line 1<br />\nLine 2</p>";
        $output = $markdown->translate($input);
        $this->assertEquals($expected, $output);
    }

    /**
     * Test parse with escaping enabled.
     */
    public function testParseWithEscaping()
    {
        $markdown = new \System\Markdown();
        $markdown->escaping(true);

        $input = "<script>alert('xss')</script>";
        $output = $markdown->translate($input);
        $this->assertContains('&lt;script&gt;', $output);
    }

    /**
     * Test parse with linkify enabled.
     */
    public function testParseWithLinkify()
    {
        $markdown = new \System\Markdown();
        $markdown->linkify(true);

        $input = "Visit http://example.com";
        $output = $markdown->translate($input);
        $this->assertContains('<a href="http://example.com">http://example.com</a>', $output);
    }

    /**
     * Test parse with safety enabled.
     */
    public function testParseWithSafety()
    {
        $markdown = new \System\Markdown();
        $markdown->escaping(false); // Allow HTML parsing
        $markdown->safety(true);

        $input = "[Link][ref]\n\n[ref]: vbscript:alert('xss')";
        $output = $markdown->translate($input);
        $this->assertContains('vbscript%3Aalert', $output);
    }
}
