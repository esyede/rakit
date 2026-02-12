<?php

defined('DS') or exit('No direct access.');

use System\View;
use System\Routing\Controller;

class Home_Controller extends Controller
{
    public $foo;

    /**
     * Handle GET /.
     *
     * @return View
     */
    public function action_index()
    {
        return View::make('home.index');
    }
}
