<?php

namespace System;

defined('DS') or exit('No direct access.');

class Blade
{
    /**
     * Direktori cache view.
     *
     * @var string
     */
    public static $directory;

    /**
     * List nama-nama compiler milik blade.
     *
     * @var array
     */
    protected static $compilers = [
        'extensions',
        'layout',
        'comment',
        'echo',
        'csrf',
        'forelse',
        'empty',
        'endforelse',
        'structure_start',
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
        'php_block',
    ];

    /**
     * Berisi nama-nama compiler kustom yang dibuat user.
     *
     * @var array
     */
    protected static $extensions = [];

    /**
     * Cache untuk hasil translate blade.
     *
     * @var array
     */
    protected static $translated = [];

    /**
     * Daftarkan blade engine ke sistem.
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
                return ltrim($view->get());
            } catch (\Throwable $e) {
                return ltrim($view->get());
            } catch (\Exception $e) {
                return ltrim($view->get());
            }
        });
    }

    /**
     * Daftarkan compiler kustom baru.
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
     * Periksa apakah view sudah "kadaluwarsa" dan perlu dikompilasi ulang.
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
     * Kompilasi file blade ke bentuk ekspresi PHP yang valid.
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
     * Terjemahkan sintaks blade ke sintaks PHP yang valid.
     *
     * @param string       $value
     * @param \System\View $view
     *
     * @return string
     */
    public static function translate($value, $view = null)
    {
        $compilers = static::$compilers;

        foreach ($compilers as $compiler) {
            if ('csrf' === $compiler && false === strpos($value, '@csrf')) {
                continue;
            }

            $value = static::{'compile_' . $compiler}($value, $view);
        }

        return $value;
    }

    /**
     * Kompilasi sintaks @php dan @endphp.
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
     * Kompilasi sintaks "@layout" ke bentuk PHP.
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
     * Ubah comment blade ke bentuk PHP.
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
     * Ubah echo blade ke bentuk PHP.
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
     * Ubah sintaks @csrf ke bentuk form HTML.
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
     * Ubah sintaks @set() ke bentuk PHP.
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
     * Ubah sintaks @unset() ke bentuk PHP.
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
     * Ubah sintaks @json() ke bentuk PHP.
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
     * Ubah sintaks @forelse ke bentuk PHP.
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
            $replace = '$1<?php if (count(' . $variables[1] . ') > 0): ?><?php foreach$2: ?>';
            $value = str_replace($forelse, preg_replace('/(\s*)@forelse(\s*\(.*\))/', $replace, $forelse), $value);
        }

        return $value;
    }

    /**
     * Ubah sintaks @empty ke bentuk PHP.
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
     * Ubah sintaks @forelse ke bentuk PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_endforelse($value)
    {
        return str_replace('@endforelse', '<?php endif; ?>', $value);
    }

    /**
     * Ubah control-structure pembuka blade ke bentuk PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_structure_start($value)
    {
        return preg_replace('/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/', '$1<?php $2$3: ?>', $value);
    }

    /**
     * Ubah control-structure penutup blade ke bentuk PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_structure_end($value)
    {
        return preg_replace('/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/', '$1<?php $2; ?>$3', $value);
    }

    /**
     * Ubah sintaks @else ke bentuk PHP.
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
     * Ubah sintaks @unless ke bentuk PHP.
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
     * Ubah sintaks @endunless ke bentuk PHP.
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
     * Ubah sintaks @error ke bentuk PHP.
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
     * Ubah sintaks @enderror ke bentuk PHP.
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
     * Ubah sintaks @guest ke bentuk PHP.
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
     * Ubah sintaks @endguest ke bentuk PHP.
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
     * Ubah sintaks @auth ke bentuk PHP.
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
     * Ubah sintaks @endauth ke bentuk PHP.
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
     * Ubah sintaks @include ke bentuk PHP.
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
     * Ubah sintaks @render ke bentuk PHP.
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
     * Ubah sintaks @render_each ke bentuk PHP.
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
     * Ubah sintaks @yield ke bentuk PHP.
     * Sintaks ini merupakan shortcut untuk method Section::yield_content().
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
     * Ubah sintaks @show ke bentuk PHP.
     *
     * @return string
     */
    protected static function compile_show($value)
    {
        return str_replace('@show', '<?php echo yield_section() ?>', $value);
    }

    /**
     * Ubah sintaks @section ke bentuk PHP
     * Sintaks ini merupakan shortcut dari method Section::start().
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
     * Ubah sintaks @endsection ke bentuk PHP.
     * Sintaks ini merupakan shortcut untuk method Section::stop().
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
     * Ubah sintaks @inject ke bentuk PHP.
     * Sintaks ini merupakan shortcut untuk method Section::inject().
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
     * Jalankan kustom compiler buatan user.
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
     * Ambil regex untuk sintaks-sintaks umum blade.
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
     * Ambil full path ke file hasil kompilasi.
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
