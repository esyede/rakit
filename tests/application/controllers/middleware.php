<?php

defined('DS') or exit('No direct script access.');

class Middleware_Controller extends Controller
{
    public function __construct()
    {
        Middleware::register('test-all-before', function () {
            $_SERVER['test-all-before'] = true;
        });

        Middleware::register('test-all-after', function () {
            $_SERVER['test-all-after'] = true;
        });

        Middleware::register('test-profile-before', function () {
            $_SERVER['test-profile-before'] = true;
        });

        Middleware::register('test-except', function () {
            $_SERVER['test-except'] = true;
        });

        Middleware::register('test-on-post', function () {
            $_SERVER['test-on-post'] = true;
        });

        Middleware::register('test-on-get-put', function () {
            $_SERVER['test-on-get-put'] = true;
        });

        Middleware::register('test-before-middleware', function () {
            return 'Middleware OK!';
        });

        Middleware::register('test-param', function ($var1, $var2) {
            return $var1.$var2;
        });

        Middleware::register('test-multi-1', function () {
            $_SERVER['test-multi-1'] = true;
        });

        Middleware::register('test-multi-2', function () {
            $_SERVER['test-multi-2'] = true;
        });

        $this->middleware('before', 'test-all-before');
        $this->middleware('after', 'test-all-after');
        $this->middleware('before', 'test-profile-before')->only(['profile']);
        $this->middleware('before', 'test-except')->except(['index', 'profile']);
        $this->middleware('before', 'test-on-post')->on(['post']);
        $this->middleware('before', 'test-on-get-put')->on(['get', 'put']);
        $this->middleware('before', 'test-before-middleware')->only('login');
        $this->middleware('after', 'test-before-middleware')->only('logout');
        $this->middleware('before', 'test-param:1,2')->only('edit');
        $this->middleware('before', 'test-multi-1|test-multi-2')->only('save');
    }

    public function action_index()
    {
        return 'action_index';
    }

    public function action_profile()
    {
        return 'action_profile';
    }

    public function action_show()
    {
        return 'action_show';
    }

    public function action_edit()
    {
        return 'action_edit';
    }

    public function action_save()
    {
        return 'action_save';
    }

    public function action_login()
    {
        return 'action_login';
    }

    public function action_logout()
    {
        return 'action_logout';
    }
}
