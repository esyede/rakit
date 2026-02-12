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
            if (is_file($file)) {
                unlink($file);
            }
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

        $out1 = '<?php /*  This is a comment  */ ?>';
        $out2 = "<?php /* \nThis is a\nmulti-line\ncomment.\n */ ?>";

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
        $out8 = "<?php \$__loop_stack = isset(\$__loop_stack) ? \$__loop_stack : []; \$__loop_stack[] = (object)[\"index\" => -1, \"iteration\" => 0, \"remaining\" => count(Foo::all()), \"count\" => count(Foo::all()), \"first\" => false, \"last\" => false, \"even\" => false, \"odd\" => false, \"depth\" => count(\$__loop_stack), \"parent\" => count(\$__loop_stack) > 0 ? \$__loop_stack[count(\$__loop_stack)-1] : null]; foreach (Foo::all() as \$foo): \$__loop_stack[count(\$__loop_stack)-1]->index++; \$__loop_stack[count(\$__loop_stack)-1]->iteration++; \$__loop_stack[count(\$__loop_stack)-1]->remaining--; \$__loop_stack[count(\$__loop_stack)-1]->first = (\$__loop_stack[count(\$__loop_stack)-1]->index === 0); \$__loop_stack[count(\$__loop_stack)-1]->last = (\$__loop_stack[count(\$__loop_stack)-1]->index === \$__loop_stack[count(\$__loop_stack)-1]->count - 1); \$__loop_stack[count(\$__loop_stack)-1]->even = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 === 0); \$__loop_stack[count(\$__loop_stack)-1]->odd = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 !== 0); \$loop = \$__loop_stack[count(\$__loop_stack)-1]; ?>\nfoo\n<?php endforeach; ?><?php array_pop(\$__loop_stack); ?>";
        $out9 = "<?php if (count(Foo::all()) > 0): ?><?php \$__loop_stack = isset(\$__loop_stack) ? \$__loop_stack : []; \$__loop_stack[] = (object)[\"index\" => -1, \"iteration\" => 0, \"remaining\" => count(Foo::all()), \"count\" => count(Foo::all()), \"first\" => false, \"last\" => false, \"even\" => false, \"odd\" => false, \"depth\" => count(\$__loop_stack), \"parent\" => count(\$__loop_stack) > 0 ? \$__loop_stack[count(\$__loop_stack)-1] : null]; foreach (Foo::all() as \$foo): \$__loop_stack[count(\$__loop_stack)-1]->index++; \$__loop_stack[count(\$__loop_stack)-1]->iteration++; \$__loop_stack[count(\$__loop_stack)-1]->remaining--; \$__loop_stack[count(\$__loop_stack)-1]->first = (\$__loop_stack[count(\$__loop_stack)-1]->index === 0); \$__loop_stack[count(\$__loop_stack)-1]->last = (\$__loop_stack[count(\$__loop_stack)-1]->index === \$__loop_stack[count(\$__loop_stack)-1]->count - 1); \$__loop_stack[count(\$__loop_stack)-1]->even = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 === 0); \$__loop_stack[count(\$__loop_stack)-1]->odd = (\$__loop_stack[count(\$__loop_stack)-1]->iteration % 2 !== 0); \$loop = \$__loop_stack[count(\$__loop_stack)-1]; ?>\nfoo\n<?php endforeach; ?><?php else: ?>\nbar\n<?php endif; array_pop(\$__loop_stack); ?>";
        $out10 = "<?php while (true): ?>\nfoo\n<?php endwhile; ?>";
        $out11 = "<?php while (Foo::bar()): ?>\nfoo\n<?php endwhile; ?>";
        $out12 = "<?php if (System\Auth::guest()): ?>\nfoo\n<?php endif; ?>";
        $out13 = "<?php if (System\Auth::check()): ?>\nfoo\n<?php endif; ?>";
        $out14 = "<?php if (\$errors->has('foo')): ?>\nfoo\n<?php endif; ?>";
        $out15 = "<input type=\"hidden\" name=\"_method\" value=\"PUT\" />";
        $out16 = "<?php Section::push('scripts') ?>\n<script></script>\n<?php Section::endpush() ?>";
        $out17 = "<?php echo Section::stack('scripts') ?>";
        $out18 = "<?php if (Section::has('content')): ?>\nContent\n<?php endif; ?>";
        $out19 = "<?php if (!Section::has('content')): ?>\nNo content\n<?php endif; ?>";
        $out20 = "\n{{ \$var }}\n";
        $out15 = "<input type=\"hidden\" name=\"_method\" value=\"PUT\" />";
        $out16 = "<?php Section::push('scripts') ?>\n<script></script>\n<?php Section::endpush() ?>";
        $out17 = "<?php echo Section::stack('scripts') ?>";
        $out18 = "<?php if (Section::has('content')): ?>\nContent\n<?php endif; ?>";
        $out19 = "<?php if (!Section::has('content')): ?>\nNo content\n<?php endif; ?>";
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

        $out1 = "<?php echo view('user.profile')->with(get_defined_vars())->render() ?>";
        $out2 = "<?php echo view(Config::get('application.default_view', 'user.profile'))->with(get_defined_vars())->render() ?>";

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
     * Test untuk $loop in @foreach.
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
     * Test untuk $loop in @forelse.
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
        $this->assertEquals("\nUnique content\n", $translated);
    }
}
