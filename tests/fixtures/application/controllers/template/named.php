<?php

defined('DS') or exit('No direct access.');

use System\Routing\Controller;

class Template_Named_Controller extends Controller
{
    public $layout = 'name: home';

    public function action_index()
    {
        // ..
    }
}
