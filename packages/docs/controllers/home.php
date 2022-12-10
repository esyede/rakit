<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;
use System\Config;
use System\Request;
use System\View;
use System\Response;
use Docs\Libraries\Docs;

class Docs_Home_Controller extends Controller
{
    /**
     * ahasa default.
     *
     * @var string
     */
    private $language;

    /**
     * Jalankan CSRF middleware di setiap POST request.
     */
    public function __construct()
    {
        $this->language = Request::foundation()->getPreferredLanguage();
        $this->language = ($this->language === 'id_ID' || $this->language === 'id') ? 'id' : 'en';
        Config::set('application.language', $this->language);

        $this->middleware('before', 'csrf')->on('post');
    }

    /**
     * Handle GET /docs.
     *
     * @return View
     */
    public function action_index($lang = null)
    {
        $lang = $lang ? $lang . '/' : Config::get('application.language', 'id') . '/';
        return View::make('docs::home')
            ->with_title(Docs::title('home'))
            ->with_sidebar(Docs::sidebar(Docs::render($lang . '000-sidebar')))
            ->with_content(Docs::content(Docs::render($lang . 'home')))
            ->with_filename($lang . 'home');
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
        $lang = isset($args[0]) ? $args[0] . '/' : Config::get('application.language', 'id') . '/';
        $filename = rtrim(implode('/', $args), '/');
        $filename = rtrim(implode('/', $args), '/') . (Docs::exists($filename . '/home') ? '/home' : '');

        if (!Docs::exists($filename)) {
            return Response::error(404);
        }

        return View::make('docs::home')
            ->with_title(Docs::title($filename))
            ->with_sidebar(Docs::sidebar(Docs::render($lang . '000-sidebar')))
            ->with_content(Docs::content(Docs::render($filename)))
            ->with_filename($filename);
    }
}
