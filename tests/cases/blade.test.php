<?php

defined('DS') or exit('No direct access.');

use System\Blade;
use System\Session;

class BladeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        if (!Session::started()) {
            Session::start('file');
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Session::$instance = null;
        array_map(function ($file) {
            is_file($file) && unlink($file);
        }, glob(path('storage') . 'sessions' . DS . '*.session.php'));
    }

    /**
     * Test for echo.
     *
     * @group system
     */
    public function testEchosAreConvertedProperly()
    {
        $blade1 = '{{ $a }}';
        $blade2 = '{{{ $a }}}';
        $blade3 = '{!! $a !!}';

        $out1 = '<?php echo e($a) ?>';
        $out2 = '<?php echo e($a) ?>';
        $out3 = '<?php echo $a ?>';

        $this->assertEquals($out1, Blade::translate($blade1));
        $this->assertEquals($out2, Blade::translate($blade2));
        $this->assertEquals($out3, Blade::translate($blade3));
    }

    /**
     * Test for csrf.
     *
     * @group system
     */
    public function testCsrfAreConvertedProperly()
    {
        $blade = '@csrf';
        $out = '<?php echo csrf_field() ?>';
        $this->assertEquals($out, Blade::translate($blade));
    }

    /**
     * Test for comment.
     *
     * @group system
     */
    public function testCommentsAreConvertedProperly()
    {
        $blade1 = '{{-- This is a comment --}}';
        $blade2 = "{{--\nThis is a\nmulti-line\ncomment.\n--}}";

        $out1 = '';
        $out2 = '';

        $this->assertEquals($out1, Blade::translate($blade1));
        $this->assertEquals($out2, Blade::translate($blade2));
    }

    /**
     * Test for control structures.
     *
     * @group system
     */
    public function testControlStructuresAreCreatedCorrectly()
    {
        $blade1 = "@if (true)\nfoo\n@endif";
        $blade2 = '@if (count(' . '$something' . ") > 0)\nfoo\n@endif";
        $blade3 = "@if (true)\nfoo\n@elseif (false)\nbar\n@else\nfoobar\n@endif";
        $blade4 = "@if (true)\nfoo\n@elseif (false)\nbar\n@endif";
        $blade5 = "@if (true)\nfoo\n@else\nbar\n@endif";
        $blade6 = '@unless (count(' . '$something' . ") > 0)\nfoobar\n@endunless";
        $blade7 = '@for (Foo::all() as ' . '$foo' . ")\nfoo\n@endfor";
        $blade8 = '@foreach (Foo::all() as ' . '$foo' . ")\nfoo\n@endforeach";
        $blade9 = '@forelse (Foo::all() as ' . '$foo' . ")\nfoo\n@empty\nbar\n@endforelse";
        $blade10 = "@while (true)\nfoo\n@endwhile";
        $blade11 = "@while (Foo::bar())\nfoo\n@endwhile";
        $blade12 = "@guest\nfoo\n@endguest";
        $blade13 = "@auth\nfoo\n@endauth";
        $blade14 = "@error('foo')\nfoo\n@enderror";
        $blade15 = "@method('PUT')";
        $blade16 = "@push('scripts')\n<script></script>\n@endpush";
        $blade17 = "@stack('scripts')";
        $blade18 = "@hassection('content')\nContent\n@endif";
        $blade19 = "@sectionmissing('content')\nNo content\n@endif";
        $blade20 = "@verbatim\n{{ \$var }}\n@endverbatim";

        $out1 = "<?php if (true): ?>\nfoo\n<?php endif; ?>";
        $out2 = "<?php if (count(\$something) > 0): ?>\nfoo\n<?php endif; ?>";
        $out3 = "<?php if (true): ?>\nfoo\n<?php elseif (false): ?>\nbar\n" .
            "<?php else: ?>\nfoobar\n<?php endif; ?>";
        $out4 = "<?php if (true): ?>\nfoo\n<?php elseif (false): ?>\nbar\n<?php endif; ?>";
        $out5 = "<?php if (true): ?>\nfoo\n<?php else: ?>\nbar\n<?php endif; ?>";
        $out6 = "<?php if (! ( (count(\$something) > 0))): ?>\nfoobar\n<?php endif; ?>";
        $out7 = "<?php for (Foo::all() as \$foo): ?>\nfoo\n<?php endfor; ?>";
        $out8 = "<?php \$__loop_stack = isset(\$__loop_stack) ? \$__loop_stack : []; \$__loop_stack[] = (object)[\"index\" => -1, \"iteration\" => 0, \"remaining\" => count(Foo::all()), \"count\" => count(Foo::all()), \"first\" => false, \"last\" => false, \"even\" => false, \"odd\" => false, \"depth\" => count(\$__loop_stack), \"parent\" => count(\$__loop_stack) > 0 ? \$__loop_stack[count(\$__loop_stack)-1] : null]; foreach (Foo::all() as \$foo): \$__loop_stack[count(\$__loop_stack)-1]->index++; \$__loop_stack[count(\$__loop_stack)-1]->iteration++; \$__loop_stack[count(\$__loop_stack)-1]->remaining--; \$__loop_stack[count(\$__loop_stack)-1]->first = (\$__loop_stack[count(\$__loop_stack)-1]->index === 0); \$__loop_stack[count(\$__loop_stack)-1]->last = (\$__loop_stack[count(\$__loop_stack)-1]->index === \$__loop_stack[count(\$__loop_stack)-1]->count - 1); \$__loop_stack[count(\$__loop_stack)-1]->even = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 === 0); \$__loop_stack[count(\$__loop_stack)-1]->odd = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 !== 0); \$loop = \$__loop_stack[count(\$__loop_stack)-1]; ?>\nfoo\n<?php endforeach; ?><?php array_pop(\$__loop_stack); \$loop = count(\$__loop_stack) ? end(\$__loop_stack) : null; ?>";
        $out9 = "<?php \$__loop_stack = isset(\$__loop_stack) ? \$__loop_stack : []; \$__loop_stack[] = (object)[\"index\" => -1, \"iteration\" => 0, \"remaining\" => count(Foo::all()), \"count\" => count(Foo::all()), \"first\" => false, \"last\" => false, \"even\" => false, \"odd\" => false, \"depth\" => count(\$__loop_stack), \"parent\" => count(\$__loop_stack) > 0 ? \$__loop_stack[count(\$__loop_stack)-1] : null]; if (count(Foo::all()) > 0): ?><?php foreach (Foo::all() as \$foo): \$__loop_stack[count(\$__loop_stack)-1]->index++; \$__loop_stack[count(\$__loop_stack)-1]->iteration++; \$__loop_stack[count(\$__loop_stack)-1]->remaining--; \$__loop_stack[count(\$__loop_stack)-1]->first = (\$__loop_stack[count(\$__loop_stack)-1]->index === 0); \$__loop_stack[count(\$__loop_stack)-1]->last = (\$__loop_stack[count(\$__loop_stack)-1]->index === \$__loop_stack[count(\$__loop_stack)-1]->count - 1); \$__loop_stack[count(\$__loop_stack)-1]->even = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 === 0); \$__loop_stack[count(\$__loop_stack)-1]->odd = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 !== 0); \$loop = \$__loop_stack[count(\$__loop_stack)-1]; ?>\nfoo\n<?php endforeach; ?><?php else: ?>\nbar\n<?php endif; array_pop(\$__loop_stack); \$loop = count(\$__loop_stack) ? end(\$__loop_stack) : null; ?>";
        $out10 = "<?php while (true): ?>\nfoo\n<?php endwhile; ?>";
        $out11 = "<?php while (Foo::bar()): ?>\nfoo\n<?php endwhile; ?>";
        $out12 = "<?php if (\System\Auth::guest()): ?>\nfoo\n<?php endif; ?>";
        $out13 = "<?php if (\System\Auth::check()): ?>\nfoo\n<?php endif; ?>";
        $out14 = "<?php if (\$errors->has('foo')): ?>\nfoo\n<?php endif; ?>";
        $out15 = '<input type="hidden" name="_method" value="PUT" />';
        $out16 = "<?php \System\Section::push('scripts') ?>\n<script></script>\n<?php \System\Section::endpush() ?>";
        $out17 = "<?php echo \System\Section::stack('scripts') ?>";
        $out18 = "<?php if (\System\Section::has('content')): ?>\nContent\n<?php endif; ?>";
        $out19 = "<?php if (!\System\Section::has('content')): ?>\nNo content\n<?php endif; ?>";
        $out20 = "\n{{ \$var }}\n";

        $this->assertEquals($out1, Blade::translate($blade1));
        $this->assertEquals($out2, Blade::translate($blade2));
        $this->assertEquals($out3, Blade::translate($blade3));
        $this->assertEquals($out4, Blade::translate($blade4));
        $this->assertEquals($out5, Blade::translate($blade5));
        $this->assertEquals($out6, Blade::translate($blade6));
        $this->assertEquals($out7, Blade::translate($blade7));
        $this->assertEquals($out8, Blade::translate($blade8));
        $this->assertEquals($out9, Blade::translate($blade9));
        $this->assertEquals($out10, Blade::translate($blade10));
        $this->assertEquals($out11, Blade::translate($blade11));
        $this->assertEquals($out12, Blade::translate($blade12));
        $this->assertEquals($out13, Blade::translate($blade13));
        $this->assertEquals($out14, Blade::translate($blade14));
        $this->assertEquals($out15, Blade::translate($blade15));
        $this->assertEquals($out16, Blade::translate($blade16));
        $this->assertEquals($out17, Blade::translate($blade17));
        $this->assertEquals($out18, Blade::translate($blade18));
        $this->assertEquals($out19, Blade::translate($blade19));
        $this->assertEquals($out20, Blade::translate($blade20));
    }

    public function testErrorAndEnderrorAreCompiledCorrectly()
    {
        $blade = "@error('name')";
        $out = "<?php if (\$errors->has('name')): ?>";

        $blade2 = '@enderror';
        $out2 = '<?php endif; ?>';

        $this->assertEquals($out, Blade::translate($blade));
        $this->assertEquals($out2, Blade::translate($blade2));
    }

    /**
     * Test for @yield.
     *
     * @group system
     */
    public function testYieldsAreCompiledCorrectly()
    {
        $blade = "@yield('something')";
        $out = "<?php echo yield_content('something') ?>";

        $this->assertEquals($out, Blade::translate($blade));
    }

    /**
     * Test for @section and @endsection.
     *
     * @group system
     */
    /**
     * @stop closes a section the same way @endsection does, which is what the
     * helpers page says it does.
     *
     * @group system
     */
    public function testStopClosesASection()
    {
        $compiled = Blade::translate("@section('x')content@stop");

        $this->assertContains('section_stop()', $compiled);
        $this->assertNotContains('@stop', $compiled);
        $this->assertEquals(Blade::translate("@section('x')content@endsection"), $compiled);
    }

    public function testSectionsAreCompiledCorrectly()
    {
        $blade = "@section('something')\nfoo\n@endsection";
        $out = "<?php section_start('something') ?>\nfoo\n<?php section_stop() ?>";

        $this->assertEquals($out, Blade::translate($blade));
    }

    /**
     * Test for @include().
     *
     * @group system
     */
    public function testIncludesAreCompiledCorrectly()
    {
        $blade1 = "@include('user.profile')";
        $blade2 = "@include(Config::get('application.default_view', 'user.profile'))";

        $out1 = "<?php echo \\System\\Blade::inherit(view('user.profile'), get_defined_vars())->render() ?>";
        $out2 = "<?php echo \\System\\Blade::inherit(view(Config::get('application.default_view', 'user.profile')), get_defined_vars())->render() ?>";

        $this->assertEquals($out1, Blade::translate($blade1));
        $this->assertEquals($out2, Blade::translate($blade2));
    }

    /**
     * Test for @render().
     *
     * @group system
     */
    public function testRendersAreCompiledCorrectly()
    {
        $blade1 = "@render('user.profile')";
        $blade2 = "@render(Config::get('application.default_view', 'user.profile'))";

        $out1 = "<?php echo render('user.profile') ?>";
        $out2 = "<?php echo render(Config::get('application.default_view', 'user.profile')) ?>";

        $this->assertEquals($out1, Blade::translate($blade1));
        $this->assertEquals($out2, Blade::translate($blade2));
    }

    /**
     * Test for $loop in @foreach.
     *
     * @group system
     */
    public function testLoopVariableInForeach()
    {
        $blade = '@foreach ($items as $item)' . "\n" . '{{ $loop->index }}' . "\n" . '@endforeach';
        $translated = Blade::translate($blade);
        $this->assertContains('isset($__loop_stack)', $translated);
        $this->assertContains('$loop = $__loop_stack[count($__loop_stack)-1]', $translated);
        $this->assertContains('array_pop($__loop_stack)', $translated);
    }

    /**
     * Test for $loop in @forelse.
     *
     * @group system
     */
    public function testLoopVariableInForelse()
    {
        $blade = '@forelse ($items as $item)' . "\n" . '{{ $loop->iteration }}' . "\n" . '@empty' . "\n" . 'No items' . "\n" . '@endforelse';
        $translated = Blade::translate($blade);
        $this->assertContains('isset($__loop_stack)', $translated);
        $this->assertContains('$loop = $__loop_stack[count($__loop_stack)-1]', $translated);
        $this->assertContains('array_pop($__loop_stack)', $translated);
    }

    /**
     * Test for @once.
     *
     * @group system
     */
    public function testOnceDirective()
    {
        $blade = '@once' . "\n" . 'Unique content' . "\n" . '@endonce';
        $translated = Blade::translate($blade);

        // The decision is made at render time (see Blade::once()), never while
        // compiling, so the compiled file must keep the block and guard it.
        $this->assertContains('\\System\\Blade::once(', $translated);
        $this->assertContains("\nUnique content\n", $translated);
        $this->assertStringEndsWith('<?php endif; ?>', $translated);

        // Compiling the very same block twice must produce the very same output.
        $this->assertEquals($translated, Blade::translate($blade));
    }

    /**
     * Test for Blade::once() - only the first call for a key returns true.
     *
     * @group system
     */
    public function testOnceIsDecidedAtRuntime()
    {
        Blade::forget_onces();

        $this->assertTrue(Blade::once('a-key'));
        $this->assertFalse(Blade::once('a-key'));
        $this->assertTrue(Blade::once('another-key'));

        Blade::forget_onces();
        $this->assertTrue(Blade::once('a-key'));
    }

    /**
     * Test for Blade::compiled().
     *
     * The result is memoized, so repeated calls must keep returning the same
     * path, and different templates must still map to different files.
     *
     * @group system
     */
    public function testCompiledPathIsStableAndUnique()
    {
        $one = path('app') . 'views' . DS . 'home' . DS . 'index.blade.php';
        $two = path('app') . 'views' . DS . 'home' . DS . 'other.blade.php';

        $this->assertEquals(Blade::compiled($one), Blade::compiled($one));
        $this->assertNotEquals(Blade::compiled($one), Blade::compiled($two));
        $this->assertStringEndsWith('.bc.php', Blade::compiled($one));
    }

    /**
     * A compiled view is executable PHP living in the storage directory, so the
     * file carries its own guard instead of relying on the web server to deny it.
     * Rendering through the framework must not be affected by it.
     *
     * @group system
     */
    public function testCompiledViewIsGuardedAgainstDirectAccess()
    {
        $template = path('app') . 'views' . DS . 'guardprobe.blade.php';
        file_put_contents($template, 'value is {{ $n }}');

        $compiled = Blade::compiled($template);
        is_file($compiled) && unlink($compiled);

        $output = System\View::make('guardprobe', ['n' => 42])->render();

        $this->assertStringStartsWith(Blade::GUARD, file_get_contents($compiled));
        $this->assertEquals('value is 42', trim($output));

        // Reached directly, the file renders nothing of the template.
        $direct = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($compiled) . ' 2>&1');

        $this->assertContains('No direct access.', (string) $direct);
        $this->assertNotContains('value is', (string) $direct);

        unlink($template);
        is_file($compiled) && unlink($compiled);
    }

    /**
     * Compiling a template for the first time must not emit any diagnostic.
     *
     * The debugger runs with Debugger::$scream on, which disables the '@'
     * operator and promotes warnings to exceptions. Anything in the compile
     * path that pokes a not-yet-existing compiled file without checking first
     * therefore turns every cold view cache into a 500.
     *
     * @group system
     */
    public function testCompilingAColdTemplateEmitsNoDiagnostics()
    {
        $views = path('app') . 'views' . DS;
        $template = $views . 'coldprobe.blade.php';

        file_put_contents($template, 'cold {{ $n }}');

        $compiled = Blade::compiled($template);
        is_file($compiled) && unlink($compiled);

        // Stand in for Debugger::$scream: refuse to let anything pass quietly.
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        $caught = null;
        $output = null;

        try {
            $output = System\View::make('coldprobe', ['n' => 7])->render();
        } catch (\Throwable $e) {
            $caught = $e;
        } catch (\Exception $e) {
            $caught = $e;
        }

        restore_error_handler();

        unlink($template);
        is_file($compiled) && unlink($compiled);

        $this->assertNull($caught, $caught ? 'Compiling a cold template raised: ' . $caught->getMessage() : '');
        $this->assertEquals('cold 7', trim($output));
    }

    /**
     * Test for Blade::expired().
     *
     * With the modification-time check disabled (how the framework runs in
     * production) a compiled template is always considered current, so an
     * edited template does not trigger a recompile until the cache is cleared.
     *
     * @group system
     */
    public function testExpiredIsSkippedWhenReloadIsDisabled()
    {
        $path = path('storage') . 'expired-probe.blade.php';
        file_put_contents($path, 'hello');

        $compiled = Blade::compiled($path);
        file_put_contents($compiled, 'hello');

        // Make the template look newer than its compiled counterpart.
        touch($compiled, time() - 60);
        clearstatcache();

        Blade::$reload = true;
        $this->assertTrue(Blade::expired($path));

        Blade::$reload = false;
        $this->assertFalse(Blade::expired($path));

        Blade::$reload = true;

        unlink($path);
        unlink($compiled);
    }

    /**
     * Run a translated template and return what it printed.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    protected function evaluate($template, array $data = [])
    {
        $__compiled = Blade::translate($template);
        extract($data);
        ob_start();
        eval('?>' . $__compiled);
        return ob_get_clean();
    }

    /**
     * Echoes inside a plain component attribute become concatenated PHP.
     *
     * @group system
     */
    public function testEchoInComponentAttributeIsConcatenated()
    {
        $out = Blade::translate('<x-alert title="Hi {{ $t }}!" raw="{!! $r !!}" plain="p"/>');
        $this->assertContains("'title' => '' . 'Hi ' . (\$t) . '!'", $out);
        $this->assertContains("'raw' => '' . (\$r)", $out);
        $this->assertContains("'plain' => 'p'", $out);
        $this->assertNotContains('echo e(', $out);
    }

    /**
     * $loop points back at the outer loop once a nested loop ends.
     *
     * @group system
     */
    public function testLoopIsRestoredAfterNestedLoop()
    {
        $blade = '@foreach($a as $x)@foreach($b as $y)@endforeach[{{ $loop->index }}:{{ $loop->depth }}]@endforeach'
            . '@forelse($a as $x)@forelse($b as $y)@empty @endforelse({{ $loop->index }})@empty @endforelse';
        $this->assertEquals('[0:0][1:0](0)(1)', $this->evaluate($blade, ['a' => [1, 2], 'b' => [1, 2, 3]]));
    }

    /**
     * Data passed to @include wins over the including view's variables.
     *
     * @group system
     */
    public function testIncludeDataOverridesParentVariables()
    {
        $view = new \stdClass();
        $view->data = ['a' => 'explicit'];
        Blade::inherit($view, ['a' => 'parent', 'b' => 'parent']);
        $this->assertEquals(['a' => 'explicit', 'b' => 'parent'], $view->data);
    }

    /**
     * Directive names only match as whole words.
     *
     * @group system
     */
    public function testDirectivesNeedAWordBoundary()
    {
        $text = '<!-- @author John --> info@showroom.com @csrfx @elsewhere @guests @emptyish';
        $this->assertEquals($text, Blade::translate($text));
        $this->assertEquals('[yes]', $this->evaluate('[@if($a)yes@else no@endif]', ['a' => true]));
    }

    /**
     * The "or" default only applies to a variable on its left.
     *
     * @group system
     */
    public function testEchoOrIgnoresStringLiterals()
    {
        $this->assertEquals('yes or no', $this->evaluate('{{ $x ? "yes or no" : "z" }}', ['x' => true]));
        $this->assertEquals('Guest|d', $this->evaluate('{{ $name or "Guest" }}|{{ $a["k"] or "d" }}', ['a' => []]));
    }

    /**
     * @forelse and @foreach take the whole iterable, parentheses and "=" included.
     *
     * @group system
     */
    public function testLoopsAcceptComplexIterables()
    {
        $data = ['items' => [1, 2, 3, 4, 5]];
        $this->assertEquals('123', $this->evaluate('@forelse(array_slice($items, 0, 3) as $x){{ $x }}@empty none@endforelse', $data));
        $this->assertEquals(
            '1345',
            $this->evaluate('@foreach(array_filter($items, function ($v) { return $v !== 2; }) as $x){{ $x }}@endforeach', $data)
        );
    }

    /**
     * @php: inline form, echoes left alone inside blocks, and comments.
     *
     * @group system
     */
    public function testPhpDirectiveAndComments()
    {
        $this->assertEquals('1 {{ z }}', $this->evaluate('@php($x = 1){{ $x }} @php echo "{{ z }}"; @endphp{{-- a */ b --}}'));
        $this->assertEquals('', Blade::translate('{{-- a */ b --}}'));
    }

    /**
     * @set stops at its own closing parenthesis.
     *
     * @group system
     */
    public function testSetStopsAtItsOwnParenthesis()
    {
        $this->assertEquals('3 <span>(total)</span>', $this->evaluate("@set('total', count(\$items)){{ \$total }} <span>(total)</span>", ['items' => [1, 2, 3]]));
    }
}
