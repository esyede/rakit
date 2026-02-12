<?php

namespace System;

defined('DS') or exit('No direct access.');

class Blade
{
    /**
     * Directory where the cached views are stored.
     *
     * @var string
     */
    public static $directory;

    /**
     * List of compiler names.
     *
     * @var array
     */
    protected static $compilers = [
        'extensions',
        'layout',
        'comment',
        'verbatim',
        'once',
        'endonce',
        'echo',
        'csrf',
        'forelse',
        'empty',
        'endforelse',
        'structure_start',
        'foreach',
        'structure_end',
        'else',
        'unless',
        'endunless',
        'error',
        'enderror',
        'guest',
        'endguest',
        'auth',
        'endauth',
        'include',
        'render_each',
        'render',
        'yield',
        'set',
        'unset',
        'json',
        'show',
        'section_start',
        'section_end',
        'inject',
        'method',
        'push',
        'endpush',
        'stack',
        'hassection',
        'sectionmissing',
        'php_block',
    ];

    /**
     * Contains custom compilers names created by the user.
     *
     * @var array
     */
    protected static $extensions = [];

    /**
     * Cache for translated blade.
     *
     * @var array
     */
    protected static $translated = [];

    /**
     * Register blade engine.
     */
    public static function sharpen()
    {
        Event::listen(View::ENGINE, function ($view) {
            if (!Str::ends_with($view->path, '.blade.php')) {
                return;
            }

            $compiled = static::compiled($view->path);

            try {
                if (!is_file($compiled) || static::expired($view->path)) {
                    file_put_contents($compiled, static::compile($view), LOCK_EX);
                }

                $view->path = $compiled;
                $result = ltrim($view->get());
                return $result;
            } catch (\Throwable $e) {
                $result = ltrim($view->get());
                return $result;
            } catch (\Exception $e) {
                $result = ltrim($view->get());
                return $result;
            }
        });
    }

    /**
     * Add custom compiler.
     *
     * <code>
     *
     *      Blade::extend(function ($view) {
     *          return str_replace('foo', 'bar', $view);
     *      });
     *
     * </code>
     *
     * @param \Closure $compiler
     */
    public static function extend(\Closure $compiler)
    {
        static::$extensions[] = $compiler;
    }

    /**
     * Check if view is expired and needs to be recompiled.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function expired($path)
    {
        return filemtime($path) > filemtime(static::compiled($path));
    }

    /**
     * Compile the given view.
     *
     * @param string $path
     *
     * @return string
     */
    public static function compile($view)
    {
        return static::translate(Storage::get($view->path), $view);
    }

    /**
     * Translate the given string.
     *
     * @param string       $value
     * @param \System\View $view
     *
     * @return string
     */
    public static function translate($value, $view = null)
    {
        $verbatims = [];
        $value = preg_replace_callback('/@verbatim(.*?)@endverbatim/s', function ($matches) use (&$verbatims) {
            $token = '___VERBATIM_' . count($verbatims) . '___';
            $verbatims[$token] = $matches[1];
            return $token;
        }, $value);

        $compilers = static::$compilers;

        foreach ($compilers as $compiler) {
            if ('csrf' === $compiler && false === strpos($value, '@csrf')) {
                continue;
            }

            $value = static::{'compile_' . $compiler}($value, $view);
        }

        foreach ($verbatims as $token => $content) {
            $value = str_replace($token, $content, $value);
        }

        return $value;
    }

    /**
     * Translate @php and @endphp.
     *
     * @param string $value
     *
     * @return string
     */
    public static function compile_php_block($value)
    {
        return preg_replace_callback('/(?<!@)@php(.*?)@endphp/s', function ($matches) {
            return '<?php ' . $matches[1] . '?>';
        }, $value);
    }

    /**
     * Translate @layout.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_layout($value)
    {
        if (!Str::starts_with($value, '@layout')) {
            return $value;
        }

        $lines = preg_split('/(\r?\n)/', $value);
        $lines[] = preg_replace(static::matcher('layout'), '$1@include$2', $lines[0]);

        return implode(CRLF, array_slice($lines, 1));
    }

    /**
     * Translate blade comments.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_comment($value)
    {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/', '<?php /* $1 */ ?>', $value);
    }

    /**
     * Translate blade echo.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_echo($value)
    {
        $compiler = function ($str) {
            // {{ .. or .. }}
            return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $str);
        };

        // {{{  }}}
        $matcher = '/\{\{\{\s*(.+?)\s*\}\}\}(\r?\n)?/s';
        $value = preg_replace_callback($matcher, function ($matches) use ($compiler) {
            $ws = empty($matches[2]) ? '' : $matches[2] . $matches[2];
            return '<?php echo e(' . $compiler($matches[1]) . ') ?>' . $ws;
        }, $value);

        // {!!  !!}
        $matcher = '/\{\!!\s*(.+?)\s*!!\}(\r?\n)?/s';
        $value = preg_replace_callback($matcher, function ($matches) use ($compiler) {
            $ws = empty($matches[2]) ? '' : $matches[2] . $matches[2];
            return '<?php echo ' . $compiler($matches[1]) . ' ?>' . $ws;
        }, $value);

        // @{{  }}, {{  }}
        $matcher = '/(@)?\{\{\s*(.+?)\s*\}\}(\r?\n)?/s';
        $value = preg_replace_callback($matcher, function ($matches) use ($compiler) {
            $ws = empty($matches[3]) ? '' : $matches[3] . $matches[3];
            return $matches[1] ? substr($matches[0], 1) : '<?php echo e(' . $compiler($matches[2]) . ') ?>' . $ws;
        }, $value);

        return $value;
    }

    /**
     * Translate @csrf.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_csrf($value)
    {
        return str_replace('@csrf', '<?php echo csrf_field() ?>', $value);
    }

    /**
     * Translate @set.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_set($value)
    {
        return preg_replace("/@set\(['\"](.*?)['\"]\,(.*)\)/", '<?php $$1 =$2;?>', $value);
    }

    /**
     * Translate @unset.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_unset($value)
    {
        return preg_replace("/@unset\(['\"](.*?)['\"]\)/", '<?php unset($$1)?>', $value);
    }

    /**
     * Translate @json.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_json($value)
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
        $result = $value;
        $offset = 0;

        while (($pos = strpos($result, '@json(', $offset)) !== false) {
            $start = $pos + 6;
            $depth = 1;
            $current = $start;
            $len = strlen($result);

            while ($current < $len && $depth > 0) {
                if ($result[$current] === '(') {
                    $depth++;
                } elseif ($result[$current] === ')') {
                    $depth--;
                }

                $current++;
            }

            if ($depth === 0) {
                $expression = substr($result, $start, $current - $start - 1);
                $original = substr($result, $pos, $current - $pos);
                $replacement = '<?php echo json_encode(' . trim($expression) . ', ' . $flags . '); ?>';
                $result = substr_replace($result, $replacement, $pos, strlen($original));
                $offset = $pos + strlen($replacement);
            } else {
                $offset = $pos + 1;
            }
        }

        return $result;
    }

    /**
     * Translate @forelse.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_forelse($value)
    {
        preg_match_all('/(\s*)@forelse(\s*\(.*\))(\s*)/', $value, $matches);

        foreach ($matches[0] as $forelse) {
            preg_match('/\s*\(\s*(\S*)\s/', $forelse, $variables);
            $replace = '$1<?php if (count(' . $variables[1] . ') > 0): ?><?php $__loop_stack = isset($__loop_stack) ? $__loop_stack : []; $__loop_stack[] = (object)["index" => -1, "iteration" => 0, "remaining" => count(' . $variables[1] . '), "count" => count(' . $variables[1] . '), "first" => false, "last" => false, "even" => false, "odd" => false, "depth" => count($__loop_stack), "parent" => count($__loop_stack) > 0 ? $__loop_stack[count($__loop_stack)-1] : null]; foreach$2: $__loop_stack[count($__loop_stack)-1]->index++; $__loop_stack[count($__loop_stack)-1]->iteration++; $__loop_stack[count($__loop_stack)-1]->remaining--; $__loop_stack[count($__loop_stack)-1]->first = ($__loop_stack[count($__loop_stack)-1]->index === 0); $__loop_stack[count($__loop_stack)-1]->last = ($__loop_stack[count($__loop_stack)-1]->index === $__loop_stack[count($__loop_stack)-1]->count - 1); $__loop_stack[count($__loop_stack)-1]->even = ($__loop_stack[count($__loop_stack)-1]->iteration % 2 === 0); $__loop_stack[count($__loop_stack)-1]->odd = ($__loop_stack[count($__loop_stack)-1]->iteration % 2 !== 0); $loop = $__loop_stack[count($__loop_stack)-1]; ?>';
            $value = str_replace($forelse, preg_replace('/(\s*)@forelse(\s*\(.*\))/', $replace, $forelse), $value);
        }

        return $value;
    }

    /**
     * Translate @empty.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_empty($value)
    {
        return str_replace('@empty', '<?php endforeach; ?><?php else: ?>', $value);
    }

    /**
     * Translate @endforelse.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endforelse($value)
    {
        return str_replace('@endforelse', '<?php endif; array_pop($__loop_stack); ?>', $value);
    }

    /**
     * Translate control-structure start.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_structure_start($value)
    {
        return preg_replace('/(\s*)@(if|elseif|for|while)(\s*\(.*\))/', '$1<?php $2$3: ?>', $value);
    }

    /**
     * Translate control-structure end.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_structure_end($value)
    {
        return preg_replace_callback('/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/', function ($matches) {
            return $matches[1] . '<?php ' . $matches[2] . '; ?>' . (('endforeach' === $matches[2]) ? '<?php array_pop($__loop_stack); ?>' : '') . $matches[3];
        }, $value);
    }

    protected static function compile_foreach($value)
    {
        return preg_replace_callback('/@foreach(\s*\(.*\))/', function ($matches) {
            if (preg_match('/\(\s*([^=]+?)\s+as\s+/', $matches[1], $arrays)) {
                return '<?php $__loop_stack = isset($__loop_stack) ? $__loop_stack : []; $__loop_stack[] = (object)["index" => -1, "iteration" => 0, "remaining" => count(' . trim($arrays[1]) . '), "count" => count(' . trim($arrays[1]) . '), "first" => false, "last" => false, "even" => false, "odd" => false, "depth" => count($__loop_stack), "parent" => count($__loop_stack) > 0 ? $__loop_stack[count($__loop_stack)-1] : null]; foreach' . $matches[1] . ': $__loop_stack[count($__loop_stack)-1]->index++; $__loop_stack[count($__loop_stack)-1]->iteration++; $__loop_stack[count($__loop_stack)-1]->remaining--; $__loop_stack[count($__loop_stack)-1]->first = ($__loop_stack[count($__loop_stack)-1]->index === 0); $__loop_stack[count($__loop_stack)-1]->last = ($__loop_stack[count($__loop_stack)-1]->index === $__loop_stack[count($__loop_stack)-1]->count - 1); $__loop_stack[count($__loop_stack)-1]->even = ($__loop_stack[count($__loop_stack)-1]->iteration % 2 === 0); $__loop_stack[count($__loop_stack)-1]->odd = ($__loop_stack[count($__loop_stack)-1]->iteration % 2 !== 0); $loop = $__loop_stack[count($__loop_stack)-1]; ?>';
            }

            return $matches[0];
        }, $value);
    }

    /**
     * Translate @else.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_else($value)
    {
        return preg_replace('/(\s*)@(else)(\s*)/', '$1<?php $2: ?>$3', $value);
    }

    /**
     * Translate @unless.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_unless($value)
    {
        return preg_replace('/(\s*)@unless(\s*\(.*\))/', '$1<?php if (! ($2)): ?>', $value);
    }

    /**
     * Translate @endunless.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endunless($value)
    {
        return str_replace('@endunless', '<?php endif; ?>', $value);
    }

    /**
     * Translate @error.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_error($value)
    {
        return preg_replace(static::matcher('error'), '$1<?php if ($errors->has$2): ?>', $value);
    }

    /**
     * Translate @enderror.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_enderror($value)
    {
        return str_replace('@enderror', '<?php endif; ?>', $value);
    }

    /**
     * Translate @guest.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_guest($value)
    {
        return str_replace('@guest', '<?php if (System\Auth::guest()): ?>', $value);
    }

    /**
     * Translate @endguest.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endguest($value)
    {
        return str_replace('@endguest', '<?php endif; ?>', $value);
    }

    /**
     * Translate @auth.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_auth($value)
    {
        return str_replace('@auth', '<?php if (System\Auth::check()): ?>', $value);
    }

    /**
     * Translate @endauth.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endauth($value)
    {
        return str_replace('@endauth', '<?php endif; ?>', $value);
    }

    /**
     * Translate @include.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_include($value)
    {
        $replacer = '$1<?php echo view$2->with(get_defined_vars())->render() ?>';
        return preg_replace(static::matcher('include'), $replacer, $value);
    }

    /**
     * Translate @render.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_render($value)
    {
        return preg_replace(static::matcher('render'), '$1<?php echo render$2 ?>', $value);
    }

    /**
     * Translate @render_each.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_render_each($value)
    {
        return preg_replace(static::matcher('render_each'), '$1<?php echo render_each$2 ?>', $value);
    }

    /**
     * Translate @yield (shortcut for Section::yield_content()).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_yield($value)
    {
        return preg_replace(static::matcher('yield'), '$1<?php echo yield_content$2 ?>', $value);
    }

    /**
     * Translate @show.
     *
     * @return string
     */
    protected static function compile_show($value)
    {
        return str_replace('@show', '<?php echo yield_section() ?>', $value);
    }

    /**
     * Translate @section (shortcut for Section::start()).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_section_start($value)
    {
        return preg_replace(static::matcher('section'), '$1<?php section_start$2 ?>', $value);
    }

    /**
     * Translate @endsection (shortcut for Section::stop()).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_section_end($value)
    {
        return preg_replace('/@endsection/', '<?php section_stop() ?>', $value);
    }

    /**
     * Translate @inject (shortcut for Section::inject()).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_inject($value)
    {
        return preg_replace(static::matcher('inject'), '$1<?php section_inject$2 ?>', $value);
    }

    /**
     * Translate @verbatim (placeholder only, handled by static::translate()).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_verbatim($value)
    {
        return $value;
    }

    /**
     * Translate @once.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_once($value)
    {
        return preg_replace_callback('/@once(.*?)@endonce/s', function ($matches) {
            static $onces = [];
            $key = md5($matches[1]);

            if (!isset($onces[$key])) {
                $onces[$key] = true;
                return $matches[1];
            }

            return '';
        }, $value);
    }

    /**
     * Translate @endonce (placeholder only, handled by static::compile_once()).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endonce($value)
    {
        return $value;
    }

    /**
     * Translate @method to hidden input.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_method($value)
    {
        return preg_replace_callback(static::matcher('method'), function ($matches) {
            return $matches[1] . '<input type="hidden" name="_method" value="' . trim(trim($matches[2], '()'), "'\"") . '" />';
        }, $value);
    }

    /**
     * Translate @push.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_push($value)
    {
        return preg_replace(static::matcher('push'), '$1<?php Section::push$2 ?>', $value);
    }

    /**
     * Translate @endpush.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endpush($value)
    {
        return str_replace('@endpush', '<?php Section::endpush() ?>', $value);
    }

    /**
     * Translate @stack.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_stack($value)
    {
        return preg_replace(static::matcher('stack'), '$1<?php echo Section::stack$2 ?>', $value);
    }

    /**
     * Translate @hassection.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_hassection($value)
    {
        return preg_replace(static::matcher('hassection'), '$1<?php if (Section::has$2): ?>', $value);
    }

    /**
     * Translate @sectionmissing.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_sectionmissing($value)
    {
        return preg_replace(static::matcher('sectionmissing'), '$1<?php if (!Section::has$2): ?>', $value);
    }

    /**
     * Compile user extensions.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_extensions($value)
    {
        $compilers = static::$extensions;

        foreach ($compilers as $compiler) {
            $value = $compiler($value);
        }

        return $value;
    }

    /**
     * Return regex for common blade syntaxes.
     *
     * @param string $function
     *
     * @return string
     */
    public static function matcher($function)
    {
        return '/(\s*)@' . $function . '(\s*\(.*\))/';
    }

    /**
     * Get full path to compiled file.
     *
     * @param string $view
     *
     * @return string
     */
    public static function compiled($path)
    {
        $name = Str::replace_last('.blade.php', '', basename($path));
        $length = strlen($path);
        $hash = 65535;

        for ($i = 0; $i < $length; $i++) {
            $hash ^= (ord($path[$i]) << 8);

            for ($j = 0; $j < 8; $j++) {
                if (($hash <<= 1) & 65536) {
                    $hash ^= 4129;
                }

                $hash &= 65535;
            }
        }

        return path('storage') . 'views' . DS . sprintf('%s__%u', $name, $hash) . '.bc.php';
    }
}
