<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Color extends Base
{
    protected static $safeColorNames = [
        'black', 'maroon', 'green', 'navy', 'olive', 'purple', 'teal',
        'lime', 'blue', 'silver', 'gray', 'yellow', 'fuchsia', 'aqua', 'white',
    ];

    protected static $allColorNames = [
        'AliceBlue', 'AntiqueWhite', 'Aqua', 'Aquamarine', 'Azure', 'Beige', 'Bisque', 'Black', 'BlanchedAlmond',
        'Blue', 'BlueViolet', 'Brown', 'BurlyWood', 'CadetBlue', 'Chartreuse', 'Chocolate', 'Coral', 'CornflowerBlue',
        'Cornsilk', 'Crimson', 'Cyan', 'DarkBlue', 'DarkCyan', 'DarkGoldenRod', 'DarkGray', 'DarkGreen', 'DarkKhaki',
        'DarkMagenta', 'DarkOliveGreen', 'Darkorange', 'DarkOrchid', 'DarkRed', 'DarkSalmon', 'DarkSeaGreen', 'DarkSlateBlue',
        'DarkSlateGray', 'DarkTurquoise', 'DarkViolet', 'DeepPink', 'DeepSkyBlue', 'DimGray', 'DimGrey', 'DodgerBlue', 'FireBrick',
        'FloralWhite', 'ForestGreen', 'Fuchsia', 'Gainsboro', 'GhostWhite', 'Gold', 'GoldenRod', 'Gray', 'Green', 'GreenYellow',
        'HoneyDew', 'HotPink', 'IndianRed ', 'Indigo ', 'Ivory', 'Khaki', 'Lavender', 'LavenderBlush', 'LawnGreen', 'LemonChiffon',
        'LightBlue', 'LightCoral', 'LightCyan', 'LightGoldenRodYellow', 'LightGray', 'LightGreen', 'LightPink', 'LightSalmon',
        'LightSeaGreen', 'LightSkyBlue', 'LightSlateGray', 'LightSteelBlue', 'LightYellow', 'Lime', 'LimeGreen', 'Linen', 'Magenta',
        'Maroon', 'MediumAquaMarine', 'MediumBlue', 'MediumOrchid', 'MediumPurple', 'MediumSeaGreen', 'MediumSlateBlue',
        'MediumSpringGreen', 'MediumTurquoise', 'MediumVioletRed', 'MidnightBlue', 'MintCream', 'MistyRose', 'Moccasin',
        'NavajoWhite', 'Navy', 'OldLace', 'Olive', 'OliveDrab', 'Orange', 'OrangeRed', 'Orchid', 'PaleGoldenRod', 'PaleGreen',
        'PaleTurquoise', 'PaleVioletRed', 'PapayaWhip', 'PeachPuff', 'Peru', 'Pink', 'Plum', 'PowderBlue', 'Purple', 'Red',
        'RosyBrown', 'RoyalBlue', 'SaddleBrown', 'Salmon', 'SandyBrown', 'SeaGreen', 'SeaShell', 'Sienna', 'Silver', 'SkyBlue',
        'SlateBlue', 'SlateGray', 'Snow', 'SpringGreen', 'SteelBlue', 'Tan', 'Teal', 'Thistle', 'Tomato', 'Turquoise', 'Violet',
        'Wheat', 'White', 'WhiteSmoke', 'Yellow', 'YellowGreen',
    ];

    /**
     * Generate a random hex color code.
     *
     * @return string
     */
    public static function hexColor()
    {
        return '#'.str_pad(dechex(mt_rand(1, 16777215)), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a random safe hex color code.
     *
     * @return string
     */
    public static function safeHexColor()
    {
        $color = str_pad(dechex(mt_rand(0, 255)), 3, '0', STR_PAD_LEFT);
        return '#'.$color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
    }

    /**
     * Generate a random RGB color as an array.
     *
     * @return array
     */
    public static function rgbColorAsArray()
    {
        $color = static::hexColor();
        return [hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2))];
    }

    /**
     * Generate a random RGB color as a comma-separated string.
     *
     * @return string
     */
    public static function rgbColor()
    {
        return implode(',', static::rgbColorAsArray());
    }

    /**
     * Generate a random RGB CSS color string.
     *
     * @return string
     */
    public static function rgbCssColor()
    {
        return 'rgb('.static::rgbColor().')';
    }

    /**
     * Get a random safe color name.
     *
     * @return string
     */
    public static function safeColorName()
    {
        return static::randomElement(static::$safeColorNames);
    }

    /**
     * Get a random color name.
     *
     * @return string
     */
    public static function colorName()
    {
        return static::randomElement(static::$allColorNames);
    }
}
