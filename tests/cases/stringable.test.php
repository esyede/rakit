<?php

defined('DS') or exit('No direct access.');

use System\Str;
use System\Stringable;

/**
 * Covers the fluent string wrapper reached through Str::of().
 */
class StringableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tear down.
     */
    public function tearDown()
    {
        Str::$macros = [];
    }

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    /**
     * Str::of() hands back a fluent string.
     *
     * @group system
     */
    public function testOfReturnsAStringable()
    {
        $string = Str::of('halo');

        $this->assertInstanceOf('System\Stringable', $string);
        $this->assertEquals('halo', (string) $string);
        $this->assertEquals('halo', $string->value());
    }

    /**
     * Anything that can become a string is accepted.
     *
     * @group system
     */
    public function testOfAcceptsWhatCanBecomeAString()
    {
        $this->assertEquals('', (string) Str::of(null));
        $this->assertEquals('123', (string) Str::of(123));
        $this->assertEquals('1.5', (string) Str::of(1.5));
        $this->assertEquals('halo', (string) Str::of(Str::of('halo')));
        $this->assertEquals('dari objek', (string) Str::of(new StringableProbe()));
    }

    /**
     * Anything that cannot is refused, instead of turning into 'Array'.
     *
     * @group system
     */
    public function testOfRefusesWhatCannotBecomeAString()
    {
        try {
            Str::of([1, 2]);
            $this->fail('Expected an array to be refused.');
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('array given', $e->getMessage());
        }

        try {
            Str::of(new \stdClass());
            $this->fail('Expected an object without __toString to be refused.');
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('object given', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Forwarding
    // -------------------------------------------------------------------------

    /**
     * Every method answers what the Str method behind it answers.
     *
     * @group system
     */
    public function testMethodsAgreeWithStr()
    {
        $this->assertEquals(Str::length('halo dunia'), Str::of('halo dunia')->length());
        $this->assertEquals(Str::substr('halo dunia', 0, 4), (string) Str::of('halo dunia')->substr(0, 4));
        $this->assertEquals(Str::substr('halo dunia', 5), (string) Str::of('halo dunia')->substr(5));
        $this->assertEquals(Str::ucfirst('halo'), (string) Str::of('halo')->ucfirst());
        $this->assertEquals(Str::lower('HALO'), (string) Str::of('HALO')->lower());
        $this->assertEquals(Str::upper('halo'), (string) Str::of('halo')->upper());
        $this->assertEquals(Str::title('halo dunia'), (string) Str::of('halo dunia')->title());
        $this->assertEquals(Str::limit('halo dunia panjang', 8), (string) Str::of('halo dunia panjang')->limit(8));
        $this->assertEquals(Str::trim('  halo  '), (string) Str::of('  halo  ')->trim());
        $this->assertEquals(Str::words('satu dua tiga', 2), (string) Str::of('satu dua tiga')->words(2));
        $this->assertEquals(Str::singular('books'), (string) Str::of('books')->singular());
        $this->assertEquals(Str::plural('book'), (string) Str::of('book')->plural());
        $this->assertEquals(Str::plural('book', 1), (string) Str::of('book')->plural(1));
        $this->assertEquals(Str::plural_studly('UserBook'), (string) Str::of('UserBook')->plural_studly());
        $this->assertEquals(Str::slug('Halo Dunia'), (string) Str::of('Halo Dunia')->slug());
        $this->assertEquals(Str::slug('Halo Dunia', '_'), (string) Str::of('Halo Dunia')->slug('_'));
        $this->assertEquals(Str::classify('user_profile'), (string) Str::of('user_profile')->classify());
        $this->assertEquals(Str::accentless('café'), (string) Str::of('café')->accentless());
        $this->assertEquals(Str::segments('/a/b/c/'), Str::of('/a/b/c/')->segments());
        $this->assertEquals(Str::censor('rahasia'), (string) Str::of('rahasia')->censor());
        $this->assertEquals(Str::before('halo@dunia', '@'), (string) Str::of('halo@dunia')->before('@'));
        $this->assertEquals(Str::after('halo@dunia', '@'), (string) Str::of('halo@dunia')->after('@'));
        $this->assertEquals(Str::camel('user_profile'), (string) Str::of('user_profile')->camel());
        $this->assertEquals(Str::studly('user_profile'), (string) Str::of('user_profile')->studly());
        $this->assertEquals(Str::kebab('userProfile'), (string) Str::of('userProfile')->kebab());
        $this->assertEquals(Str::snake('userProfile'), (string) Str::of('userProfile')->snake());
        $this->assertEquals(Str::snake('userProfile', '-'), (string) Str::of('userProfile')->snake('-'));
        $this->assertEquals(Str::start('halo', '/'), (string) Str::of('halo')->start('/'));
        $this->assertEquals(Str::finish('halo', '/'), (string) Str::of('halo')->finish('/'));
        $this->assertEquals(Str::parse_callback('Kelas@metode', null), Str::of('Kelas@metode')->parse_callback());
    }

    /**
     * The methods whose subject is not the first argument of the Str method
     * still get the string in the right place.
     *
     * @group system
     */
    public function testArgumentOrderIsKept()
    {
        $this->assertTrue(Str::of('halo-dunia')->is('halo-*'));
        $this->assertFalse(Str::of('lain')->is('halo-*'));

        $this->assertEquals(
            Str::replace_first('a', 'z', 'a b a'),
            (string) Str::of('a b a')->replace_first('a', 'z')
        );
        $this->assertEquals(
            Str::replace_last('a', 'z', 'a b a'),
            (string) Str::of('a b a')->replace_last('a', 'z')
        );
        $this->assertEquals(
            Str::replace_array('?', ['x', 'y'], '? dan ?'),
            (string) Str::of('? dan ?')->replace_array('?', ['x', 'y'])
        );
    }

    /**
     * The methods that answer with something other than a string do not wrap it.
     *
     * @group system
     */
    public function testAnswersThatAreNotStringsAreNotWrapped()
    {
        $this->assertInternalType('int', Str::of('halo')->length());
        $this->assertInternalType('bool', Str::of('halo')->is('h*'));
        $this->assertInternalType('bool', Str::of('halo')->contains('al'));
        $this->assertInternalType('bool', Str::of('halo')->contains_all(['ha', 'lo']));
        $this->assertInternalType('bool', Str::of('halo')->starts_with('ha'));
        $this->assertInternalType('bool', Str::of('halo')->ends_with('lo'));
        $this->assertInternalType('array', Str::of('/a/b')->segments());
        $this->assertInternalType('array', Str::of('a,b')->explode(','));
    }

    // -------------------------------------------------------------------------
    // Fluent helpers
    // -------------------------------------------------------------------------

    /**
     * The string is never changed in place.
     *
     * @group system
     */
    public function testTheStringIsImmutable()
    {
        $string = Str::of('halo');
        $upper = $string->upper();

        $this->assertEquals('halo', (string) $string);
        $this->assertEquals('HALO', (string) $upper);
        $this->assertNotSame($string, $upper);
    }

    /**
     * Methods chain into one another.
     *
     * @group system
     */
    public function testMethodsChain()
    {
        $this->assertEquals(
            'HALO-DUNIA-INI',
            (string) Str::of('  Halo Dunia Ini  ')->trim()->slug()->upper()
        );
        $this->assertEquals(
            '/halo_dunia/',
            (string) Str::of(' Halo Dunia ')->trim()->lower()->snake()->start('/')->finish('/')
        );
    }

    /**
     * append() and prepend() take any number of values.
     *
     * @group system
     */
    public function testAppendAndPrepend()
    {
        $this->assertEquals('halo dunia', (string) Str::of('halo')->append(' ', 'dunia'));
        $this->assertEquals('halo dunia', (string) Str::of('dunia')->prepend('halo', ' '));
        $this->assertEquals('halo', (string) Str::of('halo')->append());
    }

    /**
     * replace() and explode() answer the way their PHP counterparts do.
     *
     * @group system
     */
    public function testReplaceAndExplode()
    {
        $this->assertEquals('z b z', (string) Str::of('a b a')->replace('a', 'z'));
        $this->assertEquals(['a', 'b', 'c'], Str::of('a,b,c')->explode(','));
        $this->assertEquals(['a', 'b,c'], Str::of('a,b,c')->explode(',', 2));
    }

    /**
     * An empty delimiter throws on PHP 8 and answers FALSE before it, so it is
     * refused the same way on every version.
     *
     * @group system
     */
    public function testExplodeRefusesAnEmptyDelimiter()
    {
        try {
            Str::of('abc')->explode('');
            $this->fail('Expected the empty delimiter to be refused.');
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('must not be empty', $e->getMessage());
        }
    }

    /**
     * The emptiness of the string, which its truthiness cannot tell you.
     *
     * @group system
     */
    public function testEmptiness()
    {
        $this->assertTrue(Str::of('')->is_empty());
        $this->assertFalse(Str::of('0')->is_empty());
        $this->assertTrue(Str::of('0')->is_not_empty());
        $this->assertFalse(Str::of('')->is_not_empty());
    }

    /**
     * when() runs its callback only when the condition holds.
     *
     * @group system
     */
    public function testWhen()
    {
        $upper = function ($string) {
            return $string->upper();
        };

        $this->assertEquals('HALO', (string) Str::of('halo')->when(true, $upper));
        $this->assertEquals('halo', (string) Str::of('halo')->when(false, $upper));

        $this->assertEquals('Halo', (string) Str::of('halo')->when(false, $upper, function ($string) {
            return $string->title();
        }));

        $this->assertEquals('HALO', (string) Str::of('halo')->when(function ($string) {
            return 4 === $string->length();
        }, $upper));

        $this->assertEquals('halo', (string) Str::of('halo')->when(true, function () {
            return null;
        }));
    }

    /**
     * unless() is when() the other way round.
     *
     * @group system
     */
    public function testUnless()
    {
        $upper = function ($string) {
            return $string->upper();
        };

        $this->assertEquals('HALO', (string) Str::of('halo')->unless(false, $upper));
        $this->assertEquals('halo', (string) Str::of('halo')->unless(true, $upper));
    }

    /**
     * pipe() keeps what the callback answers, tap() keeps the string.
     *
     * @group system
     */
    public function testPipeAndTap()
    {
        $this->assertEquals('HALO', (string) Str::of('halo')->pipe('strtoupper'));
        $this->assertInstanceOf('System\Stringable', Str::of('halo')->pipe('strtoupper'));

        $seen = null;
        $result = Str::of('halo')->tap(function ($string) use (&$seen) {
            $seen = (string) $string;
        })->upper();

        $this->assertEquals('halo', $seen);
        $this->assertEquals('HALO', (string) $result);
    }

    // -------------------------------------------------------------------------
    // Macros
    // -------------------------------------------------------------------------

    /**
     * A macro on Str can be called on the fluent string too, and a macro that
     * answers with a string keeps the chain going.
     *
     * @group system
     */
    public function testMacrosAreReachable()
    {
        Str::macro('bungkus', function ($value, $mark) {
            return $mark . $value . $mark;
        });

        $this->assertEquals('*halo*', (string) Str::of('halo')->bungkus('*'));
        $this->assertEquals('*HALO*', (string) Str::of('halo')->bungkus('*')->upper());
        $this->assertInstanceOf('System\Stringable', Str::of('halo')->bungkus('*'));
    }

    /**
     * A macro that answers with something else is handed back untouched.
     *
     * @group system
     */
    public function testMacroAnswerThatIsNotAStringIsNotWrapped()
    {
        Str::macro('panjangnya', function ($value) {
            return strlen($value);
        });

        $this->assertSame(4, Str::of('halo')->panjangnya());
    }

    /**
     * A method that is nowhere says so.
     *
     * @group system
     *
     * @expectedException BadMethodCallException
     */
    public function testUnknownMethodThrows()
    {
        Str::of('halo')->tidak_ada();
    }

    /**
     * A macro may not take the name of a method the fluent string already has,
     * or the same name would mean two things.
     *
     * @group system
     */
    public function testMacroCannotTakeTheNameOfAFluentMethod()
    {
        try {
            Str::macro('append', function () {
            });
            $this->fail('Expected the macro name to be refused.');
        } catch (\Exception $e) {
            $this->assertContains('Stringable::append()', $e->getMessage());
        }

        try {
            Str::macro('of', function () {
            });
            $this->fail('Expected the macro name to be refused.');
        } catch (\Exception $e) {
            $this->assertContains('Str::of()', $e->getMessage());
        }
    }
}

/**
 * An object that knows how to become a string.
 */
class StringableProbe
{
    /**
     * Get the string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'dari objek';
    }
}
