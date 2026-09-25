<?php

defined('DS') or exit('No direct access.');

use System\Console\Fiddle\Parser;
use System\Console\Fiddle\Inspector;
use System\Console\Fiddle\Readline;
use System\Console\Fiddle\Inline;

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

        $this->assertEquals(['echo "hello";'], $parser->statements('echo "hello";'));
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
        $this->assertEmpty($parser->statements('$a = "not closed'));
        $this->assertEmpty($parser->statements('/* comment not closed'));
    }

    /**
     * A semicolon inside a string is not a statement boundary.
     *
     * @group system
     */
    public function testSemicolonInsideStringIsNotABoundary()
    {
        $parser = new Parser();
        $statements = $parser->statements('$a = "one; two";');

        $this->assertCount(1, $statements);
        $this->assertContains('one; two', $statements[0]);
    }

    /**
     * An escaped quote does not close the string.
     *
     * @group system
     */
    public function testEscapedQuoteInsideString()
    {
        $parser = new Parser();
        $statements = $parser->statements('$a = "he said \\"hello\\";";');

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

        $statements = $parser->statements("// comment\n\$a = 1;");
        $this->assertContains('$a = 1;', implode('', $statements));

        $statements = $parser->statements('/* comment */ $a = 1;');
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
        $statements = $parser->statements("\$a = <<<EOT\nhello; world\nEOT;\n");

        $this->assertCount(1, $statements);
        $this->assertContains('hello; world', $statements[0]);
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
            ["class_alias('System\\Str', 'Str');"],
            $parser->statements('use System\Str;')
        );

        $this->assertEquals(
            ["class_alias('System\\Str', 'S');"],
            $parser->statements('use System\Str as S;')
        );

        $this->assertEquals(
            ["class_alias('\\System\\Arr', 'Arr');"],
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

        $this->assertContains('hello', $this->plain($inspector->dump('hello')));
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
        $out = $this->plain($inspector->dump(['a' => 1, 'b' => 'two']));

        $this->assertContains('a', $out);
        $this->assertContains('1', $out);
        $this->assertContains('two', $out);
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

    /**
     * A collection shows its items, never an empty object.
     *
     * @group system
     */
    public function testInspectorDumpsCollectionItems()
    {
        $inspector = new Inspector();
        $out = $this->plain($inspector->dump(new \System\Collection([['id' => 1], ['id' => 2]])));

        $this->assertContains('Collection(2)', $out);
        $this->assertContains('id', $out);
    }

    /**
     * A model shows its serialized attributes, not its internals.
     *
     * @group system
     */
    public function testInspectorDumpsModelAttributes()
    {
        $inspector = new Inspector();
        $model = new FiddleModelProbe(['name' => 'Budi', 'password' => 'x']);
        $out = $this->plain($inspector->dump($model));

        $this->assertContains('Budi', $out);
        $this->assertNotContains('password', $out);
        $this->assertNotContains('original', $out);
    }

    /**
     * An object without public properties is dumped via reflection.
     *
     * @group system
     */
    public function testInspectorFallsBackToReflection()
    {
        $inspector = new Inspector();
        $vars = $inspector->object_vars(new FiddleProtectedProbe());

        $this->assertEquals(['secret' => 'hidden-value'], $vars);
    }

    /**
     * Tab-completion offers class and function names.
     *
     * @group system
     */
    public function testReadlineCompletesNames()
    {
        if (! function_exists('readline_completion_function')) {
            $this->markTestSkipped('readline extension is not available');
        }

        $readline = new Readline(fopen('php://memory', 'r+'));

        $this->assertContains('config', $readline->complete('confi', 0));
    }

    /**
     * The inline REPL evaluates statements and prints their value.
     *
     * @group system
     */
    public function testInlineEvaluatesStatement()
    {
        $out = $this->plain($this->run_inline("1 + 1;\nquit;\n"));

        $this->assertContains('// 2', $out);
    }

    /**
     * A failed statement does not end the inline session.
     *
     * @group system
     */
    public function testInlineKeepsSessionOnException()
    {
        $out = $this->plain($this->run_inline("throw new Exception('boom');\n1 + 1;\nquit;\n"));

        $this->assertContains('Exception: boom', $out);
        $this->assertContains('// 2', $out);
    }

    /**
     * Starting hooks export variables into the inline scope.
     *
     * @group system
     */
    public function testInlineRunsStartingHooks()
    {
        $out = $this->plain($this->run_inline("\$probe;\nquit;\n", function ($worker, $vars) {
            $worker->set('probe', 42);
        }));

        $this->assertContains('// 42', $out);
    }

    /**
     * Run the inline REPL against the given input and capture its output.
     *
     * @param string $input
     * @param mixed  $starting
     *
     * @return string
     */
    protected function run_inline($input, $starting = null)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $input);
        rewind($stream);

        $inline = new Inline('> ');
        $inline->input($stream);

        if (! is_null($starting)) {
            $inline->starting($starting);
        }

        ob_start();
        $inline->start();
        $output = ob_get_clean();
        fclose($stream);

        return $output;
    }
}

class FiddleModelProbe extends \System\Database\Facile\Model
{
    public static $table = 'fiddle_model_probe';

    public static $timestamps = false;

    public static $guarded = [];

    public static $hidden = ['password'];
}

class FiddleProtectedProbe
{
    protected $secret = 'hidden-value';
}
