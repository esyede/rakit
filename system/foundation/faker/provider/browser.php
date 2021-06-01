<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Browser extends Base
{
    protected static $userAgents = ['firefox', 'chrome', 'internetExplorer', 'opera', 'safari'];
    protected static $linuxProcessor = ['i686', 'x86_64'];
    protected static $macProcessor = ['Intel', 'PPC', 'U; Intel', 'U; PPC'];
    protected static $lang = ['en-US', 'sl-SI'];
    protected static $windowsPlatformTokens = [
        'Windows NT 6.2', 'Windows NT 6.1', 'Windows NT 6.0',
        'Windows NT 5.2', 'Windows NT 5.1', 'Windows NT 5.01',
        'Windows NT 5.0', 'Windows NT 4.0', 'Windows 98; Win 9x 4.90',
        'Windows 98', 'Windows 95', 'Windows CE',
    ];

    public static function macProcessor()
    {
        return static::randomElement(static::$macProcessor);
    }

    public static function linuxProcessor()
    {
        return static::randomElement(static::$linuxProcessor);
    }

    public static function userAgent()
    {
        $userAgentName = static::randomElement(static::$userAgents);
        return static::{$userAgentName}();
    }

    public static function chrome()
    {
        $saf = mt_rand(531, 536).mt_rand(0, 2);
        $platforms = [
            '('.static::linuxPlatformToken().") AppleWebKit/$saf (KHTML, like Gecko) Chrome/"
                .mt_rand(36, 40).'.0.'.mt_rand(800, 899).".0 Mobile Safari/$saf",
            '('.static::windowsPlatformToken().") AppleWebKit/$saf (KHTML, like Gecko) Chrome/"
                .mt_rand(36, 40).'.0.'.mt_rand(800, 899).".0 Mobile Safari/$saf",
            '('.static::macPlatformToken().") AppleWebKit/$saf (KHTML, like Gecko) Chrome/"
                .mt_rand(36, 40).'.0.'.mt_rand(800, 899).".0 Mobile Safari/$saf",
        ];

        return 'Mozilla/5.0 '.static::randomElement($platforms);
    }

    public static function firefox()
    {
        $ver = 'Gecko/'.date('Ymd', mt_rand(strtotime('2010-1-1'), time()))
            .' Firefox/'.mt_rand(35, 37).'.0';
        $platforms = [
            '('.static::windowsPlatformToken().'; '.static::randomElement(static::$lang)
                .'; rv:1.9.'.mt_rand(0, 2).'.20) '.$ver,
            '('.static::linuxPlatformToken().'; rv:'.mt_rand(5, 7).'.0) '.$ver,
            '('.static::macPlatformToken().' rv:'.mt_rand(2, 6).'.0) '.$ver,
        ];

        return 'Mozilla/5.0 '.static::randomElement($platforms);
    }

    public static function safari()
    {
        $saf = mt_rand(531, 535).'.'.mt_rand(1, 50).'.'.mt_rand(1, 7);

        if (0 === mt_rand(0, 1)) {
            $ver = mt_rand(4, 5).'.'.mt_rand(0, 1);
        } else {
            $ver = mt_rand(4, 5).'.0.'.mt_rand(1, 5);
        }

        $mobile = ['iPhone; CPU iPhone OS', 'iPad; CPU OS'];
        $platforms = [
            '(Windows; U; '.static::windowsPlatformToken()
                .") AppleWebKit/$saf (KHTML, like Gecko) Version/$ver Safari/$saf",
            '('.static::macPlatformToken().' rv:'.mt_rand(2, 6)
                .'.0; '.static::randomElement(static::$lang)
                .") AppleWebKit/$saf (KHTML, like Gecko) Version/$ver Safari/$saf",
            '('.static::randomElement($mobile).' '.mt_rand(7, 8).'_'.mt_rand(0, 2)
                .'_'.mt_rand(1, 2).' like Mac OS X; '.static::randomElement(static::$lang)
                .") AppleWebKit/$saf (KHTML, like Gecko) Version/".mt_rand(3, 4)
                .'.0.5 Mobile/8B'.mt_rand(111, 119)." Safari/6$saf",
        ];

        return 'Mozilla/5.0 '.static::randomElement($platforms);
    }

    public static function opera()
    {
        $platforms = [
            '('.static::linuxPlatformToken().'; '.static::randomElement(static::$lang)
                .') Presto/2.'.mt_rand(8, 12).'.'.mt_rand(160, 355).' Version/'.mt_rand(10, 12).'.00',
            '('.static::windowsPlatformToken().'; '.static::randomElement(static::$lang)
                .') Presto/2.'.mt_rand(8, 12).'.'.mt_rand(160, 355).' Version/'.mt_rand(10, 12).'.00',
        ];

        return 'Opera/'.mt_rand(8, 9).'.'.mt_rand(10, 99).' '.static::randomElement($platforms);
    }

    public static function internetExplorer()
    {
        return 'Mozilla/5.0 (compatible; MSIE '.mt_rand(5, 11)
            .'.0; '.static::windowsPlatformToken().'; Trident/'
            .mt_rand(3, 5).'.'.mt_rand(0, 1).')';
    }

    public static function windowsPlatformToken()
    {
        return static::randomElement(static::$windowsPlatformTokens);
    }

    public static function macPlatformToken()
    {
        return 'Macintosh; '.static::randomElement(static::$macProcessor)
            .' Mac OS X 10_'.mt_rand(5, 8).'_'.mt_rand(0, 9);
    }

    public static function linuxPlatformToken()
    {
        return 'X11; Linux '.static::randomElement(static::$linuxProcessor);
    }
}
