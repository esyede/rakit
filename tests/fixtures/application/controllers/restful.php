<?php

defined('DS') or exit('No direct access.');

use System\Routing\Controller;

class Restful_Controller extends Controller
{
    public $restful = true;

    public function get_index()
    {
        return 'get_index';
    }

    public function post_index()
    {
        return 'post_index';
    }
}
