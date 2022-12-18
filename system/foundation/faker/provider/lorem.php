<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct script access.');

class Lorem extends Base
{
    protected static $wordList = [
        'alias', 'consequatur', 'aut', 'perferendis', 'sit', 'voluptatem',
        'accusantium', 'doloremque', 'aperiam', 'eaque', 'ipsa', 'quae', 'ab',
        'illo', 'inventore', 'veritatis', 'et', 'quasi', 'architecto',
        'beatae', 'vitae', 'dicta', 'sunt', 'explicabo', 'aspernatur', 'aut',
        'odit', 'aut', 'fugit', 'sed', 'quia', 'consequuntur', 'magni',
        'dolores', 'eos', 'qui', 'ratione', 'voluptatem', 'sequi', 'nesciunt',
        'neque', 'dolorem', 'ipsum', 'quia', 'dolor', 'sit', 'amet',
        'consectetur', 'adipisci', 'velit', 'sed', 'quia', 'non', 'numquam',
        'eius', 'modi', 'tempora', 'incidunt', 'ut', 'labore', 'et', 'dolore',
        'magnam', 'aliquam', 'quaerat', 'voluptatem', 'ut', 'enim', 'ad',
        'minima', 'veniam', 'quis', 'nostrum', 'exercitationem', 'ullam',
        'corporis', 'nemo', 'enim', 'ipsam', 'voluptatem', 'quia', 'voluptas',
        'sit', 'suscipit', 'laboriosam', 'nisi', 'ut', 'aliquid', 'ex', 'ea',
        'commodi', 'consequatur', 'quis', 'autem', 'vel', 'eum', 'iure',
        'reprehenderit', 'qui', 'in', 'ea', 'voluptate', 'velit', 'esse',
        'quam', 'nihil', 'molestiae', 'et', 'iusto', 'odio', 'dignissimos',
        'ducimus', 'qui', 'blanditiis', 'praesentium', 'laudantium', 'totam',
        'rem', 'voluptatum', 'deleniti', 'atque', 'corrupti', 'quos',
        'dolores', 'et', 'quas', 'molestias', 'excepturi', 'sint',
        'occaecati', 'cupiditate', 'non', 'provident', 'sed', 'ut',
        'perspiciatis', 'unde', 'omnis', 'iste', 'natus', 'error',
        'similique', 'sunt', 'in', 'culpa', 'qui', 'officia', 'deserunt',
        'mollitia', 'animi', 'id', 'est', 'laborum', 'et', 'dolorum', 'fuga',
        'et', 'harum', 'quidem', 'rerum', 'facilis', 'est', 'et', 'expedita',
        'distinctio', 'nam', 'libero', 'tempore', 'cum', 'soluta', 'nobis',
        'est', 'eligendi', 'optio', 'cumque', 'nihil', 'impedit', 'quo',
        'porro', 'quisquam', 'est', 'qui', 'minus', 'id', 'quod', 'maxime',
        'placeat', 'facere', 'possimus', 'omnis', 'voluptas', 'assumenda',
        'est', 'omnis', 'dolor', 'repellendus', 'temporibus', 'autem',
        'quibusdam', 'et', 'aut', 'consequatur', 'vel', 'illum', 'qui',
        'dolorem', 'eum', 'fugiat', 'quo', 'voluptas', 'nulla', 'pariatur',
        'at', 'vero', 'eos', 'et', 'accusamus', 'officiis', 'debitis', 'aut',
        'rerum', 'necessitatibus', 'saepe', 'eveniet', 'ut', 'et',
        'voluptates', 'repudiandae', 'sint', 'et', 'molestiae', 'non',
        'recusandae', 'itaque', 'earum', 'rerum', 'hic', 'tenetur', 'a',
        'sapiente', 'delectus', 'ut', 'aut', 'reiciendis', 'voluptatibus',
        'maiores', 'doloribus', 'asperiores', 'repellat',
    ];

    public static function word()
    {
        return static::randomElement(static::$wordList);
    }

    public static function words($nb = 3, $asText = false)
    {
        $words = [];

        for ($i = 0; $i < $nb; ++$i) {
            $words[] = static::word();
        }

        return $asText ? implode(' ', $words) : $words;
    }

    public static function sentence($nbWords = 6, $variableNbWords = true)
    {
        if ($nbWords <= 0) {
            return '';
        }

        if ($variableNbWords) {
            $nbWords = self::randomizeNbElements($nbWords);
        }

        $words = static::words($nbWords);
        $words[0] = ucwords($words[0]);

        return implode(' ', $words) . '.';
    }

    public static function sentences($nb = 3, $asText = false)
    {
        $sentences = [];

        for ($i = 0; $i < $nb; ++$i) {
            $sentences[] = static::sentence();
        }

        return $asText ? implode(' ', $sentences) : $sentences;
    }

    public static function paragraph($nbSentences = 3, $variableNbSentences = true)
    {
        if ($nbSentences <= 0) {
            return '';
        }

        if ($variableNbSentences) {
            $nbSentences = self::randomizeNbElements($nbSentences);
        }

        return implode(' ', static::sentences($nbSentences));
    }

    public static function paragraphs($nb = 3, $asText = false)
    {
        $paragraphs = [];

        for ($i = 0; $i < $nb; ++$i) {
            $paragraphs[] = static::paragraph();
        }

        return $asText ? implode("\n\n", $paragraphs) : $paragraphs;
    }

    public static function text($maxNbChars = 200)
    {
        $text = [];

        if ($maxNbChars < 5) {
            throw new \InvalidArgumentException(
                'Lorem::text() can only generate text of at least 5 characters'
            );
        } elseif ($maxNbChars < 25) {
            while (empty($text)) {
                $size = 0;

                while ($size < $maxNbChars) {
                    $word = ($size ? ' ' : '') . static::word();
                    $text[] = $word;
                    $size += mb_strlen((string) $word, '8bit');
                }

                array_pop($text);
            }

            $text[0][0] = static::toUpper($text[0][0]);
            $text[count($text) - 1] .= '.';
        } elseif ($maxNbChars < 100) {
            while (empty($text)) {
                $size = 0;

                while ($size < $maxNbChars) {
                    $sentence = ($size ? ' ' : '') . static::sentence();
                    $text[] = $sentence;
                    $size += mb_strlen((string) $sentence, '8bit');
                }

                array_pop($text);
            }
        } else {
            while (empty($text)) {
                $size = 0;

                while ($size < $maxNbChars) {
                    $paragraph = ($size ? "\n" : '') . static::paragraph();
                    $text[] = $paragraph;
                    $size += mb_strlen((string) $paragraph, '8bit');
                }

                array_pop($text);
            }
        }

        return implode('', $text);
    }

    protected static function randomizeNbElements($nbElements)
    {
        return (int) ($nbElements * mt_rand(60, 140) / 100) + 1;
    }
}
