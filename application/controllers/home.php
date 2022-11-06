<?php

defined('DS') or exit('No direct script access.');

class Home_Controller extends Base_Controller
{
    /**
     * Handle GET /.
     *
     * @return View
     */
    public function action_index()
    {
        dd(preg_match('/[0-9][A-Z]/', Str::ulid()));
        return View::make('home.index');
    }
}
