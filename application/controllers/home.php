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
        $language = (Request::foundation()->getPreferredLanguage() == 'id_ID') ? 'id' : 'en';
        Config::set('application.language', $language);

        return view('home.index');
    }
}
