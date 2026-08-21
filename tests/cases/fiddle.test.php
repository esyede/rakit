<?php

defined('DS') or exit('No direct access.');

use System\Console\Fiddle\Parser;
use System\Console\Fiddle\Inspector;

/**
 * Covers the statement parser and the value inspector of the interactive
 * console (fiddle).
 */
class FiddleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Strip the ANSI escapes the inspector adds.
     *
     * @param string $value
     *
     * @return string
     */
    protected function plain($value)
    {
        return preg_replace('/\033\[[0-9;]*m/', '', $value);
    }

    // -------------------------------------------------------------------------
    // Parser
    // -------------------------------------------------------------------------

    /**
     * A complete statement comes back wrapped in a return so the REPL can echo
     * the value.
     *
     * @group system
     */
    public function testSimpleStatement()
    {
        $parser = new Parser();

        $this->assertEquals(['return $a = 1;'], $parser->statements('$a = 1;'));
        $this->assertEquals(['return 1 + 1;'], $parser->statements('1 + 1;'));
    }

    /**
     * Statements that produce no value are left alone.
     *
     * @group system
     */
    public function testNonReturnableStatements()
    {
        $parser = new Parser();

        $this->assertEquals(['echo "halo";'], $parser->statements('echo "halo";'));
        $this->assertEquals(['return 1;'], $parser->statements('return 1;'));
    }

    /**
     * Several statements are split apart.
     *
     * @group system
     */
    public function testMultipleStatements()
    {
        $parser = new Parser();
        $statements = $parser->statements('$a = 1; $b = 2;');

        $this->assertCount(2, $statements);
        $this->assertContains('$a = 1;', $statements[0]);
        $this->assertContains('$b = 2;', $statements[1]);
    }

    /**
     * An unfinished statement yields nothing, so the REPL keeps reading.
     *
     * @group system
     */
    public function testIncompleteStatements()
    {
        $parser = new Parser();

        $this->assertEmpty($parser->statements('$a = [1,'));
        $this->assertEmpty($parser->statements('if (true) {'));
        $this->assertEmpty($parser->statements('$a = "belum ditutup'));
        $this->assertEmpty($parser->statements('/* komentar belum ditutup'));
    }

    /**
     * A semicolon inside a string is not a statement boundary.
     *
     * @group system
     */
    public function testSemicolonInsideStringIsNotABoundary()
    {
        $parser = new Parser();
        $statements = $parser->statements('$a = "satu; dua";');

        $this->assertCount(1, $statements);
        $this->assertContains('satu; dua', $statements[0]);
    }

    /**
     * An escaped quote does not close the string.
     *
     * @group system
     */
    public function testEscapedQuoteInsideString()
    {
        $parser = new Parser();
        $statements = $parser->statements('$a = "dia bilang \\"halo\\";";');

        $this->assertCount(1, $statements);
    }

    /**
     * A block statement is kept whole.
     *
     * @group system
     */
    public function testBlockStatement()
    {
        $parser = new Parser();
        $statements = $parser->statements('if (true) { echo 1; }');

        $this->assertCount(1, $statements);
        $this->assertContains('if (true)', $statements[0]);
        $this->assertContains('echo 1;', $statements[0]);
    }

    /**
     * A comment is consumed together with the statement.
     *
     * @group system
     */
    public function testComments()
    {
        $parser = new Parser();

        $statements = $parser->statements("// komentar\n\$a = 1;");
        $this->assertContains('$a = 1;', implode('', $statements));

        $statements = $parser->statements("/* komentar */ \$a = 1;");
        $this->assertContains('$a = 1;', implode('', $statements));
    }

    /**
     * A heredoc is kept whole.
     *
     * @group system
     */
    public function testHeredoc()
    {
        $parser = new Parser();
        $statements = $parser->statements("\$a = <<<EOT\nhalo; dunia\nEOT;\n");

        $this->assertCount(1, $statements);
        $this->assertContains('halo; dunia', $statements[0]);
    }

    /**
     * An import is rewritten into a class_alias() call, because the REPL
     * evaluates every statement in its own scope.
     *
     * @group system
     */
    public function testUseIsRewrittenToClassAlias()
    {
        $parser = new Parser();

        $this->assertEquals(
            ["return class_alias('System\\Str', 'Str');"],
            $parser->statements('use System\Str;')
        );

        $this->assertEquals(
            ["return class_alias('System\\Str', 'S');"],
            $parser->statements('use System\Str as S;')
        );

        $this->assertEquals(
            ["return class_alias('\\System\\Arr', 'Arr');"],
            $parser->statements('use \System\Arr;')
        );
    }

    /**
     * The 'use' clause of a closure is not an import and must be left alone.
     *
     * @group system
     */
    public function testClosureUseClauseIsNotRewritten()
    {
        $parser = new Parser();
        $statements = $parser->statements('$f = function () use ($x) { return $x; };');

        $this->assertCount(1, $statements);
        $this->assertNotContains('class_alias', $statements[0]);
        $this->assertContains('use ($x)', $statements[0]);
    }

    /**
     * A closure assignment is one statement, the inner semicolons do not split
     * it.
     *
     * @group system
     */
    public function testClosureAssignment()
    {
        $parser = new Parser();
        $statements = $parser->statements('$f = function ($a) { $b = $a + 1; return $b; };');

        $this->assertCount(1, $statements);
        $this->assertContains('return $b;', $statements[0]);
    }

    // -------------------------------------------------------------------------
    // Inspector
    // -------------------------------------------------------------------------

    /**
     * Test for Inspector::dump() with scalars.
     *
     * @group system
     */
    public function testInspectorDumpsScalars()
    {
        $inspector = new Inspector();

        $this->assertContains('halo', $this->plain($inspector->dump('halo')));
        $this->assertContains('123', $this->plain($inspector->dump(123)));
        $this->assertContains('1.5', $this->plain($inspector->dump(1.5)));
        $this->assertContains('true', strtolower($this->plain($inspector->dump(true))));
        $this->assertContains('null', strtolower($this->plain($inspector->dump(null))));
    }

    /**
     * Test for Inspector::dump() with an array.
     *
     * @group system
     */
    public function testInspectorDumpsArray()
    {
        $inspector = new Inspector();
        $out = $this->plain($inspector->dump(['a' => 1, 'b' => 'dua']));

        $this->assertContains('a', $out);
        $this->assertContains('1', $out);
        $this->assertContains('dua', $out);
    }

    /**
     * Test for Inspector::dump() with an object.
     *
     * @group system
     */
    public function testInspectorDumpsObject()
    {
        $inspector = new Inspector();

        $object = new \stdClass();
        $object->name = 'Budi';

        $out = $this->plain($inspector->dump($object));

        $this->assertContains('stdClass', $out);
        $this->assertContains('name', $out);
        $this->assertContains('Budi', $out);
    }

    /**
     * A recursive structure must not loop forever.
     *
     * @group system
     */
    public function testInspectorHandlesRecursion()
    {
        $inspector = new Inspector();

        $object = new \stdClass();
        $object->self = $object;

        $out = $inspector->dump($object);

        $this->assertInternalType('string', $out);
        $this->assertLessThan(100000, strlen($out));
    }

    /**
     * Test for Inspector::inspect() - every line is commented out.
     *
     * @group system
     */
    public function testInspectorCommentsEveryLine()
    {
        $inspector = new Inspector();
        $out = $this->plain($inspector->inspect(['a' => 1, 'b' => 2]));

        foreach (explode("\n", trim($out)) as $line) {
            $this->assertStringStartsWith('// ', $line);
        }
    }

    /**
     * Test for Inspector::object_vars().
     *
     * @group system
     */
    public function testInspectorObjectVars()
    {
        $inspector = new Inspector();

        $object = new \stdClass();
        $object->a = 1;
        $object->b = 2;

        $this->assertEquals(['a' => 1, 'b' => 2], $inspector->object_vars($object));
    }
}
