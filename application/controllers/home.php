<?php

defined('DS') or exit('No direct script access.');

class Home_Controller extends Base_Controller
{
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
