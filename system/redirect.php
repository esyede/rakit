<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Redirect extends Response
{
    /**
     * Buat respon redirect ke root aplikasi.
     *
     * @param int $status
     *
     * @return Redirect
     */
    public static function home($status = 302)
    {
        return static::to(URL::home(), $status);
    }

    /**
     * Buat respon redirect ke halaman sebelumnya.
     *
     * @param int $status
     *
     * @return Redirect
     */
    public static function back($status = 302)
    {
        return static::to(Request::referrer(), $status);
    }

    /**
     * Buat respon redirect.
     *
     * <code>
     *
     *      // Buat redirect ke lokasi tertentu dalam lingkup aplikasi
     *      return Redirect::to('user/profile');
     *
     *      // Buat redirect dengan status code 301
     *      return Redirect::to('user/profile', 301);
     *
     * </code>
     *
     * @param string $url
     * @param int    $status
     *
     * @return Redirect
     */
    public static function to($url, $status = 302)
    {
        return static::make('', $status)->header('Location', URL::to($url));
    }

    /**
     * Buat respon redirect sebuah action milik controller.
     *
     * @param string $action
     * @param array  $parameters
     * @param int    $status
     *
     * @return Redirect
     */
    public static function to_action($action, $parameters = [], $status = 302)
    {
        return static::to(URL::to_action($action, $parameters), $status);
    }

    /**
     * Buat respon redirect ke named route.
     *
     * <code>
     *
     *      // Buat respon redirect ke named route bernama 'login'
     *      return Redirect::to_route('login');
     *
     *      // Buat respon redirect ke named route bernama 'profile' dengan parameter tambahan
     *      return Redirect::to_route('profile', [$username]);
     *
     * </code>
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $status
     *
     * @return Redirect
     */
    public static function to_route($route, $parameters = [], $status = 302)
    {
        return static::to(URL::to_route($route, $parameters), $status);
    }

    /**
     * Tambahkan sebuah item ke flash data (disimpan ke session).
     * Flash data akan tetap tersedia pada request selanjutnya.
     *
     * <code>
     *
     *      // Buat respon redirect dengan flash data.
     *      return Redirect::to('profile')->with('message', 'Selamat datang kembali!');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Redirect
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
     * Flash data inputan lama ke session dan return instance Redirect.
     * Setelah inputan lama di-flash, Anda bisa mengambilnya dengan Input::old().
     *
     * <code>
     *
     *      // Redirect dan flash semua data inputan ke session.
     *      return Redirect::to('login')->with_input();
     *
     *      // Redirect dan flash hanya beberapa data inputan ke session.
     *      return Redirect::to('login')->with_input('only', ['email', 'username']);
     *
     *      // Redirect dan flash semua data kecuali data-data yang disebutkan
     *      return Redirect::to('login')->with_input('except', ['password', 'api_token']);
     *
     * </code>
     *
     * @param string $filter
     * @param array  $items
     *
     * @return Redirect
     */
    public function with_input($filter = null, $items = [])
    {
        Input::flash($filter, $items);
        return $this;
    }

    /**
     * Flash pesan error dari kelas Validator ke session.
     * Method ini memudahkan Anda ketika ingin mengoper pesan error validasi ke view.
     *
     * <code>
     *
     *      // Redirect dan flash pesan error validator ke session
     *      return Redirect::to('register')->with_errors($validator);
     *
     * </code>
     *
     * @param Validator|Messages $container
     *
     * @return Redirect
     */
    public function with_errors($container)
    {
        $errors = ($container instanceof Validator) ? $container->errors : $container;
        return $this->with('errors', $errors);
    }

    /**
     * Kirim header dan konten respon ke browser.
     */
    public function send()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return parent::send();
    }
}
