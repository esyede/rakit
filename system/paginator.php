<?php

namespace System;

defined('DS') or exit('No direct access.');

class Paginator implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * Name of the default pagination view.
     *
     * @var string
     */
    const VIEW = 'paginator';

    /**
     * Name of the view used to render the pagination links.
     *
     * @var string
     */
    public static $view = 'paginator';

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
     * Language that should be used when creating pagination links.
     *
     * @var string
     */
    protected $language;

    /**
     * Name of the query string parameter holding the page number.
     *
     * @var string
     */
    protected $page_name = 'page';

    /**
     * Fragment (hash) that should be appended to every pagination link.
     *
     * @var string
     */
    protected $fragment;

    /**
     * Constructor.
     *
     * @param array  $results
     * @param int    $page
     * @param int    $total
     * @param int    $perpage
     * @param int    $last
     * @param string $page_name
     */
    protected function __construct($results, $page, $total, $perpage, $last, $page_name = 'page')
    {
        $this->page = $page;
        $this->last = $last;
        $this->total = $total;
        $this->results = $results;
        $this->perpage = $perpage;
        $this->appends = [];
        $this->language = null;
        $this->page_name = $page_name;
    }

    /**
     * Creates a new Paginator instance.
     *
     * @param array  $results
     * @param int    $total
     * @param int    $perpage
     * @param string $page_name
     * @param int    $page
     *
     * @return Paginator
     */
    public static function make($results, $total, $perpage, $page_name = 'page', $page = null)
    {
        $perpage = (int) $perpage;
        $perpage = ($perpage < 1) ? 1 : $perpage;

        $total = (int) $total;
        $total = ($total < 0) ? 0 : $total;

        // The last page is always at least 1, even when there is no result at all.
        $last = (int) ceil($total / $perpage);
        $last = ($last < 1) ? 1 : $last;

        $page = is_null($page) ? static::page($total, $perpage, $page_name) : (int) $page;

        return new static($results, $page, $total, $perpage, $last, $page_name);
    }

    /**
     * Get the current page from the query string.
     *
     * @param int    $total
     * @param int    $perpage
     * @param string $page_name
     *
     * @return int
     */
    public static function page($total, $perpage, $page_name = 'page')
    {
        $page = Input::get($page_name, 1);
        $perpage = (int) $perpage;
        $perpage = ($perpage < 1) ? 1 : $perpage;

        if (is_numeric($page) && $page > ceil($total / $perpage)) {
            $last = (int) ceil($total / $perpage);
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
     * Get the results for the current page.
     *
     * @return array
     */
    public function items()
    {
        return $this->results;
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function current_page()
    {
        return $this->page;
    }

    /**
     * Get the last page number.
     *
     * @return int
     */
    public function last_page()
    {
        return $this->last;
    }

    /**
     * Get the total number of records.
     *
     * @return int
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * Get the number of items shown per page.
     *
     * @return int
     */
    public function per_page()
    {
        return $this->perpage;
    }

    /**
     * Get the number of the first item of the current page.
     *
     * @return int|null
     */
    public function first_item()
    {
        return ($this->count() > 0) ? (($this->page - 1) * $this->perpage) + 1 : null;
    }

    /**
     * Get the number of the last item of the current page.
     *
     * @return int|null
     */
    public function last_item()
    {
        return ($this->count() > 0) ? $this->first_item() + $this->count() - 1 : null;
    }

    /**
     * Check whether there is more than one page.
     *
     * @return bool
     */
    public function has_pages()
    {
        return (1 !== (int) $this->page) || $this->has_more_pages();
    }

    /**
     * Check whether there are more pages after the current one.
     *
     * @return bool
     */
    public function has_more_pages()
    {
        return $this->page < $this->last;
    }

    /**
     * Check whether the paginator is on the first page.
     *
     * @return bool
     */
    public function on_first_page()
    {
        return $this->page <= 1;
    }

    /**
     * Check whether the paginator is on the last page.
     *
     * @return bool
     */
    public function on_last_page()
    {
        return ! $this->has_more_pages();
    }

    /**
     * Get the base url of the pagination links (without the query string).
     *
     * @return string
     */
    public function path()
    {
        return URL::current();
    }

    /**
     * Get the name of the query string parameter holding the page number.
     *
     * @return string
     */
    public function page_name()
    {
        return $this->page_name;
    }

    /**
     * Get the url of the given page number.
     *
     * @param int $page
     *
     * @return string
     */
    public function url($page)
    {
        $page = ((int) $page < 1) ? 1 : (int) $page;
        $query = array_merge($this->appends, [$this->page_name => $page]);
        $path = $this->path();

        return $path . (Str::contains($path, '?') ? '&' : '?') . http_build_query($query) . $this->build_fragment();
    }

    /**
     * Get the url of the first page.
     *
     * @return string
     */
    public function first_page_url()
    {
        return $this->url(1);
    }

    /**
     * Get the url of the last page.
     *
     * @return string
     */
    public function last_page_url()
    {
        return $this->url($this->last);
    }

    /**
     * Get the url of the next page.
     *
     * @return string|null
     */
    public function next_page_url()
    {
        return $this->has_more_pages() ? $this->url($this->page + 1) : null;
    }

    /**
     * Get the url of the previous page.
     *
     * @return string|null
     */
    public function previous_page_url()
    {
        return ($this->page > 1) ? $this->url($this->page - 1) : null;
    }

    /**
     * Render the pagination links.
     * When the view given by the $view parameter does not exist, an exception
     * will be thrown. But when it is the configured view that does not exist,
     * the default view shipped with the framework will be used instead.
     *
     * @param int    $adjacent
     * @param string $view
     *
     * @return string
     */
    public function links($adjacent = 3, $view = null)
    {
        return View::make($this->view_name($view), [
            'paginator' => $this,
            'elements' => $this->link_elements($adjacent),
        ])->render();
    }

    /**
     * Get the name of the view that should render the pagination links.
     * The default view is published from its stub the first time it is needed,
     * any other missing view is reported as an error instead.
     *
     * @param string $view
     *
     * @return string
     */
    protected function view_name($view)
    {
        $view = is_null($view) ? static::$view : $view;

        if (static::VIEW === $view && ! View::exists($view) && ! static::publish_view()) {
            throw new \Exception(sprintf('Unable to publish the pagination view to: %s', static::published_path()));
        }

        return $view;
    }

    /**
     * Copy the pagination view stub to the application's view directory.
     * The view is only published once, it will never be overwritten after that,
     * so any change made to it is safe.
     *
     * @return bool
     */
    public static function publish_view()
    {
        $target = static::published_path();

        if (is_file($target)) {
            return true;
        }

        $stub = static::stub_path();

        if (! is_file($stub)) {
            return false;
        }

        $directory = dirname($target);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
            return false;
        }

        return false !== @file_put_contents($target, file_get_contents($stub), LOCK_EX);
    }

    /**
     * Get the path of the pagination view stub.
     *
     * @return string
     */
    public static function stub_path()
    {
        return path('system') . 'console' . DS . 'commands' . DS . 'stubs' . DS . 'paginator.stub';
    }

    /**
     * Get the path the pagination view is published to.
     *
     * @return string
     */
    public static function published_path()
    {
        return path('app') . 'views' . DS . static::VIEW . '.blade.php';
    }

    /**
     * Render the pagination links (alias for links()).
     *
     * @param int    $adjacent
     * @param string $view
     *
     * @return string
     */
    public function render($adjacent = 3, $view = null)
    {
        return $this->links($adjacent, $view);
    }

    /**
     * Get the list of pagination elements as an array.
     *
     * @param int $adjacent
     *
     * @return array
     */
    public function link_elements($adjacent = 3)
    {
        $previous = Lang::line('pagination.previous')->get($this->language);
        $next = Lang::line('pagination.next')->get($this->language);
        $elements = [$this->element('previous', $this->page - 1, $previous, ($this->page <= 1))];

        foreach ($this->page_numbers($adjacent) as $page) {
            $elements[] = is_null($page)
                ? $this->element('separator', null, '...', true)
                : $this->element('page', $page, (string) $page, false);
        }

        $elements[] = $this->element('next', $this->page + 1, $next, ($this->page >= $this->last));

        return $elements;
    }

    /**
     * Make a single pagination element.
     *
     * @param string $type
     * @param int    $page
     * @param string $label
     * @param bool   $disabled
     *
     * @return array
     */
    protected function element($type, $page, $label, $disabled)
    {
        return [
            'type' => $type,
            'page' => $disabled ? null : (int) $page,
            'url' => $disabled ? null : $this->url($page),
            'label' => $label,
            'active' => ('page' === $type && $this->page === (int) $page),
            'disabled' => (bool) $disabled,
        ];
    }

    /**
     * Get the list of page numbers shown by the slider.
     * A NULL entry represents a separator (ellipsis).
     *
     * @param int $adjacent
     *
     * @return array
     */
    protected function page_numbers($adjacent = 3)
    {
        if ($this->last < (7 + ($adjacent * 2))) {
            return range(1, $this->last);
        }

        $window = $adjacent * 2;

        if ($this->page <= $window) {
            return array_merge(range(1, $window + 2), [null], range($this->last - 1, $this->last));
        } elseif ($this->page >= $this->last - $window) {
            return array_merge([1, 2], [null], range($this->last - $window - 2, $this->last));
        }

        return array_merge(
            [1, 2],
            [null],
            range($this->page - $adjacent, $this->page + $adjacent),
            [null],
            range($this->last - 1, $this->last)
        );
    }

    /**
     * Append values to the query string of pagination links.
     *
     * @param array|string $key
     * @param string       $value
     *
     * @return Paginator
     */
    public function appends($key, $value = null)
    {
        if (is_null($key)) {
            return $this;
        }

        $values = is_array($key) ? $key : [$key => $value];

        foreach ($values as $index => $item) {
            if ($index !== $this->page_name) {
                $this->appends[$index] = $item;
            }
        }

        return $this;
    }

    /**
     * Append all of the current query string values to the pagination links.
     *
     * @return Paginator
     */
    public function with_query_string()
    {
        $query = Input::query();
        return is_array($query) ? $this->appends($query) : $this;
    }

    /**
     * Set or get the fragment (hash) appended to every pagination link.
     *
     * @param string $fragment
     *
     * @return Paginator|string|null
     */
    public function fragment($fragment = null)
    {
        if (is_null($fragment)) {
            return $this->fragment;
        }

        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Make the fragment part of the pagination link.
     *
     * @return string
     */
    protected function build_fragment()
    {
        return is_null($this->fragment) ? '' : '#' . $this->fragment;
    }

    /**
     * Run the given callback over each result of the current page.
     *
     * @param callable $callback
     *
     * @return Paginator
     */
    public function through($callback)
    {
        if (is_object($this->results) && method_exists($this->results, 'map')) {
            $this->results = $this->results->map($callback);
        } else {
            $this->results = array_map($callback, (array) $this->results);
        }

        return $this;
    }

    /**
     * Check whether the current page has no result at all.
     *
     * @return bool
     */
    public function is_empty()
    {
        return 0 === $this->count();
    }

    /**
     * Check whether the current page has at least one result.
     *
     * @return bool
     */
    public function is_not_empty()
    {
        return $this->count() > 0;
    }

    /**
     * Convert the results of the current page into an array.
     *
     * @return array
     */
    protected function results_to_array()
    {
        $results = $this->results;

        if (is_object($results) && method_exists($results, 'to_array')) {
            return $results->to_array();
        }

        $data = [];

        foreach ((array) $results as $key => $value) {
            if (is_object($value) && method_exists($value, 'to_array')) {
                $data[$key] = $value->to_array();
            } elseif ($value instanceof \JsonSerializable) {
                $data[$key] = $value->jsonSerialize();
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Convert the pagination elements into an array.
     *
     * @return array
     */
    protected function links_to_array()
    {
        $links = [];

        foreach ($this->link_elements() as $element) {
            $links[] = [
                'url' => $element['url'],
                'label' => $element['label'],
                'active' => $element['active'],
            ];
        }

        return $links;
    }

    /**
     * Convert the paginator into an array.
     *
     * @return array
     */
    public function to_array()
    {
        return [
            'current_page' => $this->page,
            'data' => $this->results_to_array(),
            'first_page_url' => $this->first_page_url(),
            'from' => $this->first_item(),
            'last_page' => $this->last,
            'last_page_url' => $this->last_page_url(),
            'links' => $this->links_to_array(),
            'next_page_url' => $this->next_page_url(),
            'path' => $this->path(),
            'per_page' => $this->perpage,
            'prev_page_url' => $this->previous_page_url(),
            'to' => $this->last_item(),
            'total' => $this->total,
        ];
    }

    /**
     * Convert the paginator into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->to_array();
    }

    /**
     * Convert the paginator into JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function to_json($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get an iterator for the results of the current page.
     *
     * @return \Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return ($this->results instanceof \Traversable) ? $this->results : new \ArrayIterator((array) $this->results);
    }

    /**
     * Get the number of results of the current page.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count(($this->results instanceof \Countable) ? $this->results : (array) $this->results);
    }

    /**
     * Check whether a result exists at the given offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return ($this->results instanceof \ArrayAccess)
            ? $this->results->offsetExists($key)
            : array_key_exists($key, (array) $this->results);
    }

    /**
     * Get the result at the given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if ($this->results instanceof \ArrayAccess) {
            return $this->results->offsetExists($key) ? $this->results->offsetGet($key) : null;
        }

        $results = (array) $this->results;

        return isset($results[$key]) ? $results[$key] : null;
    }

    /**
     * Set the result at the given offset.
     *
     * @param mixed $key
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->results[] = $value;
        } else {
            $this->results[$key] = $value;
        }
    }

    /**
     * Unset the result at the given offset.
     *
     * @param mixed $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->results[$key]);
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
