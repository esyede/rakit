<?php

defined('DS') or exit('No direct access.');

use System\Input;
use System\Messages;
use System\Redirect;
use System\Request;
use System\Response;
use System\Validator;

class SupportParityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Request::foundation()->query->replace([]);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Request::foundation()->query->replace([]);
    }

    /**
     * Put the given values in the query string of the current request.
     *
     * @param array $values
     */
    protected function input(array $values)
    {
        Request::foundation()->query->replace($values);
    }

    // -------------------------------------------------------------------------
    // Input
    // -------------------------------------------------------------------------

    /**
     * Test Input::boolean().
     *
     * @group system
     */
    public function testInputBoolean()
    {
        $this->input([
            'a' => '1', 'b' => 'true', 'c' => 'on', 'd' => 'yes',
            'e' => '0', 'f' => 'false', 'g' => 'off', 'h' => 'sembarang',
        ]);

        foreach (['a', 'b', 'c', 'd'] as $key) {
            $this->assertTrue(Input::boolean($key), 'gagal untuk ' . $key);
        }

        foreach (['e', 'f', 'g', 'h'] as $key) {
            $this->assertFalse(Input::boolean($key), 'gagal untuk ' . $key);
        }

        $this->assertFalse(Input::boolean('tidak_ada'));
        $this->assertTrue(Input::boolean('tidak_ada', true));
    }

    /**
     * Test the numeric and string accessors of Input.
     *
     * @group system
     */
    public function testInputTypedAccessors()
    {
        $this->input(['n' => '42', 'f' => '3.5', 's' => 'halo', 'a' => ['x', 'y']]);

        $this->assertSame(42, Input::integer('n'));
        $this->assertSame(0, Input::integer('s'));
        $this->assertSame(7, Input::integer('s', 7));
        $this->assertSame(3.5, Input::float('f'));
        $this->assertSame('halo', Input::string('s'));
        $this->assertSame('', Input::string('tidak_ada'));
        $this->assertEquals(['x', 'y'], Input::arr('a'));
        $this->assertInstanceOf('\System\Collection', Input::collect());
    }

    /**
     * Test Input::date().
     *
     * @group system
     */
    public function testInputDate()
    {
        $this->input(['d' => '2026-05-05', 'kosong' => '']);

        $this->assertInstanceOf('\System\Carbon', Input::date('d'));
        $this->assertEquals('2026-05-05', Input::date('d')->format('Y-m-d'));
        $this->assertNull(Input::date('kosong'));
        $this->assertNull(Input::date('tidak_ada'));
    }

    /**
     * Test Input::keys(), missing() and any_filled().
     *
     * @group system
     */
    public function testInputPresenceHelpers()
    {
        $this->input(['a' => '1', 'b' => '']);

        $this->assertEquals(['a', 'b'], Input::keys());
        $this->assertTrue(Input::missing('c'));
        $this->assertFalse(Input::missing('a'));
        $this->assertTrue(Input::any_filled(['b', 'a']));
        $this->assertFalse(Input::any_filled(['b', 'c']));
    }

    // -------------------------------------------------------------------------
    // Messages
    // -------------------------------------------------------------------------

    /**
     * Test the collection interfaces of Messages.
     *
     * @group system
     */
    public function testMessagesImplementsCollectionInterfaces()
    {
        $messages = new Messages(['nama' => ['wajib diisi']]);

        $this->assertInstanceOf('\Countable', $messages);
        $this->assertInstanceOf('\ArrayAccess', $messages);
        $this->assertInstanceOf('\IteratorAggregate', $messages);
        $this->assertInstanceOf('\JsonSerializable', $messages);

        $this->assertEquals(1, count($messages));
        $this->assertEquals(['wajib diisi'], $messages['nama']);
        $this->assertTrue(isset($messages['nama']));
        $this->assertFalse(isset($messages['umur']));

        $messages['umur'] = 'harus angka';

        $this->assertEquals(['harus angka'], $messages->get('umur'));

        unset($messages['umur']);

        $this->assertFalse($messages->has('umur'));
    }

    /**
     * Test merge(), forget(), keys() and has_any().
     *
     * @group system
     */
    public function testMessagesBagHelpers()
    {
        $messages = new Messages(['nama' => ['wajib diisi']]);
        $messages->merge(['umur' => ['harus angka'], 'nama' => ['terlalu pendek']]);

        $this->assertEquals(['nama', 'umur'], $messages->keys());
        $this->assertEquals(2, count($messages->get('nama')));
        $this->assertTrue($messages->has_any(['umur', 'tidak_ada']));
        $this->assertFalse($messages->has_any(['tidak_ada']));
        $this->assertFalse($messages->is_empty());
        $this->assertTrue($messages->is_not_empty());

        $messages->forget('umur');

        $this->assertEquals(['nama'], $messages->keys());
        $this->assertTrue((new Messages())->is_empty());
    }

    /**
     * Test the array and json form of Messages.
     *
     * @group system
     */
    public function testMessagesSerialization()
    {
        $messages = new Messages(['nama' => ['wajib diisi']]);

        $this->assertEquals(['nama' => ['wajib diisi']], $messages->to_array());
        $this->assertEquals($messages->to_array(), $messages->jsonSerialize());
        $this->assertJsonStringEqualsJsonString('{"nama":["wajib diisi"]}', $messages->to_json());
        $this->assertJsonStringEqualsJsonString('{"nama":["wajib diisi"]}', json_encode($messages));
    }

    // -------------------------------------------------------------------------
    // Response and Redirect
    // -------------------------------------------------------------------------

    /**
     * Test Response::no_content().
     *
     * @group system
     */
    public function testResponseNoContent()
    {
        $response = Response::no_content();

        $this->assertEquals(204, $response->foundation()->getStatusCode());
        $this->assertEquals('', $response->content);
    }

    /**
     * Test Response::file().
     *
     * @group system
     */
    public function testResponseFile()
    {
        $path = path('storage') . 'parity-file.txt';
        file_put_contents($path, 'isi berkas');

        $response = Response::file($path);
        $disposition = $response->foundation()->headers->get('Content-Disposition');

        unlink($path);

        $this->assertEquals('isi berkas', $response->content);
        $this->assertContains('inline', $disposition);
        $this->assertContains('parity-file.txt', $disposition);
    }

    /**
     * Test that Redirect::back() falls back instead of tripping over a null referrer.
     *
     * @group system
     */
    public function testRedirectBackUsesTheFallback()
    {
        $location = Redirect::back(302, '/dasbor')->foundation()->headers->get('Location');

        $this->assertContains('/dasbor', $location);
    }

    /**
     * Test Redirect::away().
     *
     * @group system
     */
    public function testRedirectAwayKeepsTheUrlUntouched()
    {
        $location = Redirect::away('https://contoh.test/halaman')->foundation()->headers->get('Location');

        $this->assertEquals('https://contoh.test/halaman', $location);
    }

    /**
     * Test that guest() and intended() complain when there is no session driver.
     *
     * @group system
     */
    public function testGuestNeedsASessionDriver()
    {
        $driver = \System\Config::get('session.driver');
        \System\Config::set('session.driver', '');

        try {
            Redirect::guest('/masuk');
            $this->fail('Redirect::guest() should complain without a session driver.');
        } catch (\Exception $e) {
            $this->assertContains('session driver', $e->getMessage());
        }

        \System\Config::set('session.driver', $driver);
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    /**
     * Test the newly added validation rules.
     *
     * @group system
     */
    public function testNewValidationRules()
    {
        $cases = [
            [['d' => '2026-05-05'], ['d' => 'after_or_equal:2026-05-05'], true],
            [['d' => '2026-05-04'], ['d' => 'after_or_equal:2026-05-05'], false],
            [['d' => '2026-05-05'], ['d' => 'before_or_equal:2026-05-05'], true],
            [['s' => 'halo'], ['s' => 'lowercase'], true],
            [['s' => 'Halo'], ['s' => 'lowercase'], false],
            [['s' => 'HALO'], ['s' => 'uppercase'], true],
            [['s' => 'abc-123'], ['s' => 'ascii'], true],
            [['s' => 'hallö'], ['s' => 'ascii'], false],
            [['n' => '10.55'], ['n' => 'decimal:2'], true],
            [['n' => '10.5'], ['n' => 'decimal:2'], false],
            [['n' => 10], ['n' => 'multiple_of:5'], true],
            [['n' => 11], ['n' => 'multiple_of:5'], false],
            [['n' => '1234'], ['n' => 'max_digits:4'], true],
            [['n' => '12345'], ['n' => 'max_digits:4'], false],
            [['n' => '12'], ['n' => 'min_digits:3'], false],
            [['s' => 'abc'], ['s' => 'doesnt_start_with:x,y'], true],
            [['s' => 'abc'], ['s' => 'doesnt_start_with:a'], false],
            [['s' => 'abc'], ['s' => 'doesnt_end_with:c'], false],
            [['a' => ['x', 'y']], ['a' => 'contains:x'], true],
            [['a' => ['x', 'y']], ['a' => 'contains:z'], false],
            [['a' => ['x', 'y']], ['a' => 'list'], true],
            [['a' => ['k' => 'v']], ['a' => 'list'], false],
            [['p' => ''], ['p' => 'prohibited'], true],
            [['d' => 'no'], ['d' => 'declined'], true],
            [['m' => '00:1B:44:11:3A:B7'], ['m' => 'mac_address'], true],
            [['m' => 'bukan-mac'], ['m' => 'mac_address'], false],
            [['u' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'], ['u' => 'ulid'], true],
            [['c' => '#ff8800'], ['c' => 'hex_color'], true],
            [['c' => 'ff8800'], ['c' => 'hex_color'], false],
        ];

        foreach ($cases as $index => $case) {
            list($data, $rules, $expected) = $case;
            $validator = Validator::make($data, $rules);

            $this->assertEquals(
                $expected,
                $validator->passes(),
                'kasus ke-' . $index . ': ' . json_encode($rules)
            );
        }
    }

    /**
     * Test that the new rules carry a message.
     *
     * @group system
     */
    public function testNewValidationRulesCarryAMessage()
    {
        $validator = Validator::make(['n' => '1'], ['n' => 'min_digits:3']);

        $this->assertTrue($validator->fails());
        $this->assertNotEquals('', $validator->errors->first('n'));
        $this->assertNotContains('min_digits', $validator->errors->first('n'));
        $this->assertContains('3', $validator->errors->first('n'));
    }
}
