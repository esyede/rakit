<?php

defined('DS') or exit('No direct script access.');

use Docs\Libraries\Docs;

class Docs_Home_Controller extends Controller
{
    /**
     * Handle GET /docs.
     *
     * @return View
     */
    public function action_index()
    {
        $view = View::make('docs::home');

        $view->title = Docs::title('home');
        $view->sidebar = Docs::sidebar(Docs::render('000-sidebar'));
        $view->content = Docs::content(Docs::render('home'));
        $view->mdname = 'home';

        return $view;
    }

    /**
     * Handle GET /docs/[foo/bar].
     *
     * @param string $section
     * @param string $page
     *
     * @return Response
     */
    public function action_page($section, $page = null)
    {
        $mdname = rtrim(implode('/', func_get_args()), '/');

        if (is_null($page) && Docs::exists($mdname.'/home')) {
            $mdname .= '/home';
        }

        if (Docs::exists($mdname)) {
            $view = View::make('docs::home');

            $view->title = Docs::title($mdname);
            $view->sidebar = Docs::sidebar(Docs::render('000-sidebar'));
            $view->content = Docs::content(Docs::render($mdname));
            $view->mdname = $mdname;

            return $view;
        }

        return Response::error('404');
    }
}
