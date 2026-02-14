# Data Pagination

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Using Query Builder](#using-query-builder)
-   [Adding Pagination Links](#adding-pagination-links)
-   [Creating Manual Pagination](#creating-manual-pagination)
-   [Styling Pagination](#styling-pagination)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Pagination makes it easy for you to display large amounts of data by dividing it into several pages.
Rakit provides a simple and easy-to-use API for pagination.

**Advantages of using Pagination:**
- Improves performance by limiting the amount of data loaded
- Improves user experience with easy navigation
- Reduces server and database load

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

#### Display the results in view:

```php
<?php foreach ($orders->results as $order): ?>
    <tr>
        <td><?php echo $order->id; ?></td>
        <td><?php echo $order->name; ?></td>
        <td><?php echo $order->created_at; ?></td>
    </tr>
<?php endforeach; ?>
```

**Using Blade:**

```php
@foreach ($orders->results as $order)
    <tr>
        <td>{{ $order->id }}</td>
        <td>{{ $order->name }}</td>
        <td>{{ $order->created_at }}</td>
    </tr>
@endforeach
```

#### Also display the pagination links:

```php
<?php echo $orders->links(); ?>
```

The `links()` method above will create a list of page links that look like this:

```ini
Previous 1 2 ... 24 25 26 27 28 29 30 ... 78 79 Next
```

The paginator will also automatically determine which page you are currently on and update the data
and links.

**Pagination Information:**

You can access other pagination information:

```php
// Total results
<?php echo $orders->total; ?>

// Results per page
<?php echo $orders->per_page; ?>

// Current page
<?php echo $orders->page; ?>

// Last page
<?php echo $orders->last; ?>

// Results from (from which index)
<?php echo $orders->from; ?>

// Results to (to which index)
<?php echo $orders->to; ?>
```

You can also create "next" and "previous" links:

#### Creating simple "Next" and "Previous" links:

```php
<?php echo $orders->previous().' '.$orders->next(); ?>
```

**With custom text:**

```php
<?php echo $orders->previous('&laquo; Previous'); ?>
<?php echo $orders->next('Next &raquo;'); ?>
```

**Check if there are pages before/after:**

```php
<?php if ($orders->page > 1): ?>
    <?php echo $orders->previous('&laquo; Prev'); ?>
<?php endif; ?>

<?php if ($orders->page < $orders->last): ?>
    <?php echo $orders->next('Next &raquo;'); ?>
<?php endif; ?>
```

_Read more:_

-   _[Query Builder](/docs/database/magic)_

<a id="adding-pagination-links"></a>

## Adding Pagination Links

You can also add more items to the query string of pagination links, such as
the column you are sorting.

#### Adding query string to pagination links:

```php
<?php echo $orders->appends(['sort' => 'votes'])->links(); ?>
```

**Adding multiple parameters:**

```php
<?php echo $orders->appends(['sort' => 'votes', 'order' => 'desc'])->links(); ?>
```

The above example will produce URLs that look like this:

```html
mysite.com/orders?page=2&sort=votes
mysite.com/orders?page=3&sort=votes&order=desc
```

**Preserve all query strings from request:**

```php
<?php echo $orders->appends(Input::except('page'))->links(); ?>
```

This will preserve all query parameters from the current request except `page`.

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

<a id="styling-pagination"></a>

## Styling Pagination

All pagination link elements can be styled using CSS. Here is an example of the HTML
elements generated by the `links()` method:

```html
<div class="pagination">
    <ul>
        <li class="previous_page"><a href="foo">Previous</a></li>

        <li><a href="foo">1</a></li>
        <li><a href="foo">2</a></li>

        <li class="dots disabled"><a href="#">…</a></li>

        <li><a href="foo">11</a></li>
        <li><a href="foo">12</a></li>

        <li class="active"><a href="#">13</a></li>

        <li><a href="foo">14</a></li>
        <li><a href="foo">15</a></li>

        <li class="dots disabled"><a href="#">…</a></li>

        <li><a href="foo">25</a></li>
        <li><a href="foo">26</a></li>

        <li class="next_page"><a href="foo">Next</a></li>
    </ul>
</div>
```

When you are on the first page, the "Previous" link will be disabled. Likewise,
the "Next" link will be disabled when you are on the last page.

The generated HTML will look like this:

```html
<li class="disabled previous_page"><a href="#">Previous</a></li>
```

**Example styling with CSS:**

```css
.pagination {
    margin: 20px 0;
}

.pagination ul {
    display: inline-block;
    padding: 0;
    margin: 0;
}

.pagination li {
    display: inline;
    margin: 0 2px;
}

.pagination li a {
    padding: 5px 10px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
}

.pagination li.active a {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.pagination li.disabled a {
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
            @foreach ($users->results as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination-info">
        Showing {{ $users->from }} to {{ $users->to }} of {{ $users->total }} results
    </div>

    {{ $users->links() }}
</div>
```
