<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Assetor
{
    /**
     * Nama container aset.
     *
     * @var string
     */
    public $name;

    /**
     * Nama paket pemilik aset.
     *
     * @var string
     */
    public $package = DEFAULT_PACKAGE;

    /**
     * Berisi nama-nama aset yang telah terdaftar.
     *
     * @var array
     */
    public $assets = [];

    /**
     * Buat instance container aset baru.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Tambahkan sebuah aset ke container.
     *
     * Ekstensi file aset akan digunakan untuk menebak jenis aset apa
     * yang sedang didaftarkan (CSS atau JavaScript).
     *
     * Jika anda perlu mendaftarkan aset dengan ekstensi non-standard,
     * gunakan method style() atau script(), jangan pakai yang ini.
     *
     * <code>
     *
     *      // Tambahkan sebuah aset ke container
     *      Asset::container()->add('jquery', 'js/jquery.js');
     *
     *      // Tambahkan sebuah aset yang memiliki dependensi terhadap aset lain
     *      Asset::add('jquery', 'js/jquery.js', 'jquery-ui');
     *
     *      // Tambahkan sebuah aset dengan atribut tambahan
     *      Asset::add('jquery', 'js/jquery.js', null, ['defer']);
     *
     * </code>
     *
     * @param string $name
     * @param string $source
     * @param array  $dependencies
     * @param array  $attributes
     *
     * @return Assetor
     */
    public function add($name, $source, $dependencies = [], $attributes = [])
    {
        $type = File::extension($source);

        if ('js' !== $type && 'css' !== $type) {
            throw new \Exception('Only css and javascript file are supported.');
        }

        $type = ('css' === $type) ? 'style' : 'script';

        return $this->{$type}($name, 'assets/'.$source, $dependencies, $attributes);
    }

    /**
     * Tambahkan sebuah file CSS kedalam aset yang telah terdaftar.
     *
     * @param string $name
     * @param string $source
     * @param array  $dependencies
     * @param array  $attributes
     *
     * @return Assetor
     */
    public function style($name, $source, $dependencies = [], $attributes = [])
    {
        if (! array_key_exists('media', $attributes)) {
            $attributes['media'] = 'all';
        }

        $this->register('style', $name, $source, $dependencies, $attributes);

        return $this;
    }

    /**
     * Tambahkan sebuah file JS kedalam aset yang telah terdaftar.
     *
     * @param string $name
     * @param string $source
     * @param array  $dependencies
     * @param array  $attributes
     *
     * @return Assetor
     */
    public function script($name, $source, $dependencies = [], $attributes = [])
    {
        $this->register('script', $name, $source, $dependencies, $attributes);

        return $this;
    }

    /**
     * Mereturn path ke suatu aset.
     *
     * @param string $source
     *
     * @return string
     */
    public function path($source)
    {
        return Package::assets($this->package).$source;
    }

    /**
     * Set paket pemilik container aset.
     *
     * @param string $package
     *
     * @return Assetor
     */
    public function package($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Tambahkan aset ke list aset terdaftar.
     *
     * @param string $type
     * @param string $name
     * @param string $source
     * @param array  $dependencies
     * @param array  $attributes
     */
    protected function register($type, $name, $source, $dependencies, $attributes)
    {
        $dependencies = (array) $dependencies;
        $attributes = (array) $attributes;

        $this->assets[$type][$name] = compact('source', 'dependencies', 'attributes');
    }

    /**
     * Mereturn link ke seluruh aset CSS yang terdaftar.
     *
     * @return string
     */
    public function styles()
    {
        return $this->group('style');
    }

    /**
     * Mereturn link ke seluruh aset JS yang terdaftar.
     *
     * @return string
     */
    public function scripts()
    {
        return $this->group('script');
    }

    /**
     * Ambil seluruh aset terdaftar untuk jenis / grup yang diberikan.
     *
     * @param string $group
     *
     * @return string
     */
    protected function group($group)
    {
        if (! isset($this->assets[$group]) || 0 === count($this->assets[$group])) {
            return '';
        }

        $assets = '';
        $groups = $this->arrange($this->assets[$group]);

        foreach ($groups as $name => $data) {
            $assets .= $this->asset($group, $name);
        }

        return $assets;
    }

    /**
     * Ambil link HTML ke aset yang terdaftar.
     *
     * @param string $group
     * @param string $name
     *
     * @return string
     */
    protected function asset($group, $name)
    {
        if (! isset($this->assets[$group][$name])) {
            return '';
        }

        $asset = $this->assets[$group][$name];

        if (false === filter_var($asset['source'], FILTER_VALIDATE_URL)) {
            $asset['source'] = $this->path($asset['source']);
        }

        return HTML::{$group}($asset['source'], $asset['attributes']);
    }

    /**
     * Sortir dan ambil aset berdasarkan dependensinya.
     *
     * @param array $assets
     *
     * @return array
     */
    protected function arrange($assets)
    {
        list($original, $sorted) = [$assets, []];

        while (count($assets) > 0) {
            foreach ($assets as $asset => $value) {
                $this->evaluate($asset, $value, $original, $sorted, $assets);
            }
        }

        return $sorted;
    }

    /**
     * Evaluasi aset dan dependensinya.
     *
     * @param string $asset
     * @param string $value
     * @param array  $original
     * @param array  $sorted
     * @param array  $assets
     */
    protected function evaluate($asset, $value, $original, &$sorted, &$assets)
    {
        if (0 === count($assets[$asset]['dependencies'])) {
            $sorted[$asset] = $value;
            unset($assets[$asset]);
        } else {
            foreach ($assets[$asset]['dependencies'] as $key => $dependency) {
                if (! $this->valid($asset, $dependency, $original, $assets)) {
                    unset($assets[$asset]['dependencies'][$key]);
                    continue;
                }

                if (! isset($sorted[$dependency])) {
                    continue;
                }

                unset($assets[$asset]['dependencies'][$key]);
            }
        }
    }

    /**
     * Verifikasi bahwa dependensi aset valid.
     *
     * Sebuah dependensi dianggap valid apabila ia benar-benar ada,
     * tidak mengandung circular reference, dan bukan reference ke aset pemiliknya.
     *
     * @param string $asset
     * @param string $dependency
     * @param array  $original
     * @param array  $assets
     *
     * @return bool
     */
    protected function valid($asset, $dependency, $original, $assets)
    {
        if (! isset($original[$dependency])) {
            return false;
        } elseif ($dependency === $asset) {
            throw new \Exception(sprintf("Asset '%s' is dependent on itself.", $asset));
        } elseif (isset($assets[$dependency])
        && in_array($asset, $assets[$dependency]['dependencies'])) {
            throw new \Exception(sprintf(
                "Assets '%s' and '%s' have a circular dependency.",
                $asset, $dependency
            ));
        }

        return true;
    }
}
