<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;

class Template_Named_Controller extends Controller
{
    public $layout = 'name: home';

    public function action_index()
    {
        // ..
    }
}
