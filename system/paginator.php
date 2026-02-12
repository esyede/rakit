<?php

namespace System;

defined('DS') or exit('No direct access.');

class Paginator
{
    /**
     * Contains the current pagination results.
     *
     * @var array
     */
    public $results;

    /**
     * Contains the current page number.
     *
     * @var int
     */
    public $page;

    /**
     * Contains the last page number.
     *
     * @var int
     */
    public $last;

    /**
     * Contains the total number of pages.
     *
     * @var int
     */
    public $total;

    /**
     * Contains the number of items per page.
     *
     * @var int
     */
    public $perpage;

    /**
     * Value that should be appended to the end of the query string.
     *
     * @var array
     */
    protected $appends;

    /**
     * Appends the page number to the query string.
     *
     * @var string
     */
    protected $appendage;

    /**
     * Language that should be used when creating pagination links.
     *
     * @var string
     */
    protected $language;

    /**
     * The dots element used in the pagination slider.
     *
     * @var string
     */
    protected $dots = '<li class="page-item page-dots disabled"><a class="page-link" href="#">...</a></li>';

    /**
     * Constructor.
     *
     * @param array $results
     * @param int   $page
     * @param int   $total
     * @param int   $perpage
     * @param int   $last
     */
    protected function __construct($results, $page, $total, $perpage, $last)
    {
        $this->page = $page;
        $this->last = $last;
        $this->total = $total;
        $this->results = $results;
        $this->perpage = $perpage;
        $this->appends = [];
        $this->language = null;
    }

    /**
     * Creates a new Paginator instance.
     *
     * @param array $results
     * @param int   $total
     * @param int   $perpage
     *
     * @return Paginator
     */
    public static function make($results, $total, $perpage)
    {
        return new static($results, static::page($total, $perpage), $total, $perpage, ceil($total / $perpage));
    }

    /**
     * Get the current page from the query string.
     *
     * @param int $total
     * @param int $perpage
     *
     * @return int
     */
    public static function page($total, $perpage)
    {
        $page = Input::get('page', 1);

        if (is_numeric($page) && $page > ceil($total / $perpage)) {
            $last = ceil($total / $perpage);
            return ($last > 0) ? $last : 1;
        }

        return static::valid($page) ? (int) $page : 1;
    }

    /**
     * Check if the given number is a valid page number.
     * A page number is considered valid if it is an integer greater than or equal to 1.
     *
     * @param int $page
     *
     * @return bool
     */
    protected static function valid($page)
    {
        return ($page >= 1 && false !== filter_var($page, FILTER_VALIDATE_INT));
    }

    /**
     * Create pagination links.
     *
     * <code>
     *
     *      // Create pagination links
     *      echo $paginator->links();
     *
     *      // Create pagination links using a specific range.
     *      echo $paginator->links(5);
     *
     * </code>
     *
     * @param int $adjacent
     *
     * @return string
     */
    public function links($adjacent = 3)
    {
        if ($this->last <= 1) {
            return '';
        }

        // The number 7 is hard-coded to calculate all constant elements
        // in the 'slider' range, such as the current page, two ellipses, and two
        // first and last pages.

        // If there are not enough pages to create a slider based on adjacent pages,
        // all pages will be displayed. Otherwise, we create a 'truncated' slider.
        $links = ($this->last < (7 + ($adjacent * 2))) ? $this->range(1, $this->last) : $this->slider($adjacent);
        $content = $this->previous() . $links . $this->next();
        $content = "\t" . '<ul class="pagination">' . "\n" . $content . "\n\t" . '</ul>';

        return '<nav class="pagination-nav">' . "\n" . $content . "\n" . '</nav>';
    }

    /**
     * Make slider HTML containing numeric links.
     * This method is similar to links(), the difference is that
     * this one does not display the first and last page.
     *
     * <code>
     *
     *      // Make a pagination slider
     *      echo $paginator->slider();
     *
     *      // Make a pagination slider based on a specific range
     *      echo $paginator->slider(5);
     *
     * </code>
     *
     * @param int $adjacent
     *
     * @return string
     */
    public function slider($adjacent = 3)
    {
        $window = $adjacent * 2;

        if ($this->page <= $window) { // 1 [2] 3 4 5 6 ... 23 24
            return $this->range(1, $window + 2) . ' ' . $this->ending();
        } elseif ($this->page >= $this->last - $window) { // 1 2 ... 32 33 34 35 [36] 37
            return $this->beginning() . ' ' . $this->range($this->last - $window - 2, $this->last);
        }

        // 1 2 ... 23 24 25 [26] 27 28 29 ... 51 52
        $content = $this->range($this->page - $adjacent, $this->page + $adjacent);
        return $this->beginning() . ' ' . $content . ' ' . $this->ending();
    }

    /**
     * Make a 'Previous' link.
     *
     * <code>
     *
     *      // Make a 'Previous' link
     *      echo $paginator->previous();
     *
     *      // Make a 'Previous' link with custom text
     *      echo $paginator->previous('Back');
     *
     * </code>
     *
     * @param string $text
     *
     * @return string
     */
    public function previous($text = null)
    {
        return $this->element('previous', $this->page - 1, $text, function ($page) {
            return ($page <= 1);
        });
    }

    /**
     * Make a 'Next' link.
     *
     * <code>
     *
     *      // Make a 'Next' link
     *      echo $paginator->next();
     *
     *      // Make a 'Next' link with custom text
     *      echo $paginator->next('Forward');
     *
     * </code>
     *
     * @param string $text
     *
     * @return string
     */
    public function next($text = null)
    {
        return $this->element('next', $this->page + 1, $text, function ($page, $last) {
            return ($page >= $last);
        });
    }

    /**
     * Make a numbered pagination link.
     *
     * @param string   $element
     * @param int      $page
     * @param string   $text
     * @param \Closure $disabled
     *
     * @return string
     */
    protected function element($element, $page, $text, \Closure $disabled)
    {
        $class = $element . '_page';
        $text = is_null($text) ? Lang::line('pagination.' . $element)->get($this->language) : $text;

        if ($disabled($this->page, $this->last)) {
            $attributes = trim(static::attributes(['class' => $class . ' page-item disabled']));
            return sprintf("\t\t<li %s><a class=\"page-link\" href=\"#\">%s</a></li>\n", $attributes, $text);
        }

        return $this->link($page, $text, $class);
    }

    /**
     * Make 2 initial pagination links.
     *
     * @return string
     */
    protected function beginning()
    {
        return sprintf("%s\t\t%s\n", $this->range(1, 2), $this->dots);
    }

    /**
     * Make 2 final pagination links.
     *
     * @return string
     */
    protected function ending()
    {
        return sprintf("\t\t%s\n%s", $this->dots, $this->range($this->last - 1, $this->last));
    }

    /**
     * Make a numbered pagination link.
     * Only show as text for the current page.
     *
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    protected function range($start, $end)
    {
        $pages = [];

        for ($page = $start; $page <= $end; ++$page) {
            $pages[] = ($this->page === $page)
                ? sprintf("\t\t<li class=\"page-item active\"><a class=\"page-link\" href=\"#\">%s</a></li>\n", $page)
                : $this->link($page, $page, null);
        }

        return implode(' ', $pages);
    }

    /**
     * Make a numbered pagination link.
     *
     * @param int    $page
     * @param string $text
     * @param string $class
     *
     * @return string
     */
    protected function link($page, $text, $class)
    {
        return sprintf(
            "\t\t<li %s><a class=\"page-link\" href=\"%s\">%s</a></li>\n",
            trim(static::attributes(['class' => $class . ' page-item'])),
            URI::current() . '?page=' . $page . $this->appendage($this->appends),
            e($text)
        );
    }

    /**
     * Make an ending pagination link to be appended to each pagination link.
     *
     * @param array $appends
     *
     * @return string
     */
    protected function appendage(array $appends = [])
    {
        if (!is_null($this->appendage)) {
            return $this->appendage;
        }

        $appends = empty($appends) ? [] : $appends;
        return $this->appendage = (count($appends) <= 0) ? '&' . http_build_query($appends) : '';
    }

    /**
     * Append values to the query string of pagination links.
     *
     * @param array $values
     *
     * @return Paginator
     */
    public function appends(array $values)
    {
        $this->appends = $values;
        return $this;
    }

    /**
     * Make an HTML attribute string from the given array.
     *
     * @param array $attributes
     *
     * @return string
     */
    protected static function attributes(array $attributes)
    {
        $html = [];

        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }

            if (!is_null($value)) {
                $html[] = $key . '="' . e($value) . '"';
            }
        }

        return (count($html) > 0) ? ' ' . implode(' ', $html) : '';
    }

    /**
     * Set the language to be used for creating pagination links.
     *
     * @param string $language
     *
     * @return Paginator
     */
    public function speaks($language)
    {
        $this->language = $language;
        return $this;
    }
}
