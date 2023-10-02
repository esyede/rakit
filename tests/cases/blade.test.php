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
     * Test untuk token echo.
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
     * Test untuk token csrf.
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
     * Test untuk token comment.
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
     * Test untuk token control structure.
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

        $out1 = "<?php if (true): ?>\nfoo\n<?php endif; ?>";
        $out2 = "<?php if (count(\$something) > 0): ?>\nfoo\n<?php endif; ?>";
        $out3 = "<?php if (true): ?>\nfoo\n<?php elseif (false): ?>\nbar\n" .
            "<?php else: ?>\nfoobar\n<?php endif; ?>";
        $out4 = "<?php if (true): ?>\nfoo\n<?php elseif (false): ?>\nbar\n<?php endif; ?>";
        $out5 = "<?php if (true): ?>\nfoo\n<?php else: ?>\nbar\n<?php endif; ?>";
        $out6 = "<?php if (! ( (count(\$something) > 0))): ?>\nfoobar\n<?php endif; ?>";
        $out7 = "<?php for (Foo::all() as \$foo): ?>\nfoo\n<?php endfor; ?>";
        $out8 = "<?php foreach (Foo::all() as \$foo): ?>\nfoo\n<?php endforeach; ?>";
        $out9 = "<?php if (count(Foo::all()) > 0): ?><?php foreach (Foo::all() as \$foo): ?>\n" .
            "foo\n<?php endforeach; ?><?php else: ?>\nbar\n<?php endif; ?>";
        $out10 = "<?php while (true): ?>\nfoo\n<?php endwhile; ?>";
        $out11 = "<?php while (Foo::bar()): ?>\nfoo\n<?php endwhile; ?>";
        $out12 = "<?php if (System\Auth::guest()): ?>\nfoo\n<?php endif; ?>";
        $out13 = "<?php if (System\Auth::check()): ?>\nfoo\n<?php endif; ?>";
        $out14 = "<?php if (\$errors->has('foo')): ?>\nfoo\n<?php endif; ?>";

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
     * Test untuk token @yield.
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
     * Test untuk token @section dan @endsection.
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
     * Test untuk token @include().
     *
     * @group system
     */
    public function testIncludesAreCompiledCorrectly()
    {
        $blade1 = "@include('user.profile')";
        $blade2 = "@include(Config::get('application.default_view', 'user.profile'))";

        $out1 = "<?php echo view('user.profile')->with(get_defined_vars())->render() ?>";
        $out2 = "<?php echo view(Config::get('application.default_view', 'user.profile'))" .
            '->with(get_defined_vars())->render() ?>';

        $this->assertEquals($out1, Blade::translate($blade1));
        $this->assertEquals($out2, Blade::translate($blade2));
    }

    /**
     * Test untuk token @render().
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
}
