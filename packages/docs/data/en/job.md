# Job

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Usage](#cara-penggunaan)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

This component is used to queue event to a database and
later fire them through rakit console or a unix job.

This will be useful when you need to, for example,
schedule an e-mail to an user 24 hours after the registration.

```php
$name         = 'email24';
$payloads     = ['user_id' => 1, 'message' => 'Welcome new member!'];
$scheduled_at = Date::make('now')->remake('+24 hours');

Job::add($name, $payloads, $scheduled_at);
```

<a id="cara-penggunaan"></a>

## Usage

To use this component, you will need to create the `'jobs'` table:

```bash
php rakit job:table
```

Then:

```bash
php rakit migrate
```

Now play with it. For example:

```php
Route::get('execute-my-event', function () {
    // Ordinary style:
    // Event::fire('log-something', ['foo', ['bar' => 'baz']]);

    // With job:
    Job::add('log-something', ['foo', ['bar' => 'baz']]);
});

// The event
Event::listen('log-something', function ($foo) {
    Log::info(sprintf('Foo value is: %s', json_encode($foo)));
});
```

Tambahkan baris berikut ke crontab:

```bash
*/1 * * * * php /var/www/mysite/rakit job:run log-something

# or,

*/1 * * * * php /var/www/mysite/rakit job:runall
```

Or just run the command:

```bash
php rakit job:run log-something
```

Thats it, the job will fire all the `log-something` events
in the queue in [FIFO](http://en.wikipedia.org/wiki/FIFO) order.

If you want, there is a method to run all the queues in the table:

```bash
php rakit job:runall
```

You can also fire the events through a route,
just change the `job/config/job.php` file to allow it:

```php
'cli_only' => false,
```

And then:

```php
Route::get('execute-log-something', function () {
    // Don't forget to protect this route..
    $secret_key = Hash::make('s3cr3t');

    if (! Hash::check(Input::query('key'), $secret_key)) {
        return Response::json([
            'success' => false,
            'message' => 'Unauthorized: wrong secret key!',
        ], 401);
    }

    $result = Job::run('log-something');

    return Response::json([
        'success' => $result,
        'message' => 'Executing log-something: '.($result ? 'success' : 'failed'),
    ]);
});
```

Visit ` https://mysite.com/execute-log-something?key=s3cr3t` to execute the job.
