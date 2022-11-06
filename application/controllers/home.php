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
        for ($i=0; $i < 100; $i++) {
            dump(Str::ulid());
        }

        dd(Str::ulid());
        return View::make('home.index');
    }
}
