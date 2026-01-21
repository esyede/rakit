<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Bar
{
    private $panels = [];
    private $useSession = false;
    private $contentId;

    /**
     * Tambahkan panel baru.
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
     * Ambil panel berdasarkan id.
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
     * Render loading tag.
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
        $useSession = $this->useSession && session_status() === PHP_SESSION_ACTIVE;
        $redirectQueue = &$_SESSION['_oops']['redirect'];

        foreach (['bar', 'redirect', 'panic'] as $key) {
            $queue = &$_SESSION['_oops'][$key];
            $queue = array_slice((array) $queue, -10, null, true);
            $queue = array_filter($queue, function ($item) {
                return isset($item['time']) && ($item['time'] > (time() - 60));
            });
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
            $content = self::renderHtmlRows($rows);

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
    private static function renderHtmlRows(array $rows)
    {
        ob_start(function () {
            // ..
        });

        require __DIR__ . '/assets/bar/panels.phtml';
        require __DIR__ . '/assets/bar/bar.phtml';

        return Helpers::fixEncoding(ob_get_clean());
    }

    /**
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
     * Render aset debug bar.
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
        $css = array_map('file_get_contents', array_merge([
            __DIR__ . '/assets/bar/bar.css',
            __DIR__ . '/assets/toggle/toggle.css',
            __DIR__ . '/assets/dumper/dumper.css',
            __DIR__ . '/assets/panic/panic.css',
        ], Debugger::$customCssFiles));

        echo "(function(){
	       var el = document.createElement('style');
	       el.setAttribute('nonce', document.currentScript.getAttribute('nonce') || document.currentScript.nonce);
	       el.className='oops-debug';
	       el.textContent=" . json_encode(preg_replace('#\s+#u', ' ', implode($css))) . ";
	       document.head.appendChild(el);})();\n";

        array_map('readfile', array_merge([
            __DIR__ . '/assets/bar/bar.js',
            __DIR__ . '/assets/toggle/toggle.js',
            __DIR__ . '/assets/dumper/dumper.js',
            __DIR__ . '/assets/panic/panic.js',
        ], Debugger::$customJsFiles));
    }
}
