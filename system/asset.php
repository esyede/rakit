<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Asset
{
    /**
     * Berisi seluruh aset container yang telah diinstansiasi.
     *
     * @var array
     */
    public static $containers = [];

    /**
     * Ambil instance asset container.
     *
     * <code>
     *
     *      // Ambil instance container aset default.
     *      $container = Asset::container();
     *
     *      // Ambil instance container aset bedasarkan namanya.
     *      $container = Asset::container('footer');
     *
     * </code>
     *
     * @param string $container
     *
     * @return Assetor
     */
    public static function container($container = 'default')
    {
        if (isset(static::$containers[$container])) {
            return static::$containers[$container];
        }

        static::$containers[$container] = new Assetor($container);

        return static::$containers[$container];
    }

    /**
     * Magic Method untuk pemanggilan method pada default container.
     *
     * <code>
     *
     *      // Panggil method styles() pada default container
     *      echo Asset::styles();
     *
     *      // Panggil method add() pada default container
     *      Asset::add('jquery', 'js/jquery.js');
     *
     *  </code>
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::container(), $method], $parameters);
    }
}
