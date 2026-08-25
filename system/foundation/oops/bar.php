<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Bar
{
    private $panels = [];
    private $useSession = false;
    private $contentId;
    private $storage;
    private $served = false;

    /**
     * Add a panel.
     *
     * @param object $panel
     * @param string $id
     *
     * @return static
     */
    public function addPanel($panel, $id = null)
    {
        if (null === $id) {
            $counter = 0;
            do {
                $id = get_class($panel) . ($counter++ ? "-$counter" : '');
            } while (isset($this->panels[$id]));
        }

        $this->panels[$id] = $panel;

        return $this;
    }

    /**
     * Get a panel by ID.
     *
     * @param string $id
     *
     * @return object|null
     */
    public function getPanel($id)
    {
        return isset($this->panels[$id]) ? $this->panels[$id] : null;
    }

    /**
     * Render debug bar loader.
     *
     * @return void
     */
    public function renderLoader()
    {
        if (!$this->useSession) {
            throw new \LogicException('Session started before debugger enabled.');
        }

        $this->contentId = $this->contentId ?: substr(md5(uniqid('', true)), 0, 10);
        $contentId = $this->contentId;
        $nonce = Helpers::getNonce();
        $async = true;

        require __DIR__ . '/assets/bar/loader.phtml';
    }

    /**
     * Render debug bar.
     *
     * @return void
     */
    public function render()
    {
        // Halaman riwayat (open.<id>) sudah mengeluarkan HTML sendiri lalu
        // dispatch() memanggil exit; cegah shutdown handler menempelkan bar
        // kedua untuk request ini.
        if ($this->served) {
            return;
        }

        $useSession = $this->useSession && session_status() === PHP_SESSION_ACTIVE;
        $redirectQueue = null;

        if ($useSession) {
            $redirectQueue = &$_SESSION['_oops']['redirect'];

            foreach (['bar', 'redirect', 'panic'] as $key) {
                $queue = &$_SESSION['_oops'][$key];
                $queue = array_slice((array) $queue, -10, null, true);
                $queue = array_filter($queue, function ($item) {
                    return isset($item['time']) && ($item['time'] > (time() - 60));
                });
                unset($queue);
            }
        }

        $rows = [];

        if (Helpers::isAjax()) {
            if ($useSession) {
                $rows[] = (object) ['type' => 'ajax', 'panels' => $this->renderPanels('-ajax')];
                $contentId = $_SERVER['HTTP_X_OOPS_AJAX'] . '-ajax';
                $_SESSION['_oops']['bar'][$contentId] = [
                    'content' => self::renderHtmlRows($rows),
                    'dumps' => Dumper::fetchLiveData(),
                    'time' => time(),
                ];
            }
        } elseif (preg_match('#^Location:#im', implode("\n", headers_list()))) { // redireksi
            if ($useSession) {
                Dumper::fetchLiveData();
                Dumper::$livePrefix = count($redirectQueue) . 'p';
                $redirectQueue[] = [
                    'panels' => $this->renderPanels('-r' . count($redirectQueue)),
                    'dumps' => Dumper::fetchLiveData(),
                    'time' => time(),
                ];
            }
        } elseif (Helpers::isHtmlMode()) {
            $rows[] = (object) ['type' => 'main', 'panels' => $this->renderPanels()];
            $dumps = Dumper::fetchLiveData();

            foreach (array_reverse((array) $redirectQueue) as $info) {
                $rows[] = (object) ['type' => 'redirect', 'panels' => $info['panels']];
                $dumps += $info['dumps'];
            }

            $redirectQueue = null;
            $history = $this->storage()->recent(20);
            $content = self::renderHtmlRows($rows, $history);

            $this->saveHistory($content, $dumps);

            if ($this->contentId) {
                $_SESSION['_oops']['bar'][$this->contentId] = [
                    'content' => $content,
                    'dumps' => $dumps,
                    'time' => time(),
                ];
            } else {
                $contentId = substr(md5(uniqid('', true)), 0, 10);
                $nonce = Helpers::getNonce();
                $async = false;

                require __DIR__ . '/assets/bar/loader.phtml';
            }
        }
    }

    /**
     * @return string
     */
    private static function renderHtmlRows(array $rows, array $history = [])
    {
        ob_start(function () {
            // ..
        });

        require __DIR__ . '/assets/bar/panels.phtml';
        require __DIR__ . '/assets/bar/bar.phtml';

        return Helpers::fixEncoding(ob_get_clean());
    }

    /**
     * @param string $suffix
     *
     * @return array
     */
    private function renderPanels($suffix = null)
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
        });

        $obLevel = ob_get_level();
        $panels = [];

        foreach ($this->panels as $id => $panel) {
            $idHtml = preg_replace('#[^a-z0-9]+#i', '-', $id) . $suffix;

            $tab = null;
            $panelHtml = null;

            try {
                $tab = (string) $panel->getTab();
                $panelHtml = $tab ? (string) $panel->getPanel() : null;
            } catch (\Throwable $e) {
                // Skip error
            } catch (\Exception $e) {
                // Skip error
            }

            if (isset($e)) {
                while (ob_get_level() > $obLevel) {
                    ob_end_clean();
                }

                $idHtml = "error-$idHtml";
                $tab = "Error in $id";
                $panelHtml = "<h1>Error: $id</h1><div class='oops-inner'>"
                    . nl2br(Helpers::escapeHtml($e)) . '</div>';

                unset($e);
            }

            $panels[] = (object) ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml];
        }

        restore_error_handler();

        return $panels;
    }

    /**
     * File storage for the request history (openhandler).
     *
     * @return Storage
     */
    private function storage()
    {
        if (null === $this->storage) {
            require_once __DIR__ . '/storage.php';
            $this->storage = new Storage(path('storage') . 'debugbar', 25);
        }

        return $this->storage;
    }

    /**
     * Save a snapshot of the current request to the file-based history so it can
     * be reopened from the "History" dropdown (php-debugbar openhandler style).
     *
     * @param string $content
     * @param array  $dumps
     *
     * @return void
     */
    private function saveHistory($content, $dumps)
    {
        $status = function_exists('http_response_code') ? http_response_code() : 200;
        $now = microtime(true);

        $meta = [
            'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET',
            'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'status' => (int) ($status ? $status : 200),
            'time' => time(),
            'ts' => $now,
            'ms' => round(($now - Debugger::$time) * 1000, 1),
            'memory' => round(memory_get_peak_usage(true) / 1048576, 2),
        ];

        $id = $this->contentId ? $this->contentId : substr(md5(uniqid('', true)), 0, 10);

        $this->storage()->save($id, [
            'content' => $content,
            'dumps' => (array) $dumps,
            'meta' => $meta,
        ]);
    }

    /**
     * Render a standalone page holding the debug bar of a past request. Served
     * from the `_oops_bar=open.<id>` endpoint and opened in a new tab by the
     * History dropdown; loads the assets, then calls Oops.Debug.init with the
     * snapshot.
     *
     * @param string $id
     *
     * @return void
     */
    private function renderHistoryPage($id)
    {
        $rec = $this->storage()->get($id);

        header('Content-Type: text/html; charset=UTF-8');
        header_remove('Set-Cookie');

        if (!$rec || !isset($rec['content'])) {
            http_response_code(404);
            echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:sans-serif;padding:24px">Debugbar history not found.</body>';

            return;
        }

        $meta = (isset($rec['meta']) && is_array($rec['meta'])) ? $rec['meta'] : [];
        $method = isset($meta['method']) ? $meta['method'] : 'GET';
        $uri = isset($meta['uri']) ? $meta['uri'] : '';
        $when = isset($meta['time']) ? date('Y-m-d H:i:s', (int) $meta['time']) : '';
        $label = trim($method . ' ' . $uri);
        $nonce = Helpers::getNonce();
        $dumps = isset($rec['dumps']) ? $rec['dumps'] : [];

        $payload = str_replace('<!--', '<\!--', json_encode($rec['content']) . ', ' . json_encode($dumps));

        echo '<!DOCTYPE html><html><head><meta charset="utf-8">',
            '<meta name="robots" content="noindex">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>', Helpers::escapeHtml($label), ' &mdash; Rakit Debugbar</title>',
            '<style>html,body{margin:0}body{min-height:100vh;background:#0e0f13;',
            'font-family:-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}',
            '.oops-hist-banner{padding:14px 20px 72px;color:#c9ced8}',
            '.oops-hist-banner h1{margin:0 0 4px;font-size:15px;color:#cf7b74;font-weight:600}',
            '.oops-hist-banner p{margin:0;font-size:12px;color:#8b93a4}',
            '</style></head><body>',
            '<div class="oops-hist-banner"><h1>Past request &middot; ', Helpers::escapeHtml($label), '</h1>',
            '<p>', Helpers::escapeHtml($when),
            (isset($meta['status']) ? ' &middot; HTTP ' . (int) $meta['status'] : ''),
            (isset($meta['ms']) ? ' &middot; ' . Helpers::escapeHtml($meta['ms']) . ' ms' : ''),
            '</p></div>',
            '<script nonce="', Helpers::escapeHtml($nonce), '" src="?_oops_bar=js&amp;v=', urlencode(RAKIT_VERSION), '&amp;XDEBUG_SESSION_STOP=1"></script>',
            '<script nonce="', Helpers::escapeHtml($nonce), '">Oops.Debug.init(', $payload, ');</script>',
            '</body></html>';
    }

    /**
     * Render or dispatch assets.
     *
     * @return bool
     */
    public function dispatchAssets()
    {
        $asset = isset($_GET['_oops_bar']) ? $_GET['_oops_bar'] : null;

        if ('js' === $asset) {
            header('Content-Type: application/javascript');
            header('Cache-Control: max-age=864000');
            header_remove('Pragma');
            header_remove('Set-Cookie');
            $this->renderAssets();

            return true;
        }

        if (is_string($asset) && preg_match('#^open\.([A-Za-z0-9_-]+)$#', $asset, $m)) {
            $this->served = true;
            $this->renderHistoryPage($m[1]);

            return true;
        }

        $this->useSession = session_status() === PHP_SESSION_ACTIVE;

        if ($this->useSession && Helpers::isAjax()) {
            header('X-Oops-Ajax: 1');
        }

        if ($this->useSession && $asset && preg_match('#^content(-ajax)?\.(\w+)$#', $asset, $m)) {
            $session = &$_SESSION['_oops']['bar'][$m[2] . $m[1]];

            header('Content-Type: application/javascript');
            header('Cache-Control: max-age=60');
            header_remove('Set-Cookie');

            if (!$m[1]) {
                $this->renderAssets();
            }

            if ($session) {
                $method = $m[1] ? 'loadAjax' : 'init';
                echo "Oops.Debug.$method(" . json_encode($session['content'])
                    . ', ' . json_encode($session['dumps']) . ');';
                $session = null;
            }

            $session = &$_SESSION['_oops']['panic'][$m[2]];

            if ($session) {
                echo 'Oops.Panic.loadAjax(' . json_encode($session['content'])
                    . ', ' . json_encode($session['dumps']) . ');';
                $session = null;
            }

            return true;
        }

        return false;
    }

    private function renderAssets()
    {
        $css = array_map('file_get_contents', [
            __DIR__ . '/assets/bar/bar.css',
            __DIR__ . '/assets/toggle/toggle.css',
            __DIR__ . '/assets/dumper/dumper.css',
            __DIR__ . '/assets/panic/panic.css',
        ]);

        echo "(function(){
	       var el = document.createElement('style');
	       el.setAttribute('nonce', document.currentScript.getAttribute('nonce') || document.currentScript.nonce);
	       el.className='oops-debug';
	       el.textContent=" . json_encode(preg_replace('#\s+#u', ' ', implode('', $css))) . ";
	       document.head.appendChild(el);})();\n";

        array_map('readfile', [
            __DIR__ . '/assets/bar/bar.js',
            __DIR__ . '/assets/toggle/toggle.js',
            __DIR__ . '/assets/dumper/dumper.js',
            __DIR__ . '/assets/panic/panic.js',
        ]);
    }
}
