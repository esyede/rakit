<?php

defined('DS') or exit('No direct script access.');

class TemplateStub
{
    public function __toString()
    {
        return 'TemplateStub';
    }
}

class Template_Override_Controller extends Controller
{
    public $layout = 'home.index';

    public function action_index()
    {
        // ..
    }

    public function layout()
    {
        return 'Layout';
    }
}
