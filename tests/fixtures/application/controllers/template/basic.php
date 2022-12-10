<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;

class Template_Basic_Controller extends Controller
{
    public $layout = 'home.index';

    public function action_index()
    {
        // ..
    }
}
