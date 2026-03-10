<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Image extends Base
{
    protected static $categories = [
        'abstract', 'animals', 'business', 'cats', 'city', 'food', 'nightlife',
        'fashion', 'people', 'nature', 'sports', 'technics', 'transport',
    ];

    public static function imageUrl($width = 640, $height = 480, $hexBackgroundColor = null, $hexForegroundColor = null, $word = null)
    {
        return 'https://placehold.co/' . $width . '/' . $height . ($hexBackgroundColor ? '/' . ltrim($hexBackgroundColor, '#') : '')
            . ($hexForegroundColor ? '/' . ltrim($hexForegroundColor, '#') : '') . '/jpg' . ($word ? '?text=' . urlencode($word) : '');
    }

    public static function image($dir = null, $width = 640, $height = 480, $hexBackgroundColor = null, $hexForegroundColor = null, $word = null, $fullPath = true)
    {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir;

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        $filename = md5(\System\Str::random()) .'.jpg';
        $filepath = $dir . DS . $filename;
        $url = static::imageUrl($width, $height, $hexBackgroundColor, $hexForegroundColor, $word);

        $fp = fopen($filepath, 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        $success = curl_exec($ch);

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
            curl_close($ch);
        }

        fclose($fp);
        return $success ? ($fullPath ? $filepath : $filename) : false;
    }
}
