<?php

namespace System\Testing;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Session;
use System\URL;

class Response
{
    /**
     * The response the application returned.
     *
     * @var \System\Response
     */
    public $original;

    /**
     * The prepared foundation response, with its final headers and cookies.
     *
     * @var \System\Foundation\Http\Response
     */
    public $foundation;

    /**
     * The body as the client receives it, echoed output included.
     *
     * @var string
     */
    public $content;

    /**
     * Constructor.
     *
     * @param \System\Response                 $original
     * @param \System\Foundation\Http\Response $foundation
     * @param string                           $content
     */
    public function __construct($original, $foundation, $content)
    {
        $this->original = $original;
        $this->foundation = $foundation;
        $this->content = (string) $content;
    }

    /**
     * Get the status code.
     *
     * @return int
     */
    public function status()
    {
        return $this->foundation->getStatusCode();
    }

    /**
     * Get a header, or NULL.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function header($name)
    {
        return $this->foundation->headers->get($name);
    }

    /**
     * Get the decoded JSON body, or a value in it by "dot" key.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function json($key = null)
    {
        $json = json_decode($this->content, true);
        return is_null($key) ? $json : Arr::get((array) $json, $key);
    }

    /**
     * Assert the status code.
     *
     * @param int $status
     *
     * @return $this
     */
    public function assert_status($status)
    {
        \PHPUnit_Framework_Assert::assertSame(
            (int) $status,
            $this->status(),
            sprintf('Expected status %d, got %d.%s', $status, $this->status(), $this->excerpt())
        );

        return $this;
    }

    /**
     * Assert a 200 OK status.
     *
     * @return $this
     */
    public function assert_ok()
    {
        return $this->assert_status(200);
    }

    /**
     * Assert a 201 Created status.
     *
     * @return $this
     */
    public function assert_created()
    {
        return $this->assert_status(201);
    }

    /**
     * Assert a 204 No Content status.
     *
     * @return $this
     */
    public function assert_no_content()
    {
        return $this->assert_status(204);
    }

    /**
     * Assert a 401 Unauthorized status.
     *
     * @return $this
     */
    public function assert_unauthorized()
    {
        return $this->assert_status(401);
    }

    /**
     * Assert a 403 Forbidden status.
     *
     * @return $this
     */
    public function assert_forbidden()
    {
        return $this->assert_status(403);
    }

    /**
     * Assert a 404 Not Found status.
     *
     * @return $this
     */
    public function assert_not_found()
    {
        return $this->assert_status(404);
    }

    /**
     * Assert a 422 Unprocessable Entity status.
     *
     * @return $this
     */
    public function assert_unprocessable()
    {
        return $this->assert_status(422);
    }

    /**
     * Assert a redirect, to the given URI or URL when there is one.
     *
     * @param string|null $to
     *
     * @return $this
     */
    public function assert_redirect($to = null)
    {
        \PHPUnit_Framework_Assert::assertTrue(
            $this->foundation->isRedirect(),
            sprintf('Expected a redirect, got status %d.', $this->status())
        );

        if (! is_null($to)) {
            \PHPUnit_Framework_Assert::assertEquals(URL::to($to), $this->header('Location'));
        }

        return $this;
    }

    /**
     * Assert a header is present, with the given value when there is one.
     *
     * @param string      $name
     * @param string|null $value
     *
     * @return $this
     */
    public function assert_header($name, $value = null)
    {
        \PHPUnit_Framework_Assert::assertTrue(
            $this->foundation->headers->has($name),
            sprintf('Header [%s] is missing.', $name)
        );

        if (! is_null($value)) {
            \PHPUnit_Framework_Assert::assertEquals($value, $this->header($name));
        }

        return $this;
    }

    /**
     * Assert a header is absent.
     *
     * @param string $name
     *
     * @return $this
     */
    public function assert_header_missing($name)
    {
        \PHPUnit_Framework_Assert::assertFalse(
            $this->foundation->headers->has($name),
            sprintf('Header [%s] is present.', $name)
        );

        return $this;
    }

    /**
     * Assert the response sets a cookie.
     *
     * @param string $name
     *
     * @return $this
     */
    public function assert_cookie($name)
    {
        $names = [];

        foreach ($this->foundation->headers->getCookies() as $cookie) {
            $names[] = $cookie->getName();
        }

        \PHPUnit_Framework_Assert::assertContains($name, $names, sprintf('Cookie [%s] is not set.', $name));

        return $this;
    }

    /**
     * Assert the body contains the text. Escaped with HTML entities unless told not to.
     *
     * @param string $text
     * @param bool   $escape
     *
     * @return $this
     */
    public function assert_see($text, $escape = true)
    {
        $text = $escape ? htmlentities((string) $text, ENT_QUOTES, 'UTF-8', false) : (string) $text;
        \PHPUnit_Framework_Assert::assertContains($text, $this->content);

        return $this;
    }

    /**
     * Assert the body does not contain the text.
     *
     * @param string $text
     * @param bool   $escape
     *
     * @return $this
     */
    public function assert_dont_see($text, $escape = true)
    {
        $text = $escape ? htmlentities((string) $text, ENT_QUOTES, 'UTF-8', false) : (string) $text;
        \PHPUnit_Framework_Assert::assertNotContains($text, $this->content);

        return $this;
    }

    /**
     * Assert the JSON body contains the given data: every key given must be there with
     * that value, other keys may exist too.
     *
     * @param array $data
     *
     * @return $this
     */
    public function assert_json(array $data)
    {
        $json = $this->json();

        \PHPUnit_Framework_Assert::assertTrue(is_array($json), 'The response is not JSON.'.$this->excerpt());
        \PHPUnit_Framework_Assert::assertEquals($data, static::subset($data, $json));

        return $this;
    }

    /**
     * Assert a value in the JSON body, by "dot" key.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function assert_json_path($key, $value)
    {
        \PHPUnit_Framework_Assert::assertEquals($value, $this->json($key), sprintf('JSON path [%s] differs.', $key));

        return $this;
    }

    /**
     * Assert the session holds a key, with the given value when there is one.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function assert_session_has($key, $value = null)
    {
        $actual = Session::started() ? Session::get($key) : null;

        \PHPUnit_Framework_Assert::assertNotNull($actual, sprintf('Session is missing [%s].', $key));

        if (func_num_args() > 1) {
            \PHPUnit_Framework_Assert::assertEquals($value, $actual);
        }

        return $this;
    }

    /**
     * Assert the session does not hold a key.
     *
     * @param string $key
     *
     * @return $this
     */
    public function assert_session_missing($key)
    {
        \PHPUnit_Framework_Assert::assertNull(
            Session::started() ? Session::get($key) : null,
            sprintf('Session has [%s].', $key)
        );

        return $this;
    }

    /**
     * Assert validation errors were flashed, for the given fields when there are any.
     *
     * @param array|string $keys
     *
     * @return $this
     */
    public function assert_session_has_errors($keys = [])
    {
        $errors = Session::started() ? Session::get('errors') : null;

        \PHPUnit_Framework_Assert::assertInstanceOf('System\Messages', $errors, 'No validation errors were flashed.');

        foreach ((array) $keys as $key) {
            \PHPUnit_Framework_Assert::assertTrue($errors->has($key), sprintf('No validation error for [%s].', $key));
        }

        return $this;
    }

    /**
     * Keep the parts of an array that a subset names.
     *
     * @param array $subset
     * @param array $array
     *
     * @return array
     */
    protected static function subset(array $subset, array $array)
    {
        $kept = [];

        foreach ($subset as $key => $value) {
            if (! array_key_exists($key, $array)) {
                continue;
            }

            $kept[$key] = (is_array($value) && is_array($array[$key]))
                ? static::subset($value, $array[$key])
                : $array[$key];
        }

        return $kept;
    }

    /**
     * Get the start of the body, to show in a failure.
     *
     * @return string
     */
    protected function excerpt()
    {
        $body = trim(strip_tags($this->content));
        return ('' === $body) ? '' : PHP_EOL.'Body: '.mb_substr(preg_replace('/\s+/', ' ', $body), 0, 300);
    }
}
