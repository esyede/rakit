<?php

namespace System;

defined('DS') or exit('No direct access.');

class Redirect extends Response
{
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
    public static function back($status = 302)
    {
        return static::to(Request::referrer(), $status);
    }

    /**
     * Create a redirect response to a given URL.
     *
     * <code>
     *
     *      // Create a redirect to a specific location
     *      return Redirect::to('user/profile');
     *      return Redirect::to('https://google.com');
     *
     *      // Create a redirect with status code 301
     *      return Redirect::to('user/profile', 301);
     *
     * </code>
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
     * <code>
     *
     *      // Create a redirect to the 'login' named route
     *      return Redirect::to_route('login');
     *
     *      // Create a redirect to the 'profile' named route with additional parameters
     *      return Redirect::to_route('profile', [$name]);
     *
     * </code>
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
     * Add an item to the flash data (stored in session).
     * Flash data will be available on the next request.
     *
     * <code>
     *
     *      // Create a redirect with flash data.
     *      return Redirect::to('profile')->with('message', 'Welcome back!');
     *
     * </code>
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
     * <code>
     *
     *      // Redirect and flash all input data to the session.
     *      return Redirect::to('login')->with_input();
     *
     *      // Redirect and flash only some input data to the session.
     *      return Redirect::to('login')->with_input('only', ['email', 'name']);
     *
     *      // Redirect and flash all input data except the specified items
     *      return Redirect::to('login')->with_input('except', ['password', 'email']);
     *
     * </code>
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
     * <code>
     *
     *      // Redirect and flash an error message to the session.
     *      return Redirect::to('register')->with_error('email', 'Email is required');
     *
     * </code>
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
