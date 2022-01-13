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
    public function action_index($lang = null)
    {
        $lang = $lang ? $lang.'/' : 'id/';
        return view('docs::home')
            ->with_title(Docs::title('home'))
            ->with_sidebar(Docs::sidebar(Docs::render($lang.'000-sidebar')))
            ->with_content(Docs::content(Docs::render($lang.'home')))
            ->with_filename($lang.'home');
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
        $args = func_get_args();
        $lang = (isset($args[0]) && in_array($args[0], ['en', 'id'])) ? $args[0].'/' : 'id/';
        $filename = rtrim(implode('/', $args), '/');
        $filename .= (is_null($page) && Docs::exists($filename.'/home')) ? '/home' : '';

        if (! Docs::exists($filename)) {
            return Response::error('404');
        }

        return view('docs::home')
            ->with_title(Docs::title($filename))
            ->with_sidebar(Docs::sidebar(Docs::render($lang.'000-sidebar')))
            ->with_content(Docs::content(Docs::render($filename)))
            ->with_filename($filename);
    }
}
