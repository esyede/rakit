<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Markdown
{
    private static $factory;

    protected $definitions;
    protected $breaks;
    protected $escaping;
    protected $linking = true;
    protected $safety;
    protected $unmarking = ['code'];
    protected $markers = '!"*_&[:<>`~\\';
    protected $attrs = '[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:[^"\'=<>`\s]+|"[^"]*"|\'[^\']*\'))?';
    protected $inlines = [
        '"' => ['specials'],
        '!' => ['image'],
        '&' => ['specials'],
        '*' => ['emphasis'],
        ':' => ['url'],
        '<' => ['scheme', 'mailto', 'markup', 'specials'],
        '>' => ['specials'],
        '[' => ['link'],
        '_' => ['emphasis'],
        '`' => ['code'],
        '~' => ['strike'],
        '\\' => ['escaper'],
    ];

    protected $schemas = [
        'http://', 'https://', 'ftp://', 'ftps://', 'mailto:',
        'data:image/png;base64,', 'data:image/gif;base64,',
        'data:image/jpeg;base64,', 'irc:', 'ircs:', 'git:',
        'ssh:', 'news:', 'steam:',
    ];

    protected $types = [
        '#' => ['header'],
        '*' => ['rule', 'listing'],
        '+' => ['listing'],
        '-' => ['setext', 'table', 'rule', 'listing'],
        '0' => ['listing'],
        '1' => ['listing'],
        '2' => ['listing'],
        '3' => ['listing'],
        '4' => ['listing'],
        '5' => ['listing'],
        '6' => ['listing'],
        '7' => ['listing'],
        '8' => ['listing'],
        '9' => ['listing'],
        ':' => ['table'],
        '<' => ['comment', 'markup'],
        '=' => ['setext'],
        '>' => ['quote'],
        '[' => ['reference'],
        '_' => ['rule'],
        '`' => ['fenced'],
        '|' => ['table'],
        '~' => ['fenced'],
    ];

    protected $specials = [
        '\\', '`', '*', '_', '{', '}', '[', ']',
        '(', ')', '>', '#', '+', '-', '.', '!', '|',
    ];

    protected $strongs = [
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
    ];

    protected $emphasis = [
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];

    protected $voids = [
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr',
        'img', 'input', 'link', 'meta', 'param', 'source',
    ];

    protected $formattings = [
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code', 'strike', 'marquee',
        'q', 'rt', 'ins', 'font', 'strong',
        's', 'tt', 'kbd', 'mark', 'u', 'xm', 'sub', 'nobr',
        'sup', 'ruby', 'var', 'span', 'wbr', 'time',
    ];

    /**
     * Render file markdown menjadi html.
     *
     * @param string $file
     *
     * @return string
     */
    public static function render($file)
    {
        $file = Storage::get($file);
        return static::parse($file);
    }

    /**
     * Parse string markdown menjadi html.
     *
     * @param string $string
     *
     * @return string
     */
    public static function parse($string)
    {
        return static::factory()->translate($string);
    }

    /**
     * Ambil singleton object.
     *
     * @return $this
     */
    public static function factory()
    {
        if (! static::$factory) {
            static::$factory = new static();
        }

        return static::$factory;
    }

    /**
     * Ubah string markdown menjadi html.
     *
     * @param string $string
     *
     * @return string
     */
    public function translate($string)
    {
        $this->definitions = [];
        $string = explode("\n", trim(str_replace(["\r\n", "\r"], "\n", $string), "\n"));

        return trim($this->lines($string), "\n");
    }

    /**
     * Parse inline markdown.
     *
     * @param string $text
     * @param array  $nonces
     *
     * @return string
     */
    public function line($text, array $nonces = [])
    {
        $markup = '';

        while ($excerpt = strpbrk($text, $this->markers)) {
            $marker = $excerpt[0];
            $pos = strpos($text, $marker);
            $not = ['text' => $excerpt, 'context' => $text];

            foreach ($this->inlines[$marker] as $inline) {
                if (! empty($nonces) && in_array($inline, $nonces)) {
                    continue;
                }

                $rows = $this->{'inline_'.$inline}($not);

                if (! isset($rows)) {
                    continue;
                }

                if (isset($rows['position']) && $rows['position'] > $pos) {
                    continue;
                }

                if (! isset($rows['position'])) {
                    $rows['position'] = $pos;
                }

                foreach ($nonces as $item) {
                    $rows['element']['non_nestables'][] = $item;
                }

                $markup .= $this->unmarked(substr($text, 0, $rows['position']));
                $markup .= isset($rows['markup']) ? $rows['markup'] : $this->element($rows['element']);
                $text = substr($text, $rows['position'] + $rows['extent']);

                continue 2;
            }

            $markup .= $this->unmarked(substr($text, 0, $pos + 1));
            $text = substr($text, $pos + 1);
        }

        return $markup.$this->unmarked($text);
    }

    /**
     * Ubah newline menjadi tag HTML '<br />'.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function breaks($enable = true)
    {
        $this->breaks = (bool) $enable;
        return $this;
    }

    /**
     * Aktifkan escape htmlspecialchars.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function escaping($enable = true)
    {
        $this->escaping = (bool) $enable;
        return $this;
    }

    /**
     * Ubah string URL menjadi link aktif.
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function linkify($enable = true)
    {
        $this->linking = (bool) $enable;
        return $this;
    }

    /**
     * Aktifkan fitur keamanan (basic).
     *
     * @param bool $enable
     *
     * @return $this
     */
    public function safety($enable)
    {
        $this->safety = (bool) $enable;
        return $this;
    }

    protected function lines(array $parameters)
    {
        $curr = null;

        foreach ($parameters as $attri) {
            if ('' === rtrim($attri)) {
                if (isset($curr)) {
                    $curr['interrupted'] = true;
                }

                continue;
            }

            if (false !== strpos($attri, "\t")) {
                $parts = explode("\t", $attri);
                $attri = $parts[0];
                unset($parts[0]);

                foreach ($parts as $part) {
                    $attri .= str_repeat(' ', (4 - mb_strlen($attri, 'utf-8') % 4)).$part;
                }
            }

            $indent = 0;

            while (isset($attri[$indent]) && ' ' === $attri[$indent]) {
                ++$indent;
            }

            $text = ($indent > 0) ? substr($attri, $indent) : $attri;
            $tag = ['body' => $attri, 'indent' => $indent, 'text' => $text];

            if (isset($curr['continuable'])) {
                $attrib = $this->{'block_'.$curr['type'].'_continue'}($tag, $curr);

                if (isset($attrib)) {
                    $curr = $attrib;
                    continue;
                }

                if ($this->completable($curr['type'])) {
                    $curr = $this->{'block_'.$curr['type'].'_complete'}($curr);
                }
            }

            $marker = $text[0];
            $types = $this->unmarking;

            if (isset($this->types[$marker])) {
                foreach ($this->types[$marker] as $type) {
                    $types[] = $type;
                }
            }

            foreach ($types as $type) {
                $attrib = $this->{'block_'.$type}($tag, $curr);

                if (isset($attrib)) {
                    $attrib['type'] = $type;

                    if (! isset($attrib['identified'])) {
                        $attribs[] = $curr;
                        $attrib['identified'] = true;
                    }

                    if ($this->continuable($type)) {
                        $attrib['continuable'] = true;
                    }

                    $curr = $attrib;

                    continue 2;
                }
            }

            if (isset($curr) && ! isset($curr['type']) && ! isset($curr['interrupted'])) {
                $curr['element']['text'] .= "\n".$text;
            } else {
                $attribs[] = $curr;
                $curr = $this->paragraph($tag);
                $curr['identified'] = true;
            }
        }

        if (isset($curr['continuable']) && $this->completable($curr['type'])) {
            $curr = $this->{'block_'.$curr['type'].'_complete'}($curr);
        }

        $attribs[] = $curr;
        unset($attribs[0]);

        $markup = '';

        foreach ($attribs as $attrib) {
            if (isset($attrib['hidden'])) {
                continue;
            }

            $markup .= "\n";
            $markup .= isset($attrib['markup']) ? $attrib['markup'] : $this->element($attrib['element']);
        }

        return $markup."\n";
    }

    protected function continuable($type)
    {
        return method_exists($this, 'block_'.$type.'_continue');
    }

    protected function completable($type)
    {
        return method_exists($this, 'block_'.$type.'_complete');
    }

    protected function block_code($tag, $attrib = null)
    {
        if (isset($attrib) && ! isset($attrib['type']) && ! isset($attrib['interrupted'])) {
            return;
        }

        if ($tag['indent'] >= 4) {
            $text = substr($tag['body'], 4);
            return [
                'element' => [
                    'name' => 'pre',
                    'handler' => 'element', 'text' => ['name' => 'code', 'text' => $text],
                ],
            ];
        }
    }

    protected function block_code_continue($tag, $attrib)
    {
        if ($tag['indent'] >= 4) {
            if (isset($attrib['interrupted'])) {
                $attrib['element']['text']['text'] .= "\n";
                unset($attrib['interrupted']);
            }

            $attrib['element']['text']['text'] .= "\n";
            $text = substr($tag['body'], 4);
            $attrib['element']['text']['text'] .= $text;

            return $attrib;
        }
    }

    protected function block_code_complete($attrib)
    {
        $text = $attrib['element']['text']['text'];
        $attrib['element']['text']['text'] = $text;

        return $attrib;
    }

    protected function block_comment($tag)
    {
        if ($this->escaping || $this->safety) {
            return;
        }

        if (isset($tag['text'][3])
        && '-' === $tag['text'][3]
        && '-' === $tag['text'][2]
        && '!' === $tag['text'][1]) {
            $attrib = ['markup' => $tag['body']];

            if (preg_match('/-->$/', $tag['text'])) {
                $attrib['closed'] = true;
            }

            return $attrib;
        }
    }

    protected function block_comment_continue($tag, array $attrib)
    {
        if (isset($attrib['closed'])) {
            return;
        }

        $attrib['markup'] .= "\n".$tag['body'];

        if (preg_match('/-->$/', $tag['text'])) {
            $attrib['closed'] = true;
        }

        return $attrib;
    }

    protected function block_fenced($tag)
    {
        $pattern = '/^['.$tag['text'][0].']{3,}[ ]*([^`]+)?[ ]*$/';

        if (preg_match($pattern, $tag['text'], $matches)) {
            $elem = ['name' => 'code', 'text' => ''];

            if (isset($matches[1])) {
                $language = substr($matches[1], 0, strcspn($matches[1], " \t\n\f\r"));
                $elem['attributes'] = ['class' => 'language-'.$language];
            }

            return [
                'char' => $tag['text'][0],
                'element' => ['name' => 'pre', 'handler' => 'element', 'text' => $elem],
            ];
        }
    }

    protected function block_fenced_continue($tag, $attrib)
    {
        if (isset($attrib['complete'])) {
            return;
        }

        if (isset($attrib['interrupted'])) {
            $attrib['element']['text']['text'] .= "\n";
            unset($attrib['interrupted']);
        }

        if (preg_match('/^'.$attrib['char'].'{3,}[ ]*$/', $tag['text'])) {
            $attrib['element']['text']['text'] = substr($attrib['element']['text']['text'], 1);
            $attrib['complete'] = true;
            return $attrib;
        }

        $attrib['element']['text']['text'] .= "\n".$tag['body'];
        return $attrib;
    }

    protected function block_fenced_complete($attrib)
    {
        $text = $attrib['element']['text']['text'];
        $attrib['element']['text']['text'] = $text;
        return $attrib;
    }

    protected function block_header($tag)
    {
        if (isset($tag['text'][1])) {
            $level = 1;

            while (isset($tag['text'][$level]) && '#' === $tag['text'][$level]) {
                ++$level;
            }

            if ($level > 6) {
                return;
            }

            $text = trim($tag['text'], '# ');
            return ['element' => ['name' => 'h'.min(6, $level), 'text' => $text, 'handler' => 'line']];
        }
    }

    protected function block_listing($tag)
    {
        list($name, $pattern) = $tag['text'][0] <= '-' ? ['ul', '[*+-]'] : ['ol', '[0-9]+[.]'];

        if (preg_match('/^('.$pattern.'[ ]+)(.*)/', $tag['text'], $matches)) {
            $attrib = [
                'indent' => $tag['indent'],
                'pattern' => $pattern,
                'element' => ['name' => $name, 'handler' => 'elements'],
            ];

            if ('ol' === $name) {
                $start = stristr($matches[0], '.', true);

                if ('1' !== $start) {
                    $attrib['element']['attributes'] = ['start' => $start];
                }
            }

            $attrib['li'] = ['name' => 'li', 'handler' => 'li', 'text' => [$matches[2]]];
            $attrib['element']['text'][] = &$attrib['li'];

            return $attrib;
        }
    }

    protected function block_listing_continue($tag, array $attrib)
    {
        $pattern = '/^'.$attrib['pattern'].'(?:[ ]+(.*)|$)/';

        if ($attrib['indent'] === $tag['indent'] && preg_match($pattern, $tag['text'], $matches)) {
            if (isset($attrib['interrupted'])) {
                $attrib['li']['text'][] = '';
                $attrib['loose'] = true;
                unset($attrib['interrupted']);
            }

            unset($attrib['li']);

            $text = isset($matches[1]) ? $matches[1] : '';
            $attrib['li'] = ['name' => 'li', 'handler' => 'li', 'text' => [$text]];
            $attrib['element']['text'][] = &$attrib['li'];

            return $attrib;
        }

        if ('[' === $tag['text'][0] && $this->block_reference($tag)) {
            return $attrib;
        }

        if (! isset($attrib['interrupted'])) {
            $text = preg_replace('/^[ ]{0,4}/', '', $tag['body']);
            $attrib['li']['text'][] = $text;

            return $attrib;
        }

        if ($tag['indent'] > 0) {
            $attrib['li']['text'][] = '';
            $text = preg_replace('/^[ ]{0,4}/', '', $tag['body']);
            $attrib['li']['text'][] = $text;
            unset($attrib['interrupted']);

            return $attrib;
        }
    }

    protected function block_listing_complete(array $attrib)
    {
        if (isset($attrib['loose'])) {
            foreach ($attrib['element']['text'] as &$li) {
                if ('' !== end($li['text'])) {
                    $li['text'][] = '';
                }
            }
        }

        return $attrib;
    }

    protected function block_quote($tag)
    {
        if (preg_match('/^>[ ]?(.*)/', $tag['text'], $matches)) {
            $matches = (array) $matches[1];
            return ['element' => ['name' => 'blockquote', 'handler' => 'lines', 'text' => $matches]];
        }
    }

    protected function block_quote_continue($tag, array $attrib)
    {
        if ('>' === $tag['text'][0] && preg_match('/^>[ ]?(.*)/', $tag['text'], $matches)) {
            if (isset($attrib['interrupted'])) {
                $attrib['element']['text'][] = '';
                unset($attrib['interrupted']);
            }

            $attrib['element']['text'][] = $matches[1];
            return $attrib;
        }

        if (! isset($attrib['interrupted'])) {
            $attrib['element']['text'][] = $tag['text'];
            return $attrib;
        }
    }

    protected function block_rule($tag)
    {
        if (preg_match('/^(['.$tag['text'][0].'])([ ]*\1){2,}[ ]*$/', $tag['text'])) {
            return ['element' => ['name' => 'hr']];
        }
    }

    protected function block_setext($tag, array $attrib = null)
    {
        if (! isset($attrib) || isset($attrib['type']) || isset($attrib['interrupted'])) {
            return;
        }

        if ('' === chop($tag['text'], $tag['text'][0])) {
            $attrib['element']['name'] = ('=' === $tag['text'][0]) ? 'h1' : 'h2';
            return $attrib;
        }
    }

    protected function block_markup($tag)
    {
        if ($this->escaping || $this->safety) {
            return;
        }

        $pattern = '/^<(\w[\w-]*)(?:[ ]*'.$this->attrs.')*[ ]*(\/)?>/';

        if (preg_match($pattern, $tag['text'], $matches)) {
            $element = strtolower($matches[1]);

            if (in_array($element, $this->formattings)) {
                return;
            }

            $attrib = ['name' => $matches[1], 'depth' => 0, 'markup' => $tag['text']];
            $length = strlen($matches[0]);
            $remainder = substr($tag['text'], $length);

            if ('' === trim($remainder)) {
                if (isset($matches[2]) || in_array($matches[1], $this->voids)) {
                    $attrib['closed'] = true;
                    $attrib['void'] = true;
                }
            } else {
                if (isset($matches[2]) || in_array($matches[1], $this->voids)) {
                    return;
                }

                if (preg_match('/<\/'.$matches[1].'>[ ]*$/i', $remainder)) {
                    $attrib['closed'] = true;
                }
            }

            return $attrib;
        }
    }

    protected function block_markup_continue($tag, array $attrib)
    {
        if (isset($attrib['closed'])) {
            return;
        }

        $pattern = '/^<'.$attrib['name'].'(?:[ ]*'.$this->attrs.')*[ ]*>/i';

        if (preg_match($pattern, $tag['text'])) {
            ++$attrib['depth'];
        }

        $pattern = '/(.*?)<\/'.$attrib['name'].'>[ ]*$/i';

        if (preg_match($pattern, $tag['text'], $matches)) {
            if ($attrib['depth'] > 0) {
                --$attrib['depth'];
            } else {
                $attrib['closed'] = true;
            }
        }

        if (isset($attrib['interrupted'])) {
            $attrib['markup'] .= "\n";
            unset($attrib['interrupted']);
        }

        $attrib['markup'] .= "\n".$tag['body'];
        return $attrib;
    }

    protected function block_reference($tag)
    {
        $pattern = '/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/';

        if (preg_match($pattern, $tag['text'], $matches)) {
            $id = strtolower($matches[1]);
            $data = ['url' => $matches[2], 'title' => null];

            if (isset($matches[3])) {
                $data['title'] = $matches[3];
            }

            $this->definitions['reference'][$id] = $data;
            return ['hidden' => true];
        }
    }

    protected function block_table($tag, array $attr = null)
    {
        if (! isset($attr) || isset($attr['type']) || isset($attr['interrupted'])) {
            return;
        }

        if (false !== strpos($attr['element']['text'], '|') && '' === chop($tag['text'], ' -:|')) {
            $alignments = [];
            $cells = explode('|', trim(trim($tag['text']), '|'));

            foreach ($cells as $cell) {
                $cell = trim($cell);

                if ('' === $cell) {
                    continue;
                }

                $alignment = null;

                if (':' === $cell[0]) {
                    $alignment = 'left';
                }

                if (':' === substr($cell, -1)) {
                    $alignment = ('left' === $alignment) ? 'center' : 'right';
                }

                $alignments[] = $alignment;
            }

            $elems = [];
            $hdrs = explode('|', trim(trim($attr['element']['text']), '|'));

            foreach ($hdrs as $index => $val) {
                $val = trim($val);
                $elem = ['name' => 'th', 'text' => $val, 'handler' => 'line'];

                if (isset($alignments[$index])) {
                    $alignment = $alignments[$index];
                    $elem['attributes'] = ['style' => 'text-align: '.$alignment.';'];
                }

                $elems[] = $elem;
            }

            $attr = [
                'alignments' => $alignments,
                'identified' => true,
                'element' => ['name' => 'table', 'handler' => 'elements'],
            ];

            $attr['element']['text'][] = ['name' => 'thead', 'handler' => 'elements'];
            $attr['element']['text'][] = ['name' => 'tbody', 'handler' => 'elements', 'text' => []];
            $attr['element']['text'][0]['text'][] = ['name' => 'tr', 'handler' => 'elements', 'text' => $elems];

            return $attr;
        }
    }

    protected function block_table_continue($tag, array $attrib)
    {
        if (isset($attrib['interrupted'])) {
            return;
        }

        if ('|' === $tag['text'][0] || strpos($tag['text'], '|')) {
            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/', trim(trim($tag['text']), '|'), $matches);
            $elems = [];

            foreach ($matches[0] as $index => $cell) {
                $cell = trim($cell);
                $elem = ['name' => 'td', 'handler' => 'line', 'text' => $cell];

                if (isset($attrib['alignments'][$index])) {
                    $elem['attributes'] = ['style' => 'text-align: '.$attrib['alignments'][$index].';'];
                }

                $elems[] = $elem;
            }

            $elem = ['name' => 'tr', 'handler' => 'elements', 'text' => $elems];
            $attrib['element']['text'][1]['text'][] = $elem;

            return $attrib;
        }
    }

    protected function paragraph($tag)
    {
        return ['element' => ['name' => 'p', 'text' => $tag['text'], 'handler' => 'line']];
    }

    protected function inline_code($not)
    {
        $marker = $not['text'][0];
        $pattern = '/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s';

        if (preg_match($pattern, $not['text'], $matches)) {
            $text = preg_replace("/[ ]*\n/", ' ', $matches[2]);
            return ['extent' => strlen($matches[0]), 'element' => ['name' => 'code', 'text' => $text]];
        }
    }

    protected function inline_mailto($not)
    {
        if (false !== strpos($not['text'], '>')
        && preg_match('/^<((mailto:)?\S+?@\S+?)>/i', $not['text'], $matches)) {
            $url = isset($matches[2]) ? $matches[1] : 'mailto:'.$matches[1];
            return [
                'extent' => strlen($matches[0]),
                'element' => ['name' => 'a', 'text' => $matches[1], 'attributes' => ['href' => $url]],
            ];
        }
    }

    protected function inline_emphasis($not)
    {
        if (! isset($not['text'][1])) {
            return;
        }

        $marker = $not['text'][0];

        if ($not['text'][1] === $marker && preg_match($this->strongs[$marker], $not['text'], $matches)) {
            $emphasis = 'strong';
        } elseif (preg_match($this->emphasis[$marker], $not['text'], $matches)) {
            $emphasis = 'em';
        } else {
            return;
        }

        return [
            'extent' => strlen($matches[0]),
            'element' => ['name' => $emphasis, 'handler' => 'line', 'text' => $matches[1]],
        ];
    }

    protected function inline_escaper($not)
    {
        if (isset($not['text'][1]) && in_array($not['text'][1], $this->specials)) {
            return ['markup' => $not['text'][1], 'extent' => 2];
        }
    }

    protected function inline_image($not)
    {
        if (! isset($not['text'][1]) || '[' !== $not['text'][1]) {
            return;
        }

        $not['text'] = substr($not['text'], 1);
        $href = $this->inline_link($not);

        if (null === $href) {
            return;
        }

        $rows = [
            'extent' => $href['extent'] + 1,
            'element' => [
                'name' => 'img',
                'attributes' => [
                    'src' => $href['element']['attributes']['href'],
                    'alt' => $href['element']['text'],
                ],
            ],
        ];

        $rows['element']['attributes'] += $href['element']['attributes'];
        unset($rows['element']['attributes']['href']);

        return $rows;
    }

    protected function inline_link($not)
    {
        $elem = [
            'name' => 'a',
            'handler' => 'line',
            'non_nestables' => ['url', 'link'],
            'text' => null,
            'attributes' => ['href' => null, 'title' => null],
        ];

        $extent = 0;
        $remainder = $not['text'];

        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) {
            $elem['text'] = $matches[1];
            $extent += strlen($matches[0]);
            $remainder = substr($remainder, $extent);
        } else {
            return;
        }

        $pattern = '/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*"|\'[^\']*\'))?\s*[)]/';

        if (preg_match($pattern, $remainder, $matches)) {
            $elem['attributes']['href'] = URL::to($matches[1]);

            if (isset($matches[2])) {
                $elem['attributes']['title'] = substr($matches[2], 1, -1);
            }

            $extent += strlen($matches[0]);
        } else {
            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
                $definition = strlen($matches[1]) ? $matches[1] : $elem['text'];
                $definition = strtolower($definition);
                $extent += strlen($matches[0]);
            } else {
                $definition = strtolower($elem['text']);
            }

            if (! isset($this->definitions['reference'][$definition])) {
                return;
            }

            $def = $this->definitions['reference'][$definition];
            $elem['attributes']['href'] = $def['url'];
            $elem['attributes']['title'] = $def['title'];
        }

        return ['extent' => $extent, 'element' => $elem];
    }

    protected function inline_markup($not)
    {
        if ($this->escaping || $this->safety || false === strpos($not['text'], '>')) {
            return;
        }

        if ('/' === $not['text'][1] && preg_match('/^<\/\w[\w-]*[ ]*>/s', $not['text'], $matches)) {
            return ['markup' => $matches[0], 'extent' => strlen($matches[0])];
        }

        $pattern = '/^<!---?[^>-](?:-?[^-])*-->/s';

        if ('!' === $not['text'][1] && preg_match($pattern, $not['text'], $matches)) {
            return ['markup' => $matches[0], 'extent' => strlen($matches[0])];
        }

        $pattern = '/^<\w[\w-]*(?:[ ]*'.$this->attrs.')*[ ]*\/?>/s';

        if (' ' !== $not['text'][1] && preg_match($pattern, $not['text'], $matches)) {
            return ['markup' => $matches[0], 'extent' => strlen($matches[0])];
        }
    }

    protected function inline_specials($not)
    {
        if ('&' === $not['text'][0] && ! preg_match('/^&#?\w+;/', $not['text'])) {
            return ['markup' => '&amp;', 'extent' => 1];
        }

        $specials = ['>' => 'gt', '<' => 'lt', '"' => 'quot'];

        if (isset($specials[$not['text'][0]])) {
            return ['markup' => '&'.$specials[$not['text'][0]].';', 'extent' => 1];
        }
    }

    protected function inline_strike($not)
    {
        if (! isset($not['text'][1])) {
            return;
        }

        $pattern = '/^~~(?=\S)(.+?)(?<=\S)~~/';

        if ('~' === $not['text'][1] && preg_match($pattern, $not['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => ['name' => 'del', 'text' => $matches[1], 'handler' => 'line'],
            ];
        }
    }

    protected function inline_url($not)
    {
        if (true !== $this->linking || ! isset($not['text'][2]) || '/' !== $not['text'][2]) {
            return;
        }

        if (preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $not['context'], $matches, PREG_OFFSET_CAPTURE)) {
            $url = $matches[0][0];
            return [
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => ['name' => 'a', 'text' => $url, 'attributes' => ['href' => $url]],
            ];
        }
    }

    protected function inline_scheme($not)
    {
        $pattern = '/^<(\w+:\/{2}[^ >]+)>/i';

        if (false !== strpos($not['text'], '>') && preg_match($pattern, $not['text'], $matches)) {
            $url = $matches[1];
            return [
                'extent' => strlen($matches[0]),
                'element' => ['name' => 'a', 'text' => $url, 'attributes' => ['href' => $url]],
            ];
        }
    }

    protected function unmarked($text)
    {
        if ($this->breaks) {
            $text = preg_replace('/[ ]*\n/', "<br />\n", $text);
        } else {
            $text = str_replace(" \n", "\n", preg_replace('/(?:[ ][ ]+|[ ]*\\\\)\n/', "<br />\n", $text));
        }

        return $text;
    }

    protected function element(array $elem)
    {
        $elem = $this->safety ? $this->sanitize($elem) : $elem;
        $markup = '<'.$elem['name'];

        if (isset($elem['attributes'])) {
            foreach ($elem['attributes'] as $name => $value) {
                if (null === $value) {
                    continue;
                }

                $markup .= ' '.$name.'="'.self::escape($value).'"';
            }
        }

        $raw = false;

        if (isset($elem['text'])) {
            $text = $elem['text'];
        } elseif (isset($elem['raw'])) {
            $text = $elem['raw'];
            $allow = isset($elem['loosey']) && $elem['loosey'];
            $raw = ((! $this->safety) || $allow);
        }

        if (isset($text)) {
            $markup .= '>';

            if (! isset($elem['non_nestables'])) {
                $elem['non_nestables'] = [];
            }

            if (isset($elem['handler'])) {
                $markup .= $this->{$elem['handler']}($text, $elem['non_nestables']);
            } elseif (! $raw) {
                $markup .= self::escape($text, true);
            } else {
                $markup .= $text;
            }

            $markup .= '</'.$elem['name'].'>';
        } else {
            $markup .= ' />';
        }

        return $markup;
    }

    protected function elements(array $elems)
    {
        $markup = '';

        foreach ($elems as $elem) {
            $markup .= "\n".$this->element($elem);
        }

        return $markup."\n";
    }

    protected function li($lines)
    {
        $markup = trim($this->lines($lines));

        if (! in_array('', $lines) && '<p>' === substr($markup, 0, 3)) {
            $markup = substr($markup, 3);
            $markup = substr_replace($markup, '', strpos($markup, '</p>'), 4);
        }

        return $markup;
    }

    protected function sanitize(array $elem)
    {
        static $good = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
        static $safenames = ['a' => 'href', 'img' => 'src'];

        if (isset($safenames[$elem['name']])) {
            $elem = $this->url_filter($elem, $safenames[$elem['name']]);
        }

        if (! empty($elem['attributes'])) {
            foreach ($elem['attributes'] as $att => $val) {
                if (! preg_match($good, $att)) {
                    unset($elem['attributes'][$att]);
                } elseif (self::starts($att, 'on')) {
                    unset($elem['attributes'][$att]);
                }
            }
        }

        return $elem;
    }

    protected function url_filter(array $elem, $attribute)
    {
        foreach ($this->schemas as $scheme) {
            if (self::starts($elem['attributes'][$attribute], $scheme)) {
                return $elem;
            }
        }

        $elem['attributes'][$attribute] = str_replace(':', '%3A', $elem['attributes'][$attribute]);
        return $elem;
    }

    protected static function escape($text, $quoting = false)
    {
        return htmlspecialchars($text, $quoting ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
    }

    protected static function starts($string, $needle)
    {
        $len = strlen($needle);
        return ($len > strlen($string)) ? false : strtolower(substr($string, 0, $len)) === strtolower($needle);
    }
}
