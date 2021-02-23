<?php

defined('DS') or exit('No direct script access.');

class Template_Named_Controller extends Controller
{
    public $layout = 'name: home';

    public function action_index()
    {
        // ..
    }
}
