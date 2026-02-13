<?php

defined('DS') or exit('No direct access.');

use Docs\Libraries\Docs;

class Docs_Home_Controller extends Controller
{
    /**
     * Indicates that the controller is RESTful.
     *
     * @var bool
     */
    public $restful = true;

    /**
     * Construtor.
     */
    public function __construct()
    {
        $this->middleware('before', 'csrf')->on('post');
        Docs::ensure_search_data_exists();
    }

    /**
     * Handle GET /docs.
     *
     * @return View
     */
    public function get_index()
    {
        return view('docs::home')
            ->with_title(Docs::title('home'))
            ->with_sidebar(Docs::sidebar(Docs::render('000-sidebar')))
            ->with_content(Docs::content(Docs::render('home')))
            ->with_file('home');
    }

    /**
     * Handle GET /docs/[foo/bar].
     *
     * @param string $section
     * @param string $page
     *
     * @return Response
     */
    public function get_page($section, $page = null)
    {
        $args = func_get_args();
        $file = Docs::exists(rtrim(implode('/', $args), '/') . '/home') ? '/home' : '';
        $file = rtrim(implode('/', $args), '/') . $file;

        abort_if(!Docs::exists($file), 404);

        return view('docs::home')
            ->with_title(Docs::title($file))
            ->with_sidebar(Docs::sidebar(Docs::render('000-sidebar')))
            ->with_content(Docs::content(Docs::render($file)))
            ->with_file($file);
    }
}
