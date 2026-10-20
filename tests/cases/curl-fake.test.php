<?php

defined('DS') or exit('No direct access.');

use System\Curl;

class CurlFakeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tear down.
     */
    public function tearDown()
    {
        Curl::fake(false);
        Curl::clear_default_headers();
    }

    /**
     * Patterns answer matching URLs with responses shaped like real ones.
     *
     * @group system
     */
    public function testPatternsAnswer()
    {
        $calls = 0;

        Curl::fake([
            'api.github.com/users/*' => Curl::response(['login' => 'esyede'], 201, ['X-Rate' => '59']),
            'example.com/text' => 'plain',
            'example.com/counted' => function ($request) use (&$calls) {
                ++$calls;
                return Curl::response(['method' => $request['method'], 'call' => $calls], (1 === $calls) ? 500 : 200);
            },
        ]);

        $user = Curl::get('https://api.github.com/users/esyede');
        $this->assertSame(201, $user->code);
        $this->assertEquals('esyede', $user->body->login);
        $this->assertEquals('{"login":"esyede"}', $user->raw_body);
        $this->assertEquals('59', $user->headers['X-Rate']);

        $this->assertEquals('plain', Curl::get('http://example.com/text')->body);

        // A closure decides per request: a retry gets a different answer.
        $this->assertSame(500, Curl::post('https://example.com/counted')->code);
        $second = Curl::post('https://example.com/counted');
        $this->assertSame(200, $second->code);
        $this->assertEquals('POST', $second->body->method);
    }

    /**
     * Requests are recorded, with query, headers and body, for the assertions.
     *
     * @group system
     */
    public function testRecordsAndAsserts()
    {
        Curl::fake();
        Curl::default_header('Authorization', 'Bearer abc');

        $this->assertSame(200, Curl::get('https://api.example.com/items', ['Accept' => 'application/json'], ['page' => 2])->code);
        Curl::post('https://api.example.com/items', [], Curl::body_json(['name' => 'Kopi']));

        Curl::assert_sent_count(2);
        Curl::assert_sent(function ($request) {
            return 'GET' === $request['method']
                && 'https://api.example.com/items?page=2' === $request['url']
                && 'Bearer abc' === $request['headers']['authorization']
                && 'application/json' === $request['headers']['accept'];
        });
        Curl::assert_sent(function ($request) {
            return 'POST' === $request['method'] && '{"name":"Kopi"}' === $request['body'];
        });
        Curl::assert_not_sent(function ($request) {
            return 'DELETE' === $request['method'];
        });

        $this->assertCount(1, Curl::recorded(function ($request) {
            return 'POST' === $request['method'];
        }));

        Curl::fake();
        Curl::assert_nothing_sent();
    }

    /**
     * An unmatched request throws instead of reaching the network; a failed assertion fails.
     *
     * @group system
     */
    public function testStrayRequestsAndFailures()
    {
        Curl::fake(['example.com/*' => 'ok']);

        try {
            Curl::get('https://other.test/x');
            $this->fail('stray request answered');
        } catch (\Exception $e) {
            $this->assertContains('No fake response for the HTTP request: GET https://other.test/x', $e->getMessage());
        }

        $this->setExpectedException('PHPUnit_Framework_ExpectationFailedException');
        Curl::assert_sent(function ($request) {
            return 'PUT' === $request['method'];
        });
    }
}
