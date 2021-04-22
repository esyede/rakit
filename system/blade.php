<?php

namespace System;

defined('DS') or exit('No direct script access.');

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
        'forelse',
        'empty',
        'endforelse',
        'structure_start',
        'structure_end',
        'else',
        'unless',
        'endunless',
        'include',
        'render_each',
        'render',
        'yield',
        'set',
        'unset',
        'yield_section',
        'section_start',
        'section_end',
        'php_block',
    ];

    /**
     * Berisi nama-nama compiler kustom yang dibuat user.
     *
     * @var array
     */
    protected static $extensions = [];

    /**
     * Daftarkan blade engine ke sistem.
     */
    public static function sharpen()
    {
        Event::listen(View::ENGINE, function ($view) {
            if (! Str::contains($view->path, '.blade.php')) {
                return;
            }

            $compiled = Blade::compiled($view->path);

            if (! is_file($compiled) || Blade::expired($view->view, $view->path)) {
                File::put($compiled, Blade::compile($view), LOCK_EX);
            }

            $view->path = $compiled;

            return ltrim($view->get());
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
     * @param string $view
     * @param string $path
     *
     * @return bool
     */
    public static function expired($view, $path)
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
        return static::translate(File::get($view->path), $view);
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
        foreach (static::$compilers as $compiler) {
            $value = static::{'compile_'.$compiler}($value, $view);
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
        $value = preg_replace_callback('/(?<!@)@php(.*?)@endphp/s', function ($matches) {
            return '<?php '.$matches[1].'?>';
        }, $value);

        return $value;
    }

    /**
     * Rewrites Blade "@layout" expressions into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_layout($value)
    {
        if (! Str::starts_with($value, '@layout')) {
            return $value;
        }

        $lines = preg_split('/(\r?\n)/', $value);
        $regex = static::matcher('layout');
        $lines[] = preg_replace($regex, '$1@include$2', $lines[0]);

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
        $regex = '/\{\{\{\s*(.+?)\s*\}\}\}(\r?\n)?/s';
        $value = preg_replace_callback($regex, function ($matches) use ($compiler) {
            $ws = empty($matches[2]) ? '' : $matches[2].$matches[2];

            return '<?php echo e('.$compiler($matches[1]).') ?>'.$ws;
        }, $value);

        // {!!  !!}
        $regex = '/\{\!!\s*(.+?)\s*!!\}(\r?\n)?/s';
        $value = preg_replace_callback($regex, function ($matches) use ($compiler) {
            $ws = empty($matches[2]) ? '' : $matches[2].$matches[2];

            return '<?php echo '.$compiler($matches[1]).' ?>'.$ws;
        }, $value);

        // @{{  }}, {{  }}
        $regex = '/(@)?\{\{\s*(.+?)\s*\}\}(\r?\n)?/s';
        $value = preg_replace_callback($regex, function ($matches) use ($compiler) {
            $ws = empty($matches[3]) ? '' : $matches[3].$matches[3];

            return $matches[1]
                ? substr($matches[0], 1)
                : '<?php echo e('.$compiler($matches[2]).') ?>'.$ws;
        }, $value);

        return $value;
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
            preg_match('/\s*\(\s*(\S*)\s/', $forelse, $variable);

            $if = '<?php if (count('.$variable[1].') > 0): ?>';
            $search = '/(\s*)@forelse(\s*\(.*\))/';
            $replace = '$1'.$if.'<?php foreach$2: ?>';
            $blade = preg_replace($search, $replace, $forelse);

            $value = str_replace($forelse, $blade, $value);
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
        $regex = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/';

        return preg_replace($regex, '$1<?php $2$3: ?>', $value);
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
        $regex = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

        return preg_replace($regex, '$1<?php $2; ?>$3', $value);
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
     * Ubah sintaks @include ke bentuk PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_include($value)
    {
        $regex = static::matcher('include');
        $replacer = '$1<?php echo view$2->with(get_defined_vars())->render() ?>';

        return preg_replace($regex, $replacer, $value);
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
        $regex = static::matcher('render');

        return preg_replace($regex, '$1<?php echo render$2 ?>', $value);
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
        $regex = static::matcher('render_each');

        return preg_replace($regex, '$1<?php echo render_each$2 ?>', $value);
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
        $regex = static::matcher('yield');

        return preg_replace($regex, '$1<?php echo yield_content$2 ?>', $value);
    }

    /**
     * Ubah sintaks @yield_section ke bentuk PHP.
     *
     * @return string
     */
    protected static function compile_yield_section($value)
    {
        return str_replace('@yield_section', '<?php echo yield_section() ?>', $value);
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
        $regex = static::matcher('section');

        return preg_replace($regex, '$1<?php section_start$2 ?>', $value);
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
     * Jalankan kustom compiler buatan user.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function compile_extensions($value)
    {
        foreach (static::$extensions as $compiler) {
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
        return '/(\s*)@'.$function.'(\s*\(.*\))/';
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
        return path('storage').'views'.DS.crc32($path).'.bc.php';
    }
}
