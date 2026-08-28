<?php

namespace System;

defined('DS') or exit('No direct access.');

class Redirect extends Response
{
    /**
     * Key used to remember the url a guest was heading to.
     *
     * @var string
     */
    const INTENDED = 'rakit_intended_url';

    /**
     * Create a redirect response to the home page.
     *
     * @param int $status
     *
     * @return Redirect|mixed
     */
    public static function home($status = 302)
    {
        return static::to(URL::home(), $status);
    }

    /**
     * Create a redirect response to the previous page.
     *
     * @param int $status
     *
     * @return Redirect|mixed
     */
    public static function back($status = 302, $fallback = false)
    {
        $referrer = Request::referrer();

        if (! static::local($referrer)) {
            $referrer = $fallback ? $fallback : '/';
        }

        return static::to($referrer, $status);
    }

    /**
     * Check whether a URL points back at this application. Anything naming
     * another host does not, and the referrer header is written by the client.
     *
     * @param string $url
     *
     * @return bool
     */
    protected static function local($url)
    {
        if (! is_string($url) || '' === trim($url)) {
            return false;
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        return is_null($host) || $host === Request::foundation()->getHost();
    }

    /**
     * Create a redirect response to a given URL.
     *
     * @param string $url
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function to($url, $status = 302)
    {
        return static::make('', $status)->header('Location', URL::to($url));
    }

    /**
     * Create a redirect to a given controller action.
     *
     * @param string $action
     * @param array  $parameters
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function to_action($action, array $parameters = [], $status = 302)
    {
        return static::to(URL::to_action($action, $parameters), $status);
    }

    /**
     * Create a redirect to a named route.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function to_route($route, array $parameters = [], $status = 302)
    {
        return static::to(URL::to_route($route, $parameters), $status);
    }

    /**
     * Create a redirect response to an external url, without passing it through URL::to().
     *
     * @param string $url
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function away($url, $status = 302)
    {
        return static::make('', $status)->header('Location', $url);
    }

    /**
     * Create a redirect response to a https url.
     *
     * @param string $url
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function secure($url, $status = 302)
    {
        return static::away(Str::replace_first('http://', 'https://', URL::to($url)), $status);
    }

    /**
     * Create a redirect response to the very same url.
     *
     * @param int $status
     *
     * @return Redirect|mixed
     */
    public static function refresh($status = 302)
    {
        return static::to(URI::current(), $status);
    }

    /**
     * Remember the current url, then redirect to the given one.
     * Use intended() afterwards to go back to where the visitor came from.
     *
     * @param string $url
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function guest($url, $status = 302)
    {
        static::guard_session('guest');
        Session::put(static::INTENDED, URL::current());

        return static::to($url, $status);
    }

    /**
     * Redirect to the url that was remembered by guest(), or to the default one.
     *
     * @param string $default
     * @param int    $status
     *
     * @return Redirect|mixed
     */
    public static function intended($default = '/', $status = 302)
    {
        static::guard_session('intended');
        $url = Session::get(static::INTENDED, $default);
        Session::forget(static::INTENDED);

        return static::to($url, $status);
    }

    /**
     * Make sure a session driver is configured.
     *
     * @param string $method
     */
    protected static function guard_session($method)
    {
        if ('' === Config::get('session.driver', '')) {
            throw new \Exception(sprintf('A session driver must be set before using Redirect::%s().', $method));
        }
    }

    /**
     * Add an item to the flash data (stored in session).
     * Flash data will be available on the next request.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Redirect|mixed
     */
    public function with($key, $value)
    {
        if ('' === Config::get('session.driver', '')) {
            throw new \Exception('A session driver must be set before setting flash data.');
        }

        Session::flash($key, $value);
        return $this;
    }

    /**
     * Flash old input data to the session and return the Redirect instance.
     * After old input data is flashed, you can retrieve it using Input::old().
     *
     * @param string $filter
     * @param array  $items
     *
     * @return Redirect|mixed
     */
    public function with_input($filter = null, array $items = [])
    {
        Input::flash($filter, $items);
        return $this;
    }

    /**
     * Flash an error message to the session.
     *
     * @param Validator|Messages $container
     *
     * @return Redirect|mixed
     */
    public function with_errors($container)
    {
        return $this->with('errors', ($container instanceof Validator) ? $container->errors : $container);
    }

    /**
     * Send the redirect response to the browser.
     */
    public function send()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return parent::send();
    }
}
