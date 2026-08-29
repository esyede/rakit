<?php

defined('DS') or exit('No direct access.');

use System\View;
use System\Blade;
use System\Storage;

/**
 * Covers the <x-name> component tags: their attributes, their slots, the
 * attribute bag, and the classes behind them.
 */
class BladeComponentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Contains the views the tests wrote.
     *
     * @var array
     */
    protected $written = [];

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach ($this->written as $file) {
            Storage::delete($file);
        }

        $this->written = [];
    }

    /**
     * Render the given template through the blade engine.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    protected function render($template, array $data = [])
    {
        static $count = 0;

        $name = 'component_probe_' . (++$count);
        $file = path('app') . 'views' . DS . $name . '.blade.php';

        file_put_contents($file, $template, LOCK_EX);
        $this->written[] = $file;

        $reload = Blade::$reload;
        Blade::$reload = true;

        try {
            $output = View::make($name, $data)->render();
        } catch (\Exception $e) {
            Blade::$reload = $reload;

            throw $e;
        }

        Blade::$reload = $reload;

        return trim(preg_replace('/\s+/', ' ', $output));
    }

    // -------------------------------------------------------------------------
    // Tags and attributes
    // -------------------------------------------------------------------------

    /**
     * A component tag renders the view of the same name.
     *
     * @group system
     */
    public function testComponentRendersItsView()
    {
        $this->assertEquals('[halo]', $this->render('<x-wrap>halo</x-wrap>'));
    }

    /**
     * A tag may close itself.
     *
     * @group system
     */
    public function testSelfClosingTag()
    {
        $this->assertEquals('[]', $this->render('<x-wrap />'));
    }

    /**
     * A tag that only looks like one is left alone.
     *
     * @group system
     */
    public function testATagThatIsNotAComponentIsLeftAlone()
    {
        $this->assertEquals('<xa-wrap>halo</xa-wrap>', $this->render('<xa-wrap>halo</xa-wrap>'));
        $this->assertEquals('<div>halo</div>', $this->render('<div>halo</div>'));
    }

    /**
     * An attribute becomes a variable of the view, and @props gives it a value
     * for when the tag leaves it out.
     *
     * @group system
     */
    public function testAttributesBecomeVariables()
    {
        $this->assertContains('alert-info', $this->render('<x-alert>x</x-alert>'));
        $this->assertContains('alert-error', $this->render('<x-alert type="error">x</x-alert>'));
    }

    /**
     * An attribute written with a colon is read as an expression.
     *
     * @group system
     */
    public function testBoundAttributeIsAnExpression()
    {
        $this->assertContains(
            'alert-warning',
            $this->render('<x-alert :type="$kind">x</x-alert>', ['kind' => 'warning'])
        );

        $this->assertContains(
            'alert-yes',
            $this->render('<x-alert :type="$a > $b ? \'yes\' : \'no\'">x</x-alert>', ['a' => 5, 'b' => 2])
        );
    }

    /**
     * An attribute with no value at all is there, and true.
     *
     * @group system
     */
    public function testBareAttribute()
    {
        $this->assertContains('required', $this->render('<x-alert required>x</x-alert>'));
    }

    /**
     * What @props does not name is left in the attribute bag, and printed as
     * html rather than as escaped text.
     *
     * @group system
     */
    public function testTheAttributeBagCarriesTheRest()
    {
        $output = $this->render('<x-alert type="e" id="a1" data-x="2">z</x-alert>');

        $this->assertContains('id="a1"', $output);
        $this->assertContains('data-x="2"', $output);
        $this->assertNotContains('type="e"', $output);
    }

    /**
     * merge() puts defaults underneath, and joins the class lists.
     *
     * @group system
     */
    public function testAttributeBagMerge()
    {
        $output = $this->render('<x-forms.input name="email" class="big" />');

        $this->assertContains('name="email"', $output);
        $this->assertContains('class="form-control big"', $output);
    }

    // -------------------------------------------------------------------------
    // Slots
    // -------------------------------------------------------------------------

    /**
     * A named slot fills the variable of that name.
     *
     * @group system
     */
    public function testNamedSlot()
    {
        $this->assertEquals(
            '<div class="card"><h3>Judul</h3><div>Isi</div></div>',
            $this->render('<x-card><x-slot name="title">Judul</x-slot>Isi</x-card>')
        );
    }

    /**
     * A named slot may be written with a colon instead.
     *
     * @group system
     */
    public function testNamedSlotWithAColon()
    {
        $this->assertEquals(
            '<div class="card"><h3>Judul</h3><div>Isi</div></div>',
            $this->render('<x-card><x-slot:title>Judul</x-slot>Isi</x-card>')
        );
    }

    /**
     * The slot knows whether it was given anything.
     *
     * @group system
     */
    public function testAnEmptySlotSaysSo()
    {
        $this->assertEquals('<div>KOSONG</div>', $this->render('<x-empty-check />'));
        $this->assertEquals('<div>ada</div>', $this->render('<x-empty-check>ada</x-empty-check>'));
    }

    /**
     * Blade inside a slot is compiled like blade anywhere else.
     *
     * @group system
     */
    public function testBladeInsideASlot()
    {
        $this->assertEquals('[ya]', $this->render('<x-wrap>@if($a)ya@else tidak @endif</x-wrap>', ['a' => true]));
        $this->assertEquals('[ab]', $this->render('<x-wrap>@foreach($xs as $x){{ $x }}@endforeach</x-wrap>', ['xs' => ['a', 'b']]));
    }

    // -------------------------------------------------------------------------
    // Nesting
    // -------------------------------------------------------------------------

    /**
     * A component may hold another one, and the html of the inner one is not
     * escaped on its way out of the outer one.
     *
     * @group system
     */
    public function testNesting()
    {
        $this->assertEquals('[[[core]]]', $this->render('<x-wrap><x-wrap><x-wrap>core</x-wrap></x-wrap></x-wrap>'));

        $output = $this->render('<x-card><x-slot name="title">T</x-slot><x-alert type="ok">In</x-alert></x-card>');

        $this->assertContains('<div class="alert alert-ok"', $output);
        $this->assertNotContains('&lt;div', $output);
    }

    /**
     * A component in a loop is rendered once per turn.
     *
     * @group system
     */
    public function testComponentInALoop()
    {
        $this->assertEquals(
            '[a][b]',
            $this->render('@foreach($xs as $x)<x-wrap>{{ $x }}</x-wrap>@endforeach', ['xs' => ['a', 'b']])
        );
    }

    /**
     * A component works inside a section, and inside an included view.
     *
     * @group system
     */
    public function testComponentInsideASection()
    {
        $this->assertEquals('[isi]', $this->render('@section("x")<x-wrap>isi</x-wrap>@endsection@yield("x")'));
    }

    /**
     * A tag inside @verbatim is left alone.
     *
     * @group system
     */
    public function testVerbatimKeepsTheTag()
    {
        $this->assertEquals('<x-wrap>raw</x-wrap>', $this->render('@verbatim<x-wrap>raw</x-wrap>@endverbatim'));
    }

    // -------------------------------------------------------------------------
    // Escaping
    // -------------------------------------------------------------------------

    /**
     * What a slot holds has already been escaped on its way in, so it is not
     * escaped again on its way out, and user input stays escaped.
     *
     * @group system
     */
    public function testUserInputInsideASlotStaysEscaped()
    {
        $output = $this->render('<x-wrap>{{ $evil }}</x-wrap>', ['evil' => '<script>alert(1)</script>']);

        $this->assertNotContains('<script>', $output);
        $this->assertContains('&lt;script&gt;', $output);
    }

    /**
     * Raw output inside a slot is still raw.
     *
     * @group system
     */
    public function testRawOutputInsideASlotIsStillRaw()
    {
        $this->assertEquals('[<b>tebal</b>]', $this->render('<x-wrap>{!! $raw !!}</x-wrap>', ['raw' => '<b>tebal</b>']));
    }

    /**
     * User input handed to an attribute is escaped where it lands.
     *
     * @group system
     */
    public function testUserInputInAnAttributeIsEscaped()
    {
        $evil = '"><script>alert(1)</script>';

        $output = $this->render('<x-alert :type="$evil">x</x-alert>', ['evil' => $evil]);
        $this->assertNotContains('<script>', $output);

        $output = $this->render('<x-alert :id="$evil">x</x-alert>', ['evil' => $evil]);
        $this->assertNotContains('<script>', $output);
    }

    /**
     * A quoted attribute value may hold the characters that end a tag without
     * the tag being cut short there.
     *
     * @group system
     */
    public function testAttributeValueMayHoldTagCharacters()
    {
        $this->assertEquals('[y]', $this->render('<x-wrap :x="\'a/>b\'">y</x-wrap>'));
        $this->assertEquals('[y]', $this->render('<x-wrap title="a > b">y</x-wrap>'));
    }

    /**
     * A slot that throws does not leave its buffer open, or everything printed
     * after it would be swallowed.
     *
     * @group system
     */
    public function testASlotThatThrowsLeavesNoBufferBehind()
    {
        $level = ob_get_level();

        try {
            $this->render('<x-wrap>{{ $nowhere->method() }}</x-wrap>');
            $this->fail('Expected the slot to throw.');
        } catch (\Exception $e) {
            // sesuai harapan
        } catch (\Throwable $e) {
            // sesuai harapan
        }

        $this->assertEquals($level, ob_get_level());
    }

    // -------------------------------------------------------------------------
    // Component classes
    // -------------------------------------------------------------------------

    /**
     * A component with a class of its own gets its properties from the tag,
     * and keeps its own values for what the tag left out.
     *
     * @group system
     */
    public function testComponentClass()
    {
        $output = $this->render('<x-badge label="Baru" colour="green" id="b1" />');

        $this->assertContains('badge-green', $output);
        $this->assertContains('Baru', $output);
        $this->assertContains('id="b1"', $output);

        $output = $this->render('<x-badge label="Lama" />');

        $this->assertContains('badge-grey', $output);
    }

    /**
     * A component class may answer with the content itself instead of a view.
     *
     * @group system
     */
    public function testComponentClassThatAnswersWithContent()
    {
        $this->assertEquals('<b>&lt;i&gt;miring&lt;/i&gt;</b>', $this->render('<x-inline text="<i>miring</i>" />'));
    }

    /**
     * A package names its components the way it names its views, with the
     * double colon, and the class behind one carries the package prefix.
     *
     * @group system
     */
    public function testComponentOfAPackage()
    {
        $this->assertEquals(
            '<div class="kotak-dummy">isi</div>',
            $this->render('<x-dummy::kotak>isi</x-dummy::kotak>')
        );

        $this->assertEquals(
            '<b>Baru</b>',
            $this->render('<x-dummy::lencana label="Baru" />')
        );
    }

    /**
     * Naming the default package is the same as naming no package at all, so
     * the class behind it carries no prefix.
     *
     * @group system
     */
    public function testNamingTheDefaultPackageChangesNothing()
    {
        $this->assertEquals(
            $this->render('<x-badge label="A" />'),
            $this->render('<x-application::badge label="A" />')
        );
    }

    /**
     * A component that is nowhere says so.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testMissingComponentThrows()
    {
        $this->render('<x-nowhere />');
    }
}
