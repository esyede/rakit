<?php

namespace System\Paginator;

defined('DS') or exit('No direct access.');

use System\Lang;
use System\Paginator;
use System\Str;

class Cursor extends Paginator
{
    /**
     * The cursor of the next page, NULL on the last one.
     *
     * @var string|null
     */
    public $next_cursor;

    /**
     * The cursor of the previous page, NULL on the first one.
     *
     * @var string|null
     */
    public $previous_cursor;

    /**
     * Create a cursor paginator.
     *
     * @param array|\System\Collection $results
     * @param int                      $perpage
     * @param string|null              $next
     * @param string|null              $previous
     * @param string                   $cursor_name
     *
     * @return Cursor
     */
    public static function cursor($results, $perpage, $next, $previous, $cursor_name = 'cursor')
    {
        $paginator = new static($results, 1, null, max(1, (int) $perpage), 1, $cursor_name);
        $paginator->next_cursor = $next;
        $paginator->previous_cursor = $previous;

        return $paginator;
    }

    /**
     * Encode a position as a cursor.
     *
     * @param array $values    the ordering columns' values of the row
     * @param bool  $backwards whether the cursor reads the rows before it
     *
     * @return string
     */
    public static function encode(array $values, $backwards)
    {
        return rtrim(strtr(base64_encode(json_encode(['v' => $values, 'b' => (bool) $backwards])), '+/', '-_'), '=');
    }

    /**
     * Decode a cursor; NULL when it is not one, or does not hold the given columns.
     *
     * @param mixed $cursor
     * @param array $keys
     *
     * @return array|null [values, backwards]
     */
    public static function decode($cursor, array $keys)
    {
        if (! is_string($cursor) || '' === $cursor || strlen($cursor) > 4096) {
            return null;
        }

        $data = json_decode((string) base64_decode(strtr($cursor, '-_', '+/'), true), true);

        if (! is_array($data) || ! isset($data['v']) || ! is_array($data['v']) || array_keys($data['v']) !== $keys) {
            return null;
        }

        foreach ($data['v'] as $value) {
            if (! is_scalar($value)) {
                return null;
            }
        }

        return [$data['v'], ! empty($data['b'])];
    }

    /**
     * Get the url of a cursor.
     *
     * @param string $cursor
     *
     * @return string
     */
    public function cursor_url($cursor)
    {
        $query = array_merge($this->appends, [$this->page_name => $cursor]);
        $path = $this->path();

        return $path.(Str::contains($path, '?') ? '&' : '?').http_build_query($query).$this->build_fragment();
    }

    /**
     * Get the url of the next page.
     *
     * @return string|null
     */
    public function next_page_url()
    {
        return is_null($this->next_cursor) ? null : $this->cursor_url($this->next_cursor);
    }

    /**
     * Get the url of the previous page.
     *
     * @return string|null
     */
    public function previous_page_url()
    {
        return is_null($this->previous_cursor) ? null : $this->cursor_url($this->previous_cursor);
    }

    /**
     * Check whether a next page exists.
     *
     * @return bool
     */
    public function has_more_pages()
    {
        return ! is_null($this->next_cursor);
    }

    /**
     * Check whether this is the first page.
     *
     * @return bool
     */
    public function on_first_page()
    {
        return is_null($this->previous_cursor);
    }

    /**
     * Check whether this is the last page.
     *
     * @return bool
     */
    public function on_last_page()
    {
        return is_null($this->next_cursor);
    }

    /**
     * Check whether there is more than one page.
     *
     * @return bool
     */
    public function has_pages()
    {
        return ! ($this->on_first_page() && $this->on_last_page());
    }

    /**
     * Get the previous and next links; a cursor paginator has no page numbers.
     *
     * @param int $adjacent
     *
     * @return array
     */
    public function link_elements($adjacent = 3)
    {
        $element = function ($type, $url) {
            return [
                'type' => $type,
                'page' => null,
                'url' => $url,
                'label' => Lang::line('pagination.'.$type)->get($this->language),
                'active' => false,
                'disabled' => is_null($url),
            ];
        };

        return [$element('previous', $this->previous_page_url()), $element('next', $this->next_page_url())];
    }

    /**
     * Convert the paginator into an array.
     *
     * @return array
     */
    public function to_array()
    {
        return [
            'data' => $this->results_to_array(),
            'path' => $this->path(),
            'per_page' => $this->perpage,
            'next_cursor' => $this->next_cursor,
            'next_page_url' => $this->next_page_url(),
            'prev_cursor' => $this->previous_cursor,
            'prev_page_url' => $this->previous_page_url(),
        ];
    }
}
