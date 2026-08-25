<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

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
     * How deep into an array/object dump() should descend.
     *
     * @var int
     */
    public $maxDepth = 10;

    /**
     * How many characters dump() should show.
     *
     * @var int
     */
    public $maxLength = 300;

    /**
     * Array keys to hide from the debugger error page, so sensitive data
     * never reaches the output.
     *
     * @var array
     */
    public $keysToHide = [
      '2fa',
      '2fa_code',
      '2fa_pin',
      '2fa_token',
      '_csrf',
      'acc_number',
      'access_token',
      'account_bank_number',
      'account_number',
      'account_routing_number',
      'api_key',
      'api_token',
      'auth_token',
      'bank_acc',
      'bank_acc_num',
      'bank_acc_number',
      'bank_account',
      'bank_account_num',
      'bank_account_number',
      'card_cvv',
      'card_num',
      'card_number',
      'cc_num',
      'cc_number',
      'cert',
      'certificate',
      'confirm_passwd',
      'confirm_password',
      'credentials',
      'credit_card',
      'credit_card_num',
      'credit_card_number',
      'creds',
      'csrf',
      'cvv',
      'dsn',
      'issuer_certificate',
      'key',
      'mysql_pwd',
      'new_password',
      'old_password',
      'otp',
      'otp_code',
      'otp_pin',
      'otp_token',
      'passwd',
      'passwd_confirm',
      'password',
      'password1',
      'password2',
      'password_confirm',
      'pin',
      'private_key',
      'pwd',
      'raw',
      'repeat_password',
      'routing_acc',
      'routing_acc_num',
      'routing_acc_number',
      'routing_account_number',
      'routing_number',
      'salt',
      'secret',
      'security_code',
      'security_pin',
      'security_token',
      'social_security_num',
      'social_security_number',
      'ssn',
      'stripe_token',
      'token',
      'totp',
      'totp_code',
      'totp_pin',
      'totp_token',
      'two_factor_code',
      'two_factor_pin',
      'two_factor_token',
    ];

    /**
     * The registered panels.
     *
     * @var array|callable
     */
    private $panels = [];

    /**
     * Callbacks that answer an action for an exception.
     *
     * @var array|callable
     */
    private $actions = [];

    public function __construct()
    {
        $this->collapsePaths[] = __DIR__;
    }

    /**
     * Add a new panel.
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
     * Add a new action.
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
     * Render the blue screen page.
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
     * Write the blue screen page to a file.
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
        $dir = ('' === $dir) ? '.'.DIRECTORY_SEPARATOR : $dir;
        $file = $dir.$base;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
            file_put_contents($dir.'index.html', 'No direct access.');
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
        $lastError = (($e instanceof \ErrorException) || ($e instanceof \Error)) ? null : error_get_last();

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

        $css = array_map('file_get_contents', [
            __DIR__.DS.'assets'.DS.'panic'.DS.'panic.css',
        ]);

        $css = preg_replace('#\s+#u', ' ', implode('', $css));

        $nonce = $toScreen ? Helpers::getNonce() : null;
        $actions = $toScreen ? $this->renderActions($e) : [];

        require $template;
    }

    /**
     * @param \Throwable|\Exception $ex
     *
     * @return \stdClass|array
     */
    private function renderPanels($ex)
    {
        $obLevel = ob_get_level();
        $res = [];

        foreach ($this->panels as $callback) {
            $name = '(unknown)';
            $e = '';
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
            $res[] = (object) ['tab' => "Error in panel $name", 'panel' => nl2br(Helpers::escapeHtml($e))];
        }

        return $res;
    }

    /**
     * @param \Throwable|\Exception $ex
     *
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

        $oopsAction = \System\Foundation\Oops\Context::getOopsAction($ex);

        if (! empty($oopsAction['link']) && ! empty($oopsAction['label'])) {
            $actions[] = $oopsAction;
        }

        if (preg_match('# ([\'"])(\w{3,}(?:\\\\\w{3,})+)\\1#i', $ex->getMessage(), $m)) {
            $class = $m[2];
        }

        $query = (($ex instanceof \ErrorException) ? '' : Helpers::getClass($ex).' ')
            .preg_replace('#\'.*\'|".*"#Us', '', $ex->getMessage());

        $actions[] = [
            'link' => 'https://www.google.com/search?sourceid=rakit_framework&q='.urlencode($query),
            'label' => 'search',
            'external' => true,
        ];

        if (($ex instanceof \ErrorException)
            && \System\Foundation\Oops\Context::isSkippable($ex)
            && preg_match('#^https?://#', $source = Helpers::getSource())
        ) {
            $actions[] = [
                'link' => $source.(strpos($source, '?') ? '&' : '?').'_oops_skip_error',
                'label' => 'skip error',
            ];
        }

        return $actions;
    }

    /**
     * Render exception as markdown.
     *
     * @param \Throwable|\Exception $e
     *
     * @return string
     */
    public static function toMarkdown($e)
    {
        $nl = "\n";
        $out = '# '.Helpers::getClass($e).($e->getCode() ? ' #'.$e->getCode() : '').$nl.$nl;
        $out .= '**Message:** '.trim((string) $e->getMessage()).$nl;

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $req = trim($method.' '.$uri);

        if ('' !== $req) {
            $out .= '**Request:** '.$req.$nl;
        }

        $out .= '**Location:** '.$e->getFile().':'.$e->getLine().$nl;
        $out .= '**PHP:** '.PHP_VERSION.$nl.$nl;

        $snippet = static::plainSnippet($e->getFile(), (int) $e->getLine(), 12);

        if (null !== $snippet) {
            $out .= '## Source ('.basename($e->getFile()).':'.$e->getLine().')'.$nl;
            $out .= '```php'.$nl.$snippet.$nl.'```'.$nl.$nl;
        }

        $out .= '## Stack trace'.$nl;
        $out .= '```'.$nl.$e->getTraceAsString().$nl.'```'.$nl;

        // Chained (previous) exceptions
        $prev = $e->getPrevious();
        $guard = 0;
        $maxDepth = 5;

        while ($prev && $guard++ < $maxDepth) {
            $out .= $nl.'## Caused by: '.Helpers::getClass($prev).$nl;
            $out .= '**Message:** '.trim((string) $prev->getMessage()).$nl;
            $out .= '**Location:** '.$prev->getFile().':'.$prev->getLine().$nl;
            $prev = $prev->getPrevious();
        }

        return $out;
    }

    /**
     * Extract a snippet of raw code (without HTML) around a specific line,
     * with a ">" marker on the line containing the error. Used for Markdown output.
     *
     * @param string $file
     * @param int    $line
     * @param int    $around
     *
     * @return string|null
     */
    private static function plainSnippet($file, $line, $around = 12)
    {
        if (! is_file($file)) {
            return;
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES);

        if (! is_array($lines) || empty($lines)) {
            return;
        }

        $total = count($lines);
        $half = (int) floor($around / 2);
        $start = max(1, $line - $half);
        $end = min($total, $line + $half);

        $out = [];

        for ($i = $start; $i <= $end; $i++) {
            $prefix = ($i === $line) ? '> ' : '  ';
            $out[] = $prefix.$i.': '.$lines[$i - 1];
        }

        return implode("\n", $out);
    }

    /**
     * Apply the syntax highlighter to the contents of a file.
     *
     * @param string $file
     * @param int    $line
     * @param int    $lines
     *
     * @return string|null
     */
    public static function highlightFile($file, $line, $lines = 15, array $vars = [])
    {
        if ($source = @file_get_contents($file)) {
            return static::highlightPhp($source, $line, $lines, $vars);
        }
    }

    /**
     * Apply the syntax highlighter to a string of PHP code.
     *
     * @param string $source
     * @param int    $line
     * @param int    $lines
     *
     * @return string
     */
    public static function highlightPhp($source, $line, $lines = 15, array $vars = [])
    {
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#8c8c8c');
            ini_set('highlight.default', '#e0e0e0');
            ini_set('highlight.html', '#ff6b6b');
            ini_set('highlight.keyword', '#91a7ff');
            ini_set('highlight.string', '#69db7c');
        }

        $source = str_replace(["\r\n", "\r"], "\n", $source);

        if (PHP_VERSION_ID >= 80300) {
            $html = highlight_string($source, true);
            $html = preg_replace('#^<pre[^>]*>#', '', $html);
            $html = preg_replace('#</pre>\s*\z#', '', $html);
            $pos = strpos($html, '>');
            $content = (false === $pos) ? $html : substr($html, $pos + 1);
            $content = preg_replace('#</code>\s*\z#', '', $content);
            $out = '<code>';
            $out .= static::highlightLine($content, $line, $lines);
        } else {
            $source = explode("\n", highlight_string($source, true));
            $out = $source[0]; // <code><span color=highlight.html>
            $source = str_replace('<br />', "\n", $source[1]);
            $out .= static::highlightLine($source, $line, $lines);
        }

        if (! empty($vars)) {
            $out = preg_replace_callback('#">\$(\w+)(&nbsp;)?</span>#', function ($m) use ($vars) {
                return array_key_exists($m[1], $vars)
                    ? '" title="'.str_replace('"', '&quot;', trim(strip_tags(Dumper::toHtml($vars[$m[1]], [Dumper::DEPTH => 1])))).$m[0]
                    : $m[0];
            }, $out);
        }

        $out = str_replace('&nbsp;', ' ', $out);
        return "<pre class='code'><div>$out</div></pre>";
    }

    /**
     * Highlight a single line of code.
     *
     * @param string $html
     * @param int    $line
     * @param int    $lines
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
     * Should the stack trace for this file be collapsed?
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

            if (0 === strncmp($file, $path, mb_strlen($path, '8bit'))) {
                return true;
            }
        }

        return false;
    }

    public function renderPhpInfo()
    {
        ob_start();
        @phpinfo(INFO_LICENSE); // @ = phpinfo may be disabled
        $license = ob_get_clean();

        ob_start();
        @phpinfo(INFO_CONFIGURATION | INFO_MODULES); // @ = phpinfo may be disabled
        $info = ob_get_clean();

        if (strpos($license, '<body') === false) {
            echo '<pre class="oops-dump">', Helpers::escapeHtml($info), '</pre>';
        } else {
            $info = str_replace('<table', '<table class="oops-sortable"', $info);
            echo preg_replace('#^.+<body>|</body>.+\z#s', '', $info);
        }
    }
}
