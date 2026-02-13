<?php

defined('DS') or exit('No direct access.');

use Docs\Libraries\Docs;

class Docs_Home_Controller extends Controller
{
    /**
     * Jalankan CSRF middleware di setiap POST request.
     */
    public function __construct()
    {
        $this->middleware('before', 'csrf')->on('post');
    }

    /**
     * Handle GET /docs.
     *
     * @return View
     */
    public function action_index()
    {
        Docs::ensure_search_data_exists();

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
    public function action_page($section, $page = null)
    {
        $args = func_get_args();
        $file = Docs::exists(rtrim(implode('/', $args), '/') . '/home') ? '/home' : '';
        $file = rtrim(implode('/', $args), '/') . $file;

        abort_if(!Docs::exists($file), 404);

        Docs::ensure_search_data_exists();

        return view('docs::home')
            ->with_title(Docs::title($file))
            ->with_sidebar(Docs::sidebar(Docs::render('000-sidebar')))
            ->with_content(Docs::content(Docs::render($file)))
            ->with_file($file);
    }
}
