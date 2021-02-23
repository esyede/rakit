<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Image extends Base
{
    protected static $categories = [
        'abstract', 'animals', 'business', 'cats', 'city', 'food', 'nightlife',
        'fashion', 'people', 'nature', 'sports', 'technics', 'transport',
    ];

    public static function imageUrl(
        $width = 640,
        $height = 480,
        $category = null,
        $randomize = true,
        $word = null
    ) {
        $url = 'http://lorempixel.com/'.$width.'/'.$height.'/';

        if ($category) {
            if (! in_array($category, static::$categories)) {
                throw new \InvalidArgumentException(sprintf('Unkown image category: %s', $category));
            }

            $url .= $category.'/';

            if ($word) {
                $url .= $word.'/';
            }
        }

        if ($randomize) {
            $url .= '?'.static::randomNumber(5, true);
        }

        return $url;
    }

    public static function image(
        $dir = null,
        $width = 640,
        $height = 480,
        $category = null,
        $fullPath = true,
        $randomize = true,
        $word = null
    ) {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir;

        if (! is_dir($dir) || ! is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory: %s', $dir));
        }

        $name = Uuid::uuid();
        $filename = md5($name).'.jpg';
        $filepath = $dir.DS.$filename;
        $url = static::imageUrl($width, $height, $category, $randomize, $word);

        if (extension_loaded('curl')) {
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            $success = curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        } else {
            return new \RuntimeException(
                'The image formatter downloads an image from a remote HTTP server. '.
                'Therefore, it requires that PHP can request remote hosts via cURL'
            );
        }

        if (! $success) {
            return false;
        }

        return $fullPath ? $filepath : $filename;
    }
}
