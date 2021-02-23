<?php

defined('DS') or exit('No direct script access.');

class Auth_Controller extends Controller
{
    public function action_index()
    {
        return 'action_index';
    }

    public function action_login()
    {
        return 'action_login';
    }

    public function action_profile($name)
    {
        return $name;
    }
}
