<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;
use System\Request;
use System\View;
use System\Response;
use Docs\Libraries\Docs;

class Docs_Home_Controller extends Controller
{
    /**
     * Bahasa default.
     *
     * @var string
     */
    private $lang;

    /**
     * Jalankan CSRF middleware di setiap POST request.
     */
    public function __construct()
    {
        $this->lang = Request::getPreferredLanguage();
        $this->lang = (false !== stripos($this->lang, 'id')) ? 'id' : 'en';
        $this->middleware('before', 'csrf')->on('post');
    }

    /**
     * Handle GET /docs.
     *
     * @return View
     */
    public function action_index($lang = null)
    {
        $lang = $lang ? $lang . '/' : $this->lang . '/';
        return View::make('docs::home')
            ->with_title(Docs::title('home'))
            ->with_sidebar(Docs::sidebar(Docs::render($lang . '000-sidebar')))
            ->with_content(Docs::content(Docs::render($lang . 'home')))
            ->with_file($lang . 'home');
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
        $lang = isset($args[0]) ? $args[0] . '/' : $this->lang . '/';
        $file = Docs::exists(rtrim(implode('/', $args), '/') . '/home') ? '/home' : '';
        $file = rtrim(implode('/', $args), '/') . $file;

        if (!Docs::exists($file)) {
            return Response::error(404);
        }

        return View::make('docs::home')
            ->with_title(Docs::title($file))
            ->with_sidebar(Docs::sidebar(Docs::render($lang . '000-sidebar')))
            ->with_content(Docs::content(Docs::render($file)))
            ->with_file($file);
    }
}
