<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct script access.');

class Panic
{
    /**
     * List info error.
     *
     * @var array
     */
    public $info = [];

    /**
     * Path stack trace.
     *
     * @var array
     */
    public $collapsePaths = [];

    /**
     * Berapa dalam array/object yang harus ditampilkan oleh dump()?
     *
     * @var int
     */
    public $maxDepth = 10;

    /**
     * Berapa banyak karakter harus ditampilkan oleh dump()?
     *
     * @var int
     */
    public $maxLength = 300;

    /**
     * List key array yang ingin disembunyikan dari tampilan error debugger.
     * Ini berguna untuk menyembunyikan data - data sensitif.
     *
     * @var array
     */
    public $keysToHide = [
        'password',
        'passwd',
        'pass',
        'pwd',
        'creditcard',
        'credit card',
        'cc',
        'pin',
        'sandi',
        'kata sandi',
        'kartu kredit',
    ];

    /**
     * Berisi list panel terdaftar.
     *
     * @var array|callable
     */
    private $panels = [];

    /**
     * Berisi list fungsi yang mereturn action untuk exception.
     *
     * @var array|callable
     */
    private $actions = [];

    public function __construct()
    {
        $this->collapsePaths[] = __DIR__;
    }

    /**
     * Tambahkan panel baru.
     *
     * @param callable $panel
     *
     * @return static
     */
    public function addPanel($panel)
    {
        if (! in_array($panel, $this->panels, true)) {
            $this->panels[] = $panel;
        }

        return $this;
    }

    /**
     * Tambahkan action baru.
     *
     * @param callable $action
     *
     * @return static
     */
    public function addAction($action)
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * Tampilkan halaman blue screen.
     *
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    public function render($e)
    {
        if (Helpers::isAjax() && session_status() === PHP_SESSION_ACTIVE) {
            ob_start(function () {
                // ..
            });

            $this->renderTemplate($e, __DIR__.'/assets/panic/content.phtml');
            $contentId = $_SERVER['HTTP_X_OOPS_AJAX'];
            $_SESSION['_oops']['panic'][$contentId] = [
                'content' => ob_get_clean(),
                'dumps' => Dumper::fetchLiveData(),
                'time' => time(),
            ];
        } else {
            $this->renderTemplate($e, __DIR__.'/assets/panic/page.phtml');
        }
    }

    /**
     * Simpan tampilan blue screen ke file.
     *
     * @param \Throwable|\Exception $e
     * @param string                $file
     *
     * @return void
     */
    public function renderToFile($e, $file)
    {
        $base = basename($file);
        $dir = substr_replace($file, '', strrpos($file, $base), strlen($base));
        $file = $dir.DIRECTORY_SEPARATOR.$base;

        if (! is_dir($dir)) {
            mkdir($dir.'aaaa');
            file_put_contents($dir.'index.html', 'No direct script access.');
        }

        if (! is_file($file)) {
            if ($handle = @fopen($file, 'x')) {
                // Buffer ganda terkadang mencegah pengiriman HTTP header
                ob_start();
                ob_start(function ($buffer) use ($handle) {
                    fwrite($handle, $buffer);
                }, 4096);
                $this->renderTemplate($e, __DIR__.'/assets/panic/page.phtml', false);
                ob_end_flush();
                ob_end_clean();
                fclose($handle);
            }
        }
    }

    private function renderTemplate($e, $template, $toScreen = true)
    {
        $messageHtml = preg_replace(
            '#\'\S[^\']*\S\'|"\S[^"]*\S"#U',
            '<i>$0</i>',
            htmlspecialchars((string) $e->getMessage(), ENT_SUBSTITUTE, 'UTF-8')
        );

        $info = array_filter($this->info);
        $source = Helpers::getSource();
        $sourceIsUrl = preg_match('#^https?://#', $source);
        $title = ($e instanceof \ErrorException)
            ? Helpers::errorTypeToString($e->getSeverity())
            : Helpers::getClass($e);
        $lastError = ($e instanceof \ErrorException || $e instanceof \Error) ? null : error_get_last();

        $keysToHide = array_flip(array_map('strtolower', $this->keysToHide));
        $dump = function ($v, $k = null) use ($keysToHide) {
            if (is_string($k) && isset($keysToHide[strtolower($k)])) {
                $v = Dumper::HIDDEN_VALUE;
            }

            return Dumper::toHtml($v, [
                Dumper::DEPTH => $this->maxDepth,
                Dumper::TRUNCATE => $this->maxLength,
                Dumper::LIVE => true,
                Dumper::LOCATION => Dumper::LOCATION_CLASS,
                Dumper::KEYS_TO_HIDE => $this->keysToHide,
            ]);
        };

        $css = array_map('file_get_contents', array_merge([
            __DIR__.DS.'assets'.DS.'panic'.DS.'panic.css',
        ], Debugger::$customCssFiles));

        $css = preg_replace('#\s+#u', ' ', implode($css));

        $nonce = $toScreen ? Helpers::getNonce() : null;
        $actions = $toScreen ? $this->renderActions($e) : [];

        require $template;
    }

    /**
     * @return \stdClass|array
     */
    private function renderPanels($ex)
    {
        $obLevel = ob_get_level();
        $res = [];

        foreach ($this->panels as $callback) {
            try {
                $panel = call_user_func($callback, $ex);

                if (empty($panel['tab']) || empty($panel['panel'])) {
                    continue;
                }

                $res[] = (object) $panel;
                continue;
            } catch (\Throwable $e) {
                // Skip error
            } catch (\Exception $e) {
                // Skip error
            }

            // Restore ob-level jika rusak
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            is_callable($callback, true, $name);

            $res[] = (object) [
                'tab' => "Error in panel $name",
                'panel' => nl2br(Helpers::escapeHtml($e)),
            ];
        }

        return $res;
    }

    /**
     * @return array
     */
    private function renderActions($ex)
    {
        $actions = [];

        foreach ($this->actions as $callback) {
            $action = call_user_func($callback, $ex);

            if (! empty($action['link']) && ! empty($action['label'])) {
                $actions[] = $action;
            }
        }

        if (property_exists($ex, 'oopsAction')
        && ! empty($ex->oopsAction['link'])
        && ! empty($ex->oopsAction['label'])) {
            $actions[] = $ex->oopsAction;
        }

        if (preg_match('# ([\'"])(\w{3,}(?:\\\\\w{3,})+)\\1#i', $ex->getMessage(), $m)) {
            $class = $m[2];

            if (! class_exists($class)
            && ! interface_exists($class)
            && ! trait_exists($class)
            && ($file = Helpers::guessClassFile($class))
            && ! is_file($file)) {
                $actions[] = [
                    'link' => Helpers::editorUri($file, 1, 'create'),
                    'label' => 'create class',
                ];
            }
        }

        if (preg_match('# ([\'"])((?:/|[a-z]:[/\\\\])\w[^\'"]+\.\w{2,5})\\1#i', $ex->getMessage(), $m)) {
            $file = $m[2];
            $actions[] = [
                'link' => Helpers::editorUri($file, 1, $label = is_file($file) ? 'open' : 'create'),
                'label' => $label.' file',
            ];
        }

        $query = (($ex instanceof \ErrorException) ? '' : Helpers::getClass($ex).' ')
            .preg_replace('#\'.*\'|".*"#Us', '', $ex->getMessage());

        $actions[] = [
            'link' => 'https://www.google.com/search?sourceid=rakit_framework&q='.urlencode($query),
            'label' => 'search',
            'external' => true,
        ];

        if (($ex instanceof \ErrorException)
        && ! empty($ex->skippable)
        && preg_match('#^https?://#', $source = Helpers::getSource())) {
            $actions[] = [
                'link' => $source.(strpos($source, '?') ? '&' : '?').'_oops_skip_error',
                'label' => 'skip error',
            ];
        }

        return $actions;
    }

    /**
     * Terapkan syntax highlighter ke isi file.
     *
     * @param string $file
     * @param int    $line
     * @param int    $lines
     *
     * @return string|null
     */
    public static function highlightFile($file, $line, $lines = 15, array $vars = null)
    {
        $source = @file_get_contents($file);

        if ($source) {
            $source = static::highlightPhp($source, $line, $lines, $vars);
            $editor = Helpers::editorUri($file, $line);

            if ($editor) {
                $source = substr_replace($source, ' data-oops-href="'.Helpers::escapeHtml($editor).'"', 4, 0);
            }

            return $source;
        }
    }

    /**
     * Terapkan syntax highlighter pada string kode PHP.
     *
     * @param string $source
     * @param int    $line
     * @param int    $lines
     *
     * @return string
     */
    public static function highlightPhp($source, $line, $lines = 15, array $vars = null)
    {
        // if (function_exists('ini_set')) {
        //     ini_set('highlight.comment', '#6a737d');
        //     ini_set('highlight.default', '#484467');
        //     ini_set('highlight.html', '#22863a');
        //     ini_set('highlight.keyword', '#8959a8');
        //     ini_set('highlight.string', '#00C02D');
        // }

        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $out = $source[0]; // <code><span color=highlight.html>
        $source = str_replace('<br />', "\n", $source[1]);
        $out .= static::highlightLine($source, $line, $lines);

        if ($vars) {
            $out = preg_replace_callback('#">\$(\w+)(&nbsp;)?</span>#', function ($m) use ($vars) {
                return array_key_exists($m[1], $vars)
                    ? '" title="'.str_replace(
                        '"',
                        '&quot;',
                        trim(strip_tags(Dumper::toHtml($vars[$m[1]], [Dumper::DEPTH => 1])))).$m[0]
                    : $m[0];
            }, $out);
        }

        $out = str_replace('&nbsp;', ' ', $out);

        return "<pre class='code'><div>$out</div></pre>";
    }

    /**
     * Highlight sebuah baris kode.
     *
     * @return string
     */
    public static function highlightLine($html, $line, $lines = 15)
    {
        $source = explode("\n", "\n".str_replace("\r\n", "\n", $html));
        $out = '';
        $spans = 1;
        $start = $i = max(1, min($line, count($source) - 1) - (int) floor($lines * 2 / 3));

        while (--$i >= 1) {
            if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
                if ($m[1] !== '</span>') {
                    $spans++;
                    $out .= $m[1];
                }

                break;
            }
        }

        $source = array_slice($source, $start, $lines, true);
        end($source);
        $numWidth = mb_strlen((string) key($source), '8bit');

        foreach ($source as $n => $s) {
            $spans += substr_count($s, '<span') - substr_count($s, '</span');
            $s = str_replace(["\r", "\n"], ['', ''], $s);
            preg_match_all('#<[^>]+>#', $s, $tags);

            if ($n === $line) {
                $out .= sprintf("<span class='highlight'>%{$numWidth}s:    %s\n</span>%s", $n, strip_tags($s), implode('', $tags[0]));
            } else {
                $out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
            }
        }

        $out .= str_repeat('</span>', $spans).'</code>';

        return $out;
    }

    /**
     * Haruskah file stack trace di-collapse?
     *
     * @param string $file
     *
     * @return bool
     */
    public function isCollapsed($file)
    {
        $file = strtr($file, '\\', '/').'/';

        foreach ($this->collapsePaths as $path) {
            $path = strtr($path, '\\', '/').'/';

            if (strncmp($file, $path, 0 === mb_strlen($path, '8bit'))) {
                return true;
            }
        }

        return false;
    }
}
