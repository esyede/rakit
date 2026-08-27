# Data Pagination

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Using Query Builder](#using-query-builder)
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

Pagination splits large result sets into pages so the database fetches less
data and the user can navigate the results. Rakit's query builder and Facile
models both expose a `paginate()` helper that returns a `Paginator` instance
ready for rendering.

<a id="using-query-builder"></a>

## Using Query Builder

Let's explore a complete example of pagination using [Query Builder](/docs/database/magic):

#### Get paginated results from query:

```php
$perpage = 10;

$orders = DB::table('orders')->paginate($perpage);
```

You can also pass an array of table column names you want to retrieve in the query:

```php
$orders = DB::table('orders')->paginate($perpage, ['id', 'name', 'created_at']);
```

**With WHERE condition:**

```php
$orders = DB::table('orders')
    ->where('status', '=', 'completed')
    ->order_by('created_at', 'desc')
    ->paginate(15);
```

**Using Facile Model:**

```php
// In controller
$users = User::where('active', '=', true)->paginate(20);

return View::make('users.index', compact('users'));
```

The full signature is `paginate($perpage, $columns, $page_name, $page)`. The last two
arguments are explained in [Custom Page Name](#custom-page-name).

> **Note:** `GROUP BY`, `HAVING`, `distinct()` and a `take()`/`skip()` set before `paginate()`
> are all handled correctly when the total is counted, so `total` is the real number of
> records and not the size of the first group.

<a id="displaying-the-results"></a>

## Displaying the Results

The paginator is iterable, countable and array accessible, so you can loop over it directly:

```php
@foreach ($orders as $order)
    <tr>
        <td>{{ $order->id }}</td>
        <td>{{ $order->name }}</td>
        <td>{{ $order->created_at }}</td>
    </tr>
@endforeach
```

`$orders->results` and `$orders->items()` both hand back the same `Collection`, so this
works just as well:

```php
@foreach ($orders->items() as $order)
    {{ $order->name }}
@endforeach
```

#### Also display the pagination links:

```php
{{ $orders->links() }}
```

The `links()` method above will create a list of page links that look like this:

```ini
Previous 1 2 ... 24 25 26 27 28 29 30 ... 78 79 Next
```

The paginator will also automatically determine which page you are currently on and update the
data and links. Nothing at all is rendered when there is only one page.

<a id="pagination-information"></a>

## Pagination Information

```php
$orders->items();             // the results of the current page (a Collection)
$orders->total();             // total number of records
$orders->per_page();          // number of records per page
$orders->current_page();      // current page number
$orders->last_page();         // last page number, always at least 1
$orders->first_item();        // index of the first record on this page, NULL when empty
$orders->last_item();         // index of the last record on this page, NULL when empty
$orders->count();             // number of records on this page
$orders->is_empty();          // TRUE when this page holds no record
$orders->is_not_empty();      // the opposite of is_empty()
$orders->has_pages();         // TRUE when there is more than one page
$orders->has_more_pages();    // TRUE when there is a page after this one
$orders->on_first_page();     // TRUE when this is page one
$orders->on_last_page();      // TRUE when this is the last page
$orders->page_name();         // name of the query string parameter, 'page' by default
```

And the urls:

```php
$orders->path();                // base url, without the query string
$orders->url(3);                // url of page 3
$orders->first_page_url();
$orders->last_page_url();
$orders->next_page_url();       // NULL when there is no page after this one
$orders->previous_page_url();   // NULL when this is page one
```

The public properties `$orders->results`, `$orders->page`, `$orders->last`, `$orders->total`
and `$orders->perpage` are still there and hold the same values, so older code keeps working.

**Example:**

```php
<div class="pagination-info">
    Showing {{ $orders->first_item() }} to {{ $orders->last_item() }}
    of {{ $orders->total() }} results
</div>
```

`through()` runs a callback over the results of the current page without losing the
pagination state:

```php
$orders->through(function ($order) {
    $order->total = number_format($order->total);
    return $order;
});
```

<a id="adding-pagination-links"></a>

## Adding Pagination Links

You can also add more items to the query string of pagination links, such as
the column you are sorting.

#### Adding query string to pagination links:

```php
{{ $orders->appends(['sort' => 'votes'])->links() }}
```

`appends()` also accepts a single key and value, and it merges instead of replacing, so it
may be called more than once:

```php
$orders->appends('sort', 'votes')->appends('order', 'desc');
```

The above example will produce URLs that look like this:

```html
mysite.com/orders?sort=votes&page=2
mysite.com/orders?sort=votes&order=desc&page=3
```

**Preserve all query strings from request:**

```php
{{ $orders->with_query_string()->links() }}
```

This will preserve all query parameters from the current request, except the page number.

**Adding a fragment (hash):**

```php
{{ $orders->fragment('daftar')->links() }}   // mysite.com/orders?page=2#daftar
```

<a id="custom-page-name"></a>

## Custom Page Name

When a single page holds two paginated lists, give each one its own query string parameter:

```php
$orders = DB::table('orders')->paginate(10, ['*'], 'order_page');
$invoices = DB::table('invoices')->paginate(10, ['*'], 'invoice_page');
```

Their links then read `?order_page=2` and `?invoice_page=3`, so paging through one does not
reset the other.

You can also force a page number instead of reading it from the query string:

```php
$orders = DB::table('orders')->paginate(10, ['*'], 'page', 3);
```

<a id="pagination-as-json"></a>

## Pagination as JSON

The paginator serializes itself into the same shape Laravel uses, which makes it a drop-in
response for a javascript front end:

```php
return Response::json(DB::table('orders')->paginate(10));
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

`to_array()`, `to_json()` and `json_encode()` all produce the same thing. When the results are
Facile models, each one is converted with its own `to_array()`, so `$hidden`, `$visible`,
`$appends` and `$casts` are all honored.

`link_elements()` hands you that `links` array in a richer form, with a `type`
(`previous`, `next`, `page` or `separator`), a `page` number and a `disabled` flag, which is
what the pagination view itself is built on.

<a id="creating-manual-pagination"></a>

## Creating Manual Pagination

Sometimes you may need to create pagination manually, without using the query builder.
Useful when you are working with data from API or other data sources.

#### Creating pagination manually:

```php
$orders = Paginator::make($items, $total, $perpage);
```

**Complete example:**

```php
// Get data from other source (e.g. API)
$all_orders = API::get_orders(); // Returns array with 100 items

// Count total
$total = count($all_orders);

// Determine per page
$perpage = 10;

// Get current page
$page = Input::get('page', 1);

// Slice data according to page
$offset = ($page - 1) * $perpage;
$items = array_slice($all_orders, $offset, $perpage);

// Create paginator
$orders = Paginator::make($items, $total, $perpage);

return View::make('orders.index', compact('orders'));
```

The full signature is `Paginator::make($results, $total, $perpage, $page_name, $page)`.

<a id="customizing-the-view"></a>

## Customizing the View

The markup lives in a Blade view, not in PHP. The first time `links()` is called, Rakit
publishes `system/console/commands/stubs/paginator.stub` to
`application/views/paginator.blade.php`. Edit that file to change the markup; it is never
overwritten once it is there, so your changes are safe.

The view receives two variables:

-   `$paginator`, the paginator itself
-   `$elements`, the list from `link_elements()`

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
@else
        <li class="page-item"><a class="page-link" href="{{ $element['url'] }}">{{ $element['label'] }}</a></li>
@endif
@endforeach
    </ul>
</nav>
@endif
```

**Using a different view for one call:**

```php
{{ $orders->links(3, 'pagination.compact') }}
```

**Using a different view everywhere:**

```php
Paginator::$view = 'pagination.compact';
```

The first argument of `links()` is how many page numbers to show on each side of the current
one, three by default. `render()` is an alias of `links()`.

> **Note:** the "Previous" and "Next" labels come from
> `application/language/<lang>/pagination.php`. Use `speaks('en')` to render a single
> paginator in another language.

<a id="styling-pagination"></a>

## Styling Pagination

Here is the HTML the default view generates:

```html
<nav class="pagination-nav">
    <ul class="pagination">
        <li class="previous_page page-item"><a class="page-link" href="foo">Previous</a></li>

        <li class="page-item"><a class="page-link" href="foo">1</a></li>
        <li class="page-item"><a class="page-link" href="foo">2</a></li>

        <li class="page-item page-dots disabled"><a class="page-link" href="#">...</a></li>

        <li class="page-item"><a class="page-link" href="foo">12</a></li>
        <li class="page-item active"><a class="page-link" href="#">13</a></li>
        <li class="page-item"><a class="page-link" href="foo">14</a></li>

        <li class="page-item page-dots disabled"><a class="page-link" href="#">...</a></li>

        <li class="page-item"><a class="page-link" href="foo">25</a></li>
        <li class="page-item"><a class="page-link" href="foo">26</a></li>

        <li class="next_page page-item"><a class="page-link" href="foo">Next</a></li>
    </ul>
</nav>
```

When you are on the first page, the "Previous" link will be disabled. Likewise,
the "Next" link will be disabled when you are on the last page:

```html
<li class="previous_page page-item disabled"><a class="page-link" href="#">Previous</a></li>
```

The class names match Bootstrap, so a Bootstrap based project needs no extra CSS at all.

**Example styling with plain CSS:**

```css
.pagination {
    display: inline-block;
    padding: 0;
    margin: 20px 0;
}

.pagination .page-item {
    display: inline;
    margin: 0 2px;
}

.pagination .page-link {
    padding: 5px 10px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
}

.pagination .page-item.active .page-link {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.pagination .page-item.disabled .page-link {
    color: #999;
    cursor: not-allowed;
}
```

**Complete example in view with Bootstrap:**

```php
<div class="container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination-info">
        Showing {{ $users->first_item() }} to {{ $users->last_item() }}
        of {{ $users->total() }} results
    </div>

    {{ $users->links() }}
</div>
```

_Read more:_

-   _[Query Builder](/docs/database/magic)_
-   _[Facile Model](/docs/database/facile)_
