<?php

defined('DS') or exit('No direct access.');

use System\Collection;
use System\Lang;
use System\Request;
use System\Response;
use System\Str;

class SupportExtrasTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Put language lines in place for the test.
     *
     * @param string $language
     * @param array  $lines
     */
    protected function lines($language, array $lines)
    {
        $property = new \ReflectionProperty('System\Lang', 'lines');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $property->setAccessible(true);

        $all = $property->getValue();
        $all[DEFAULT_PACKAGE][$language]['extras'] = $lines;
        $property->setValue(null, $all);
    }

    /**
     * Response, Collection and Request take macros; Request still reaches its foundation.
     *
     * @group system
     */
    public function testMacros()
    {
        Collection::macro('evens', function () {
            return $this->filter(function ($value) {
                return 0 === $value % 2;
            })->values();
        });

        Response::macro('shout', function ($text) {
            return Response::make(strtoupper($text));
        });

        Request::macro('extras_marker', function () {
            return 'marked';
        });

        $collection = new Collection([1, 2, 3, 4]);
        $this->assertSame([2, 4], $collection->evens()->all());
        $this->assertEquals('HI', Response::shout('hi')->content);
        $this->assertEquals('marked', Request::extras_marker());
        $this->assertTrue(Collection::has_macro('evens'));
        $this->assertFalse(Response::has_macro('evens'));
        $this->assertInternalType('string', Request::getMethod());
    }

    /**
     * Lang::choice() and trans_choice(): exact counts, ranges, plural forms, :count.
     *
     * @group system
     */
    public function testChoice()
    {
        $this->lines('en', [
            'apples' => '{0} No apples|{1} One apple|[2,9] :count apples|[10,*] Many apples',
            'items' => 'One item|:count items',
            'named' => ':name has one|:name has :count',
        ]);
        $this->lines('id', ['items' => ':count barang|:count barang lain']);

        $this->assertEquals('No apples', Lang::choice('extras.apples', 0, [], 'en'));
        $this->assertEquals('One apple', Lang::choice('extras.apples', 1, [], 'en'));
        $this->assertEquals('5 apples', Lang::choice('extras.apples', 5, [], 'en'));
        $this->assertEquals('Many apples', trans_choice('extras.apples', 25, [], 'en'));
        $this->assertEquals('One item', Lang::choice('extras.items', 1, [], 'en'));
        $this->assertEquals('3 items', Lang::choice('extras.items', 3, [], 'en'));
        $this->assertEquals('Budi has 2', Lang::choice('extras.named', 2, ['name' => 'Budi'], 'en'));

        // Indonesian has no plural form: the first segment for every count.
        $this->assertEquals('3 barang', Lang::choice('extras.items', 3, [], 'id'));

        $this->assertEquals('extras.missing', Lang::choice('extras.missing', 2, [], 'en'));
    }

    /**
     * The new string helpers, on Str and chained on Stringable.
     *
     * @group system
     */
    public function testStringHelpers()
    {
        $this->assertEquals('bu**************', Str::mask('budi@example.com', '*', 2));
        $this->assertEquals('bu*****ample.com', Str::mask('budi@example.com', '*', 2, 5));
        $this->assertEquals('budi@example.***', Str::mask('budi@example.com', '*', -3));
        $this->assertEquals('ab***', Str::mask('abcde', '*', 2, 99));
        $this->assertEquals('héll***o', Str::mask('héllo wo', '*', 4, 3)); // counted in characters

        $this->assertEquals('laravel framework', Str::squish("  laravel \t\n  framework  "));
        $this->assertEquals('User Profile Picture Uploaded', Str::headline('user_profile-pictureUploaded'));
        $this->assertEquals('Email Notification Sent', Str::headline('EmailNotificationSent'));

        $this->assertEquals('...is a beautiful morn...', Str::excerpt('This is a beautiful morning', 'beautiful', ['radius' => 5]));
        $this->assertNull(Str::excerpt('abc', 'zzz'));

        $this->assertEquals('b', Str::between('a[b]c', '[', ']'));
        $this->assertEquals('b][c', Str::between('a[b][c]', '[', ']'));
        $this->assertEquals('b', Str::between_first('a[b][c]', '[', ']'));
        $this->assertEquals('ext', Str::after_last('file.tar.ext', '.'));
        $this->assertEquals('file.tar', Str::before_last('file.tar.ext', '.'));

        $this->assertEquals('"x"', Str::wrap('x', '"'));
        $this->assertEquals('x', Str::unwrap('[x]', '[', ']'));
        $this->assertEquals('--é--', Str::pad_both('é', 5, '-'));
        $this->assertEquals('00042', Str::pad_left('42', 5, '0'));
        $this->assertEquals('ab...', Str::pad_right('ab', 5, '.'));

        $this->assertTrue(Str::is_url('https://example.com/a?b=1'));
        $this->assertFalse(Str::is_url('ftp://example.com', ['http', 'https']));
        $this->assertFalse(Str::is_url('not a url'));
        $this->assertTrue(Str::is_json('{"a":1}'));
        $this->assertFalse(Str::is_json('{a:1}'));
        $this->assertTrue(Str::is_ascii('abc'));
        $this->assertFalse(Str::is_ascii('ábc'));
        $this->assertTrue(Str::is_uuid('3f2504e0-4f89-11d3-9a0c-0305e82c3301'));
        $this->assertFalse(Str::is_uuid('3f2504e0'));

        $this->assertEquals('Burritos are great', Str::swap(['Tacos' => 'Burritos'], 'Tacos are great'));
        $this->assertEquals('hél', Str::take('héllo', 3));
        $this->assertEquals('lo', Str::take('héllo', -2));
        $this->assertEquals('olléh', Str::reverse('héllo'));
        $this->assertSame(1, Str::position('héllo', 'é'));
        $this->assertEquals('a b c', Str::deduplicate('a   b  c'));
        $this->assertEquals(['Foo', 'Bar'], Str::ucsplit('FooBar'));

        // Chained: any Str helper taking the string first works on Stringable.
        $this->assertEquals('Hello World', (string) Str::of('  hello_world  ')->squish()->headline());
        $this->assertEquals('ab***', (string) Str::of('abcde')->mask('*', 2));
    }
}
