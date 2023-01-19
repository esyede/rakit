<?php

defined('DS') or exit('No direct script access.');

use System\View;
use System\Routing\Controller;

class Home_Controller extends Controller
{
    public $foo;

    /**
     * Handler untuk request ke root aplikasi.
     *
     * @return View
     */
    public function action_index()
    {
        return View::make('home.index');
    }
}
