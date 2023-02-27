<?php

defined('DS') or exit('No direct script access.');

class Logviewer_Home_Controller extends Controller
{
    private $viewer;

    public function __construct()
    {
        // Proteksi route dengan middleware
        $middlewares = Config::get('logviewer::main.middleware');
        $middlewares = array_merge($middlewares, ['auth']);
        $this->middleware('before', $middlewares);

        $this->viewer = new \Esyede\Viewer();
    }

    public function action_index()
    {
        $folder_files = [];

        if (Input::get('f')) {
            $this->viewer->in(\Esyede\Viewer::decode(Input::get('f')));
            $folder_files = $this->viewer->items(true);
        }
        if (Input::get('l')) {
            $this->viewer->of(\Esyede\Viewer::decode(Input::get('l')));
        }

        if (false !== ($halt = $this->halt())) {
            return $halt;
        }

        $data = [
            'logs' => $this->viewer->all(),
            'folders' => $this->viewer->dirs(),
            'current_folder' => $this->viewer->dirname(),
            'folder_files' => $folder_files,
            'files' => $this->viewer->files(true),
            'current_file' => $this->viewer->filename(),
            'standard' => true,
            'structure' => $this->viewer->lists(),
            'log_dir' => $this->viewer->logdir(),

        ];

        if (Request::wants_json()) {
            return Response::json($data);
        }

        if (is_array($data['logs']) && count($data['logs']) > 0) {
            $first = reset($data['logs']);
            if ($first) {
                if (! $first['context'] && ! $first['level']) {
                    $data['standard'] = false;
                }
            }
        }

        return View::make('logviewer::log', $data);
    }

    private function halt()
    {
        if (Input::get('f')) {
            $this->viewer->in(\Esyede\Viewer::decode(Input::get('f')));
        }

        if (Input::get('dl')) {
            return Response::download($this->viewer->path(\Esyede\Viewer::decode(Input::get('dl'))));
        } elseif (Input::has('clean')) {
            Storage::put($this->viewer->path(\Esyede\Viewer::decode(Input::get('clean'))), '');
            return Redirect::to(Request::referrer());
        } elseif (Input::has('del')) {
            Storage::delete($this->viewer->path(\Esyede\Viewer::decode(Input::get('del'))));
            return Redirect::to(Request::uri());
        } elseif (Input::has('delall')) {
            $files = ($this->viewer->dirname())
                ? $this->viewer->items(true)
                : $this->viewer->files(true);

            foreach ($files as $file) {
                Storage::delete($this->viewer->path($file));
            }

            return Redirect::to(Request::uri());
        }

        return false;
    }
}
