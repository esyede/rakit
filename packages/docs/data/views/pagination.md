# Data Pagination

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Using Query Builder](#using-query-builder)
-   [Cursor Pagination](#cursor-pagination)
-   [Displaying the Results](#displaying-the-results)
-   [Pagination Information](#pagination-information)
-   [Adding Pagination Links](#adding-pagination-links)
-   [Custom Page Name](#custom-page-name)
-   [Pagination as JSON](#pagination-as-json)
-   [Creating Manual Pagination](#creating-manual-pagination)
-   [Customizing the View](#customizing-the-view)
-   [Styling Pagination](#styling-pagination)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Pagination splits a large result set into pages. Both the
[Query Builder](/docs/database/magic) and [Facile models](/docs/database/facile) provide a
`paginate()` method that returns a `Paginator`, which knows the current page and renders the
page links for you.

<a id="using-query-builder"></a>

## Using Query Builder

Pass the number of records per page (20 by default) and, optionally, the columns to select:

```php
$orders = DB::table('orders')->paginate(10);
$orders = DB::table('orders')->paginate(10, ['id', 'name', 'created_at']);

$orders = DB::table('orders')
    ->where('status', '=', 'completed')
    ->order_by('created_at', 'desc')
    ->paginate(15);

$users = User::where('active', '=', true)->paginate(20);
```

The full signature is `paginate($perpage, $columns, $page_name, $page)`; the last two are covered
in [Custom Page Name](#custom-page-name).

> `GROUP BY`, `HAVING`, `distinct()` and a `take()`/`skip()` set before `paginate()` are all
> taken into account when counting, so the total is the real number of records.

`simple_paginate()` takes the same arguments but skips the `COUNT` query, which matters on big
tables: it fetches one row more than a page to know whether a next page exists. Its `total` is
`null`, and its links reach the next page only:

```php
$logs = DB::table('logs')->order_by('id', 'desc')->simple_paginate(50);
$logs = Log::where('level', '=', 'error')->simple_paginate(50);
```

<a id="cursor-pagination"></a>

## Cursor Pagination

`cursor_paginate()` pages by position instead of page number: the link to the next page holds
the last row's ordering values, and the next page starts after them
(`WHERE id > 120 ORDER BY id LIMIT 51`). Page 10.000 costs as little as page 1 (an `OFFSET`
reads and drops every row before it), and rows inserted while a user scrolls never shift a row
onto two pages. It suits feeds, infinite scrolling and API exports:

```php
$posts = DB::table('posts')->order_by('created_at', 'desc')->order_by('id', 'desc')->cursor_paginate(50);
$posts = Post::where('published', '=', 1)->cursor_paginate(50); // ordered by the primary key

{!! $posts->links() !!}  {{-- Previous / Next --}}
```

The page is read from the `cursor` query parameter (the third argument renames it, the fourth
passes a cursor yourself). `next_cursor`, `previous_cursor`, `next_page_url()` and
`previous_page_url()` give the neighbours, and `to_json()` returns `data`, `path`, `per_page`,
`next_cursor`, `next_page_url`, `prev_cursor` and `prev_page_url`.

There are rules for the `ORDER BY` columns:

-   They must be plain columns, not `order_by_raw()`.
-   They must be among the selected columns and never `NULL`.
-   Together they must be unique: end the ordering with the primary key, as above.

A cursor holds values only; the columns always come from the query, so a forged cursor cannot
change what is compared, and one that does not fit the query starts over on the first page.
There is no page count and no jumping to page N.

<a id="displaying-the-results"></a>

## Displaying the Results

The paginator is iterable, countable and array accessible, so loop over it directly and print
its links:

```php
@foreach ($orders as $order)
    <tr>
        <td>{{ $order->id }}</td>
        <td>{{ $order->name }}</td>
    </tr>
@endforeach

{!! $orders->links() !!}
```

`$orders->items()` (or the `$orders->results` property) returns the same `Collection`. `links()`
renders something like `Previous 1 2 ... 24 25 26 27 28 ... 78 79 Next` and renders nothing when
there is only one page. The current page is read from the query string automatically.

<a id="pagination-information"></a>

## Pagination Information

```php
$orders->items();             // results of the current page (a Collection)
$orders->total();             // total number of records
$orders->per_page();          // records per page
$orders->current_page();      // current page number
$orders->last_page();         // last page number, always at least 1
$orders->first_item();        // index of the first record on this page, NULL when empty
$orders->last_item();         // index of the last record on this page, NULL when empty
$orders->count();             // records on this page
$orders->is_empty();          // TRUE when this page holds no record
$orders->is_not_empty();
$orders->has_pages();         // TRUE when there is more than one page
$orders->has_more_pages();    // TRUE when there is a page after this one
$orders->on_first_page();
$orders->on_last_page();
$orders->page_name();         // query string parameter, 'page' by default

$orders->path();              // base url, without the query string
$orders->url(3);              // url of page 3
$orders->first_page_url();
$orders->last_page_url();
$orders->next_page_url();     // NULL on the last page
$orders->previous_page_url(); // NULL on the first page
```

The legacy public properties `results`, `page`, `last`, `total` and `perpage` hold the same
values.

`through()` transforms the results of the current page while keeping the pagination state:

```php
$orders->through(function ($order) {
    $order->total = number_format($order->total);
    return $order;
});
```

<a id="adding-pagination-links"></a>

## Adding Pagination Links

`appends()` adds values to the query string of every page link, for example the current sort.
It accepts an array or a key and value, and merges on repeated calls:

```php
{!! $orders->appends(['sort' => 'votes'])->links() !!}

$orders->appends('sort', 'votes')->appends('order', 'desc');
// mysite.com/orders?sort=votes&order=desc&page=3
```

`with_query_string()` keeps every parameter of the current request (except the page number),
and `fragment()` adds a hash:

```php
{!! $orders->with_query_string()->links() !!}
{!! $orders->fragment('list')->links() !!}   // mysite.com/orders?page=2#list
```

<a id="custom-page-name"></a>

## Custom Page Name

When one page shows two paginated lists, give each its own query string parameter so paging one
does not reset the other:

```php
$orders   = DB::table('orders')->paginate(10, ['*'], 'order_page');
$invoices = DB::table('invoices')->paginate(10, ['*'], 'invoice_page');
```

The fourth argument forces a page number instead of reading it from the query string:

```php
$orders = DB::table('orders')->paginate(10, ['*'], 'page', 3);
```

<a id="pagination-as-json"></a>

## Pagination as JSON

A paginator can be returned straight to a JavaScript front end:

```php
return Response::json(DB::table('orders')->paginate(3));
```

```json
{
    "current_page": 2,
    "data": [{ "id": 4, "name": "Order 4" }],
    "first_page_url": "https://mysite.com/orders?page=1",
    "from": 4,
    "last_page": 4,
    "last_page_url": "https://mysite.com/orders?page=4",
    "links": [
        { "url": "https://mysite.com/orders?page=1", "label": "Previous", "active": false },
        { "url": "https://mysite.com/orders?page=1", "label": "1", "active": false },
        { "url": "https://mysite.com/orders?page=2", "label": "2", "active": true },
        { "url": "https://mysite.com/orders?page=3", "label": "3", "active": false },
        { "url": "https://mysite.com/orders?page=4", "label": "4", "active": false },
        { "url": "https://mysite.com/orders?page=3", "label": "Next", "active": false }
    ],
    "next_page_url": "https://mysite.com/orders?page=3",
    "path": "https://mysite.com/orders",
    "per_page": 3,
    "prev_page_url": "https://mysite.com/orders?page=1",
    "to": 6,
    "total": 10
}
```

`to_array()`, `to_json()` and `json_encode()` produce the same structure. Facile model results
are converted with their own `to_array()`, so `$hidden`, `$visible`, `$appends` and `$casts` are
honored.

`link_elements()` returns a richer version of `links`, where each element also has a `type`
(`previous`, `next`, `page` or `separator`), a `page` number and a `disabled` flag. The pagination
view is built on it.

<a id="creating-manual-pagination"></a>

## Creating Manual Pagination

To paginate data that does not come from the query builder, such as an API response, slice it
yourself and hand it to `Paginator::make($results, $total, $perpage, $page_name, $page)`:

```php
$all = API::get_orders();
$perpage = 10;
$page = Paginator::page(count($all), $perpage); // current page from the query string

$items = array_slice($all, ($page - 1) * $perpage, $perpage);
$orders = Paginator::make($items, count($all), $perpage);
```

<a id="customizing-the-view"></a>

## Customizing the View

The markup lives in a Blade view. The first time `links()` is called, Rakit publishes
`system/console/commands/stubs/paginator.stub` to `application/views/paginator.blade.php`; edit
that file freely, it is never overwritten. The view receives `$paginator` and `$elements` (the
list from `link_elements()`):

```php
@if ($paginator->has_pages())
<nav class="pagination-nav">
    <ul class="pagination">
@foreach ($elements as $element)
@if ('separator' === $element['type'])
        <li class="page-item page-dots disabled"><a class="page-link" href="#">{{ $element['label'] }}</a></li>
@elseif ($element['disabled'])
        <li class="{{ $element['type'] }}_page page-item disabled"><a class="page-link" href="#">{{ $element['label'] }}</a></li>
@elseif ($element['active'])
        <li class="page-item active"><a class="page-link" href="#">{{ $element['label'] }}</a></li>
@elseif ('page' === $element['type'])
        <li class="page-item"><a class="page-link" href="{{ $element['url'] }}">{{ $element['label'] }}</a></li>
@else
        <li class="{{ $element['type'] }}_page page-item"><a class="page-link" href="{{ $element['url'] }}">{{ $element['label'] }}</a></li>
@endif
@endforeach
    </ul>
</nav>
@endif
```

To use another view for one call, or everywhere:

```php
{!! $orders->links(3, 'pagination.compact') !!}

Paginator::$view = 'pagination.compact';
```

The first argument of `links()` is how many page numbers to show on each side of the current one
(3 by default). `render()` is an alias of `links()`.

> The "Previous" and "Next" labels come from `application/language/<lang>/pagination.php`. Call
> `speaks('en')` on a paginator to render it in another language.

<a id="styling-pagination"></a>

## Styling Pagination

The default view produces Bootstrap-compatible markup, so a Bootstrap project needs no extra CSS:

```html
<nav class="pagination-nav">
    <ul class="pagination">
        <li class="previous_page page-item"><a class="page-link" href="foo">Previous</a></li>
        <li class="page-item"><a class="page-link" href="foo">1</a></li>
        <li class="page-item page-dots disabled"><a class="page-link" href="#">...</a></li>
        <li class="page-item active"><a class="page-link" href="#">13</a></li>
        <li class="page-item"><a class="page-link" href="foo">14</a></li>
        <li class="next_page page-item"><a class="page-link" href="foo">Next</a></li>
    </ul>
</nav>
```

On the first page the "Previous" item gets a `disabled` class (`previous_page page-item disabled`),
and likewise "Next" on the last page. A minimal plain-CSS starting point:

```css
.pagination { display: inline-block; padding: 0; margin: 20px 0; }
.pagination .page-item { display: inline; margin: 0 2px; }
.pagination .page-link { padding: 5px 10px; border: 1px solid #ddd; text-decoration: none; color: #333; }
.pagination .page-item.active .page-link { background: #007bff; color: #fff; border-color: #007bff; }
.pagination .page-item.disabled .page-link { color: #999; cursor: not-allowed; }
```
